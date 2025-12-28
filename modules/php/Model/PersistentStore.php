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

/*
  Basic guideline: all mutations are done through Model public methods. While it may return modeled objects with mutable fields,
  mutations should not be made directly on those objects.
*/

class PersistentStore {

    public function __construct(private Db $db = new DefaultDb()) {}

    public function updateTileType(Tile $tile): void {
        $this->db->execute(
            "UPDATE tiles
             SET type = '{$tile->type->value}'
             WHERE id={$tile->id}"
        );
    }

    /**
     * Used to insert extra tiles (the blocked tiles for 2p).
     *
     * @param list<Tile> $tilepool
     */
    public function insertTiles(array $tilepool): void {
        $values = implode(
            ',',
            array_map(fn ($tile) => "({$tile->id},'{$tile->type->value}')",
                      $tilepool)
        );
        $this->db->execute("INSERT INTO tiles (id, type) VALUES {$values}");
    }

    public function updateScore(int $player_id, int $score): void {
        $this->db->execute("
            UPDATE player
            SET player_score = {$score},
                player_score_aux = money
            WHERE player_id = {$player_id}
        ");
    }

    private static function nullableIntStr(?int $val): string {
        if ($val === null) { return "NULL"; }
        return "{$val}";
    }

    private static function nullableIntVal(?string $val): ?int {
        if ($val === null) { return null; }
        return intval($val);
    }

    public function updatePlayer(Player $player): void {
        $taken = self::nullableIntStr($player->truck_taken);
        $this->db->execute(
            "UPDATE player
             SET money = {$player->money},
                 purchased_extensions = {$player->purchased_extensions},
                 truck_taken = {$taken}
             WHERE player_id = {$player->id}"
        );
    }

    public function setBankMoney(int $money): void {
        $this->db->execute("UPDATE zglobals SET bank_money = {$money}");
    }

    public function getBankMoney(): int {
        $row = $this->db->getSingleFieldList("SELECT bank_money FROM zglobals");
        return intval($row[0]);
    }

    /** @return array{players: array<int,Player>,trucks: array<int,Truck>, enclosures:array<int,array<int,Enclosure>>,stock:Stock} */
    public function retrieveAll(): array {
        $players = $this->retrievePlayers();
        $pencs = [];
        foreach ($players as $player) {
            $pencs[$player->id] = Enclosure::forPlayer($player);
        }
        $trucks = Truck::forPlayerCount(count($players));
        $stocktiles = [];
        $drawn = Tile::Empty();
        $rows = $this->db->getObjectList("SELECT * FROM tiles ORDER BY location,loc_id,loc_pos");
        foreach ($rows as $row) {
            $tile = new Tile(intval($row['id']), TileType::from($row['type']));
            $loc = $row['location'];
            switch ($loc) {
            case 'S':
                $stocktiles[] = $tile;
                break;
            case 'D':
                $drawn = $tile;
                break;
            case 'T':
                $truck_id = intval($row['loc_id']);
                $truck_pos = intval($row['loc_pos']);
                $truck = $trucks[$truck_id];
                $curr = $truck->tileAt($truck_pos);
                if ($curr->isEmpty()) {
                    $truck->placeTileAt($tile, $truck_pos);
                } else if ($curr != $tile) {
                    throw new ModelException("Tried to put {$tile} at {$truck_pos} on truck {$truck_id} but it was not empty, had {$curr}");
                }
                break;
            case 'E':
                $pid = intval($row['player_id']);
                if (!isset($players[$pid])) {
                    throw new ModelException("Unknown player_id {$pid} for enclosure");
                }
                $enc_id = intval($row['loc_id']);
                $enc_pos = intval($row['loc_pos']);
                $p = $pencs[$pid][$enc_id]->placeTile($tile, $enc_pos);
                if ($p->space->pos <> $enc_pos) {
                    throw new ModelException("Tried to put {$tile} at {$enc_pos} in {$pid}'s enclosure {$enc_id} but it ended up at {$p}");
                }
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
            "players" => $players,
            "trucks" => $trucks,
            "stock" => $stock,
            "enclosures" => $pencs,
        ];
    }

    /** @return array<int,Player> */
    private function retrievePlayers(): array {
        $players = [];
        $data = $this->db->getObjectList("SELECT player_id, money, purchased_extensions, truck_taken
                                          FROM player");
        $numPlayers = count($data);
        foreach ($data as $row) {
            $id = intval($row["player_id"]);
            $taken = self::nullableIntVal($row["truck_taken"]);
            $players[$id] = new Player(
                $id,
                intval($row["money"]),
                $numPlayers,
                intval($row["purchased_extensions"]),
                $taken);
        }
        return $players;
    }

    /**
     * FIXME: this should be list<Tile>
     * @param array<int,Tile> $tiles
     */
    public function insertStock(array $tiles): void {
        // Insert overall tile -> type map.
        $values = [];
        $i = 1;
        foreach ($tiles as $tile) {
            $values[] = "({$tile->id},'{$tile->type->value}', 'S', $i)";
            $i++;
        }
        $this->db->execute("INSERT INTO tiles (id, type, location, loc_pos) VALUES "
                           . implode(',', $values));
    }

    public function updateStock(Stock $stock): void {
        $tile_id = $stock->drawn->id;
        if (!$stock->drawn->isEmpty()) {
            $this->db->execute(
                "UPDATE tiles
                 SET location = 'D',
                     player_id = NULL,
                     loc_id = NULL,
                     loc_pos = NULL
                 WHERE id = {$tile_id}");
        } else {
            //            throw new ModelException("Attempt to update stock but no drawn tile");
        }
    }

    public function updateTruck(Truck $truck): void {
        $i = 1;
        foreach ($truck->getAllTiles() as $tile) {
            if (!$tile->isEmpty()) {
                $this->db->execute(
                    "UPDATE tiles
                     SET location = 'T',
                         player_id = NULL,
                         loc_id = {$truck->id},
                         loc_pos = {$i}
                     WHERE id = {$tile->id}");
            }
            $i++;
        }
    }

    public function deleteTile(Tile $tile): void {
        $this->db->execute("DELETE FROM tiles WHERE id = {$tile->id}");
    }

    /** @param list<Enclosure> $enclosures */
    public function updateEnclosures(int $player_id, array $enclosures): void {
        if (count($enclosures) == 0) {
            return;
        }
        // uniquify
        $toUpdate = [];
        foreach ($enclosures as $enc) {
            if (!isset($toUpdate[$enc->id])) {
                $toUpdate[$enc->id] = $enc;
                // FIXME: optimization potential: only need to do this for exchanges.
                // Could instead remove unique constraint
                $this->db->execute(
                    "UPDATE tiles
                     SET location = '', player_id = NULL, loc_id = NULL, loc_pos = NULL
                     WHERE player_id = {$player_id} AND loc_id = {$enc->id}"
                );
            }
        }

        foreach ($toUpdate as $enclosure) {
            foreach ($enclosure->nonEmptyContents() as $pos => $t) {
                $this->db->execute(
                    "UPDATE tiles
                     SET player_id = {$player_id},
                         location = 'E',
                         loc_id = {$enclosure->id},
                         loc_pos = {$pos}
                     WHERE id = {$t->id}"
                );
            }
        }
    }
}
