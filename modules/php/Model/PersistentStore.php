<?php

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * zooloretto implementation : © Jay Sachs <vagabond@covariant.org>
 *
 * Copyright 2025 Jay Sachs <vagabond@covariant.org>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 */

declare(strict_types=1);

namespace Bga\Games\zooloretto\Model;

use Bga\Games\zooloretto\Utils\Db;
use Bga\Games\zooloretto\Utils\DefaultDb;

/*
  Basic guideline: all mutations are done through Model public methods. While it may return modeled objects with mutable fields,
  mutations should not be made directly on those objects.
*/

class PersistentStore {

    public function __construct(private Db $db = new DefaultDb()) {}

    /**
     * Used to insert extra tiles (the blocked tiles for 2p).
     *
     * @param list<Tile> $tilepool
     */
    public function insertTiles(array $tilepool): void {
        $values = implode(
            ',',
            array_map(fn ($tile) => "({$tile->id},'{$tile->type->value}','Z', {$tile->id}+10000)", $tilepool)
        );
        $this->db->execute("INSERT INTO tiles (id, type, location, loc_pos) VALUES {$values}");
    }

    public function updateScore(int $player_id, int $score): void {
        $this->db->execute("UPDATE player
                            SET player_score = {$score}, player_score_aux = money
                            WHERE player_id = {$player_id}");
    }

    private static function nullableIntStr(?int $val): string {
        return $val === null ? "NULL" : "{$val}";
    }

    private static function nullableIntVal(?string $val): ?int {
        return $val === null ? null : intval($val);
    }

    public function updatePlayer(Player $player): void {
        $taken = self::nullableIntStr($player->truck_taken);
        $this->db->execute("UPDATE player
                            SET money = {$player->money},
                                purchased_extensions = {$player->purchased_extensions},
                                truck_taken = {$taken}
                            WHERE player_id = {$player->id}");
    }

    public function setBankMoney(int $money): void {
        $this->db->execute("UPDATE zglobals SET bank_money = {$money}");
    }

    public function getBankMoney(): int {
        $row = $this->db->getSingleFieldList("SELECT bank_money FROM zglobals");
        return intval($row[0]);
    }

    /**
     * @param array<int,Player> $players
     * @return array{trucks: array<int,Truck>, enclosures:array<int,array<int,Enclosure>>,stock:Stock}
     */
    public function retrieveAll(array $players): array {
        $pencs = [];
        foreach ($players as $player) {
            $pencs[$player->id] = Enclosure::forPlayer($player);
        }
        $trucks = Truck::forPlayerCount(count($players));
        $stocktiles = [];
        $drawn = Tile::Empty();
        $reproduced = [];
        // N.B. later operations depend on the ordering here,in particular, the stock is retrieved
        //   by the loc_pos, which is initialized randoomly.
        $rows = $this->db->getObjectList("SELECT * FROM tiles WHERE location <> 'X' ORDER BY location,loc_id,loc_pos");
        foreach ($rows as $row) {
            $id = intval($row['id']);
            if ($id > 10000) {
                $reproduced[intval($id / 10000)] = true;
                $reproduced[$id % 10000] = true;
            }
        }
        foreach ($rows as $row) {
            $id = intval($row['id']);
            $tile = new Tile($id, TileType::from($row['type']), isset($reproduced[$id]));
            $loc = $row['location'];
            switch ($loc) {
            case 'S':
                $stocktiles[] = $tile;
                break;
            case 'D':
                $drawn = $tile;
                break;
            case 'T':
                $trucks[intval($row['loc_id'])]->placeTileAt($tile, intval($row['loc_pos']));
                break;
            case 'E':
                $pencs[intval($row['player_id'])][intval($row['loc_id'])]->placeTile($tile, intval($row['loc_pos']));
                break;
            default:
                throw new ModelException("Unknown location type {$loc}");
            }
        }
        foreach ($players as $player) {
            if ($player->truck_taken !== null) {
                $trucks[$player->truck_taken]->setTakenBy($player->id);
            }
        }
        $stock = new Stock($stocktiles, $drawn);
        return [
            "trucks" => $trucks,
            "stock" => $stock,
            "enclosures" => $pencs,
        ];
    }

    /** @return array<int,Player> */
    public function retrievePlayers(): array {
        $players = [];
        $data = $this->db->getObjectList("SELECT player_id, money, purchased_extensions, truck_taken FROM player");
        $numPlayers = count($data);
        foreach ($data as $row) {
            $id = intval($row["player_id"]);
            $players[$id] = new Player(
                $id,
                intval($row["money"]),
                $numPlayers,
                intval($row["purchased_extensions"]),
                self::nullableIntVal($row["truck_taken"]));
        }
        return $players;
    }

    /** @param array<int,Tile> $tiles */
    public function insertStock(array $tiles): void {
        // Insert overall tile -> type map.
        $values = [];
        foreach ($tiles as $i => $tile) {
            $values[] = "({$tile->id},'{$tile->type->value}', 'S', $i)";
        }
        $this->db->execute("INSERT INTO tiles (id, type, location, loc_pos) VALUES " . implode(',', $values));
    }

    public function updateStock(Stock $stock): void {
        $tile_id = $stock->drawn->id;
        if (!$stock->drawn->isEmpty()) {
            $this->db->execute("UPDATE tiles
                                SET location = 'D', player_id = 0, loc_id = 0, loc_pos = 0
                                WHERE id = {$tile_id}");
        } else {
            throw new ModelException("Attempt to update stock but no drawn tile");
        }
    }

    public function updateTruck(Truck $truck): void {
        foreach ($truck->getAllTiles() as $pos => $tile) {
            if (!$tile->isEmpty()) {
                $this->db->execute("UPDATE tiles
                                    SET location = 'T', player_id = 0, loc_id = {$truck->id}, loc_pos = {$pos}
                                    WHERE id = {$tile->id}");
            }
        }
    }

    public function deleteTile(Tile $tile): void {
        $this->db->execute("UPDATE tiles SET location='X',loc_pos = id+10000 WHERE id = {$tile->id}");
    }

    /** @param list<Enclosure> $enclosures */
    public function updateEnclosures(int $player_id, array $enclosures): void {
        // uniquify
        $toUpdate = [];
        foreach ($enclosures as $enc) {
            if (!isset($toUpdate[$enc->id])) {
                $toUpdate[$enc->id] = $enc;
                // FIXME: optimization potential: only need to do this for exchanges.
                // Could instead remove unique constraint
                $this->db->execute("UPDATE tiles
                                    SET location = 'F'
                                    WHERE player_id = {$player_id} AND loc_id = {$enc->id}");
            }
        }

        foreach ($toUpdate as $enclosure) {
            foreach ($enclosure->nonEmptyContents() as $pos => $t) {
                $this->db->execute("UPDATE tiles
                                    SET player_id = {$player_id},
                                        location = 'E',
                                        loc_id = {$enclosure->id},
                                        loc_pos = {$pos}
                                    WHERE id = {$t->id}");
            }
        }
    }
}
