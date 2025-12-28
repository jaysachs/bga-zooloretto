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

    public function updateTile(Tile $tile): void {
        $this->db->Execute("UPDATE tiles SET type = '{$tile->type->value}' WHERE id={$tile->id}");
    }

    /** @param list<Tile> $tilepool  */
    public function insertTiles(array $tilepool): void {
        // Insert overall tile -> type map.
        $this->db->execute("INSERT INTO tiles (id, type) VALUES "
                           . implode(',', array_map(fn ($tile) => "({$tile->id},'{$tile->type->value}')",
                                                    $tilepool)));
    }

    /** @param list<Truck> $trucks */
    public function insertTrucks(array $trucks): void {
        $values = [];
        foreach ($trucks as $truck) {
            $ts = array_map(fn (Tile $t) => "$t->id", $truck->getAllTiles());
            $values[] = sprintf("(%d, %s, %s, %s)", $truck->id, $ts[1], $ts[2], $ts[3]);
        }
        $this->db->execute("INSERT INTO trucks (id, tile_id1, tile_id2, tile_id3) VALUES "
                           . implode(',', $values));

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

    /** @return array{players: array<int,Player>,trucks: list<Truck>, enclosures:array<int,array<int,Enclosure>>,stock:Stock} */
    public function retrieveAll(): array {
        $players = $this->retrievePlayers();
        $pencs = [];
        foreach ($players as $player) {
            $pencs[$player->id] = Enclosure::forPlayer($player);
        }
        $this->populateEnclosures($pencs);
        $trucks = $this->retrieveTrucks();
        $stock = $this->retrieveStock();
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
		$this->db->execute(
            "INSERT INTO stock (tile_id) VALUES "
             . implode(',', array_map(fn ($t) => "({$t->id})", $tiles)));

    }

    private function retrieveStock(): Stock {
        $drawn = Tile::empty();
        $rows = $this->db->getObjectList(
            "SELECT s.seq_id AS seq_id, s.tile_id AS tile_id, t.type AS type, s.drawn AS drawn
             FROM stock s
             INNER JOIN tiles t ON t.id = s.tile_id ORDER BY s.seq_id"
        );
        $tiles = [];
        foreach ($rows as $row) {
            $tile = new Tile(intval($row["tile_id"]), TileType::from($row["type"]));
            if (intval($row["drawn"]) === 1) {
                $drawn = $tile;
            } else {
                $tiles[] = $tile;
            }
        }
        return new Stock($tiles, $drawn);
    }

    public function updateStock(Stock $stock): void {
        // could just brute-force delete it all and re-insert, but let's be smart;
        //   this is only called when a tile is drawn or when it's moved to a truck
        //   (i.e. removed from the stock)
        $tile_id = $stock->drawn->id;
        if ($tile_id) {
            // tile was drawn
            $this->db->execute("UPDATE stock SET drawn = 1 WHERE tile_id = {$tile_id}");
        } else {
            // tile moved to truck
            $this->db->execute("DELETE FROM stock WHERE drawn = 1");
        }
    }

    /** @return list<Truck> */
    private function retrieveTrucks(): array {
        $rows = $this->db->getObjectList(
            'SELECT tr.id, tr.tile_id1, tr.tile_id2, tr.tile_id3, t1.type AS type1, t2.type AS type2, t3.type AS type3, p.player_id AS taken_by
             FROM trucks AS tr
             LEFT OUTER JOIN tiles AS t1 ON tr.tile_id1 = t1.id
             LEFT OUTER JOIN tiles AS t2 ON tr.tile_id2 = t2.id
             LEFT OUTER JOIN tiles AS t3 ON tr.tile_id3 = t3.id
             LEFT OUTER JOIN player as p ON p.truck_taken = tr.id
             ORDER BY tr.id');
         return array_map(
             /**
              * @param array<string,string> $row
              */
             function (array $row) : Truck {
                 $tile = function(int $pos) use (&$row): Tile {
                     return new Tile(intval($row["tile_id{$pos}"]), TileType::from($row["type{$pos}"]));
                 };
                 $taken_by = self::nullableIntVal($row["taken_by"]);
                 return new Truck(intval($row['id']), [$tile(1), $tile(2), $tile(3)], $taken_by);
             },
             $rows
         );
    }

    public function updateTruck(Truck $truck): void {
        $tiles = $truck->getAllTiles();
        $this->db->execute(
            "UPDATE trucks
             SET tile_id1={$tiles[1]->id},
                 tile_id2={$tiles[2]->id},
                 tile_id3={$tiles[3]->id}
             WHERE id = {$truck->id}");
    }

    /**
     * @param array<int,array<int,Enclosure>> $pencs
     */
    public function populateEnclosures(array $pencs): void {
        $rows = $this->db->getObjectList("SELECT ec.enclosure_id, ec.pos, ec.tile_id, ec.player_id, t.type
                                          FROM enclosure_contents ec
                                          INNER JOIN tiles t
                                          ON ec.tile_id = t.id
                                          ORDER BY ec.player_id, ec.enclosure_id, ec.pos");
        foreach ($rows as $row) {
            $player_id = intval($row['player_id']);
            $eid = intval($row['enclosure_id']);
            $pos = intval($row['pos']);
            $tileid = intval($row['tile_id']);
            $type = TileType::from($row["type"]);
            if (!$type->isEmpty()) {
                if (!isset($pencs[$player_id][$eid])) {
                    throw new ModelException("No enclosure {$eid} for {$player_id} but it has {$type->value} at {$pos}");
                }
                $pencs[$player_id][$eid]->placeTile(new Tile($tileid, $type), $pos);
            }
        }
    }

    /** @param array<int,Enclosure> $enclosures */
    public function updateEnclosures(int $player_id, array $enclosures): void {
        if (count($enclosures) == 0) {
            return;
        }
        $ids = implode(',', array_map(fn ($e) => "{$e->id}", $enclosures));
        $this->db->execute("DELETE FROM enclosure_contents WHERE player_id={$player_id} AND enclosure_id IN ({$ids})");
        $values = [];
        $seen = [];
        foreach ($enclosures as $enclosure) {
            if (isset($seen[$enclosure->id])) {
                continue;
            }
            $seen[$enclosure->id] = true;
            foreach ($enclosure->nonEmptyContents() as $pos => $t) {
                $values[] = "({$player_id}, {$enclosure->id}, {$pos}, {$t->id})";
            }
        }
        if (count($values) == 0) {
            return;
        }
        $sql = 'INSERT INTO enclosure_contents (player_id, enclosure_id, pos, tile_id) VALUES '
             . implode(',', $values);
        $this->db->execute($sql);
    }
}
