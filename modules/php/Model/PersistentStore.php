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

use \Bga\GameFramework\Db\Globals;
use \Bga\GameFramework\Table;


/*
  Basic guideline: all mutations are done through Model public methods. While it may return modeled objects with mutable fields,
  mutations should not be made directly on those objects.
*/

class PersistentStore {

    public function __construct(private Db $db = new DefaultDb()) {}

    /** @param Enclosure[] $enclosures */
    public function insertEnclosures(int $player_id, array $enclosures): void {
        $make = function(Enclosure $enc) use (&$player_id): string {
            return "({$player_id}, {$enc->id}, {$enc->animal_capacity}, {$enc->stall_capacity})";
        };
        $this->db->execute("INSERT INTO enclosures (player_id, enclosure_id, animal_capacity, stall_capacity) VALUES "
                            . implode(',', array_map($make, $enclosures)));
        // See note blow. We could insert "empty" contents for all stalls, which would facilite updates like swaps.
        // However, barns are a kind of enclosure and don't have a fixed capacity so that's not possible.
        // Better to be consistent.
    }

    public function updateTile(Tile $tile): void {
        $this->db->Execute("UPDATE tiles SET type = '{$tile->type->value}' WHERE id={$tile->id}");
    }

    /** @param $tilepool Tile[] */
    public function insertTiles(array $tilepool): void {
        // Insert overall tile -> type map.
        $this->db->execute("INSERT INTO tiles (id, type) VALUES "
                           . implode(',', array_map(fn ($tile) => "({$tile->id},'{$tile->type->value}')",
                                                    $tilepool)));
    }

    public function insertStock(Stock $stock): void {
        // Insert stock piles
		$this->db->execute("INSERT INTO primary_stock (tile_id) VALUES "
                           . implode(',', array_map(fn ($i) => "($i)", $stock->primaryIds())));
		$this->db->execute("INSERT INTO endgame_stock (tile_id) VALUES "
                           . implode(',', array_map(fn ($i) => "($i)", $stock->endgameIds())));

    }

    /** @param $trucks Truck[] */
    public function insertTrucks(array $trucks): void {
        $values = [];
        foreach ($trucks as $truck) {
            $ts = array_map(fn (Tile $t) => "$t->id", $truck->getAllTiles());
            $values[] = sprintf("(%d, %s, %s, %s)", $truck->id, $ts[1], $ts[2], $ts[3]);
        }
        $this->db->execute("INSERT INTO trucks (id, tile_id1, tile_id2, tile_id3) VALUES "
                           . implode(',', $values));

    }

    public function updatePlayer(Player $player): void {
        $this->db->execute("UPDATE player SET money = {$player->money} WHERE player_id = {$player->id}");
    }

    public function setBankMoney(int $money): void {
        $this->db->execute("UPDATE zglobals SET bank_money = {$money}");
    }

    public function incBankMoney(int $delta): void {
        $this->db->execute("UPDATE zglobals SET bank_money = bank_money + {$delta}");
    }

    /** @return Player[] */
    public function retrievePlayers(): array {
        $players = [];
        $data = $this->db->getObjectList("SELECT p.player_id, p.player_no, p.money, e.enclosure_count, t.id AS truck_taken
                                          FROM player AS p
                                          LEFT OUTER JOIN
                                            (SELECT COUNT(*) as enclosure_count, player_id FROM enclosures GROUP BY player_id) AS e
                                          ON p.player_id = e.player_id
                                          LEFT OUTER JOIN trucks AS t
                                          ON p.player_id = t.taken_by");
        $numPlayers = count($data);
        // FIXME: this logic doesn't belong here
        $extensionLimit = $numPlayers == 2 ? 2 : 1;
        foreach ($data as $row) {
            $id = intval($row["player_id"]);
            $taken = intval($row["truck_taken"]);
            // FIXME: this logic doesn't belong here
            // 4 because the barn is an enclosure
            $purchasedExtensions = intval($row["enclosure_count"]) - 4;
            $players[$id] = new Player($id, intval($row["player_no"]), intval($row["money"]), $extensionLimit, $purchasedExtensions, $taken);
        }
        return $players;
    }

    public function retrieveStock(): Stock {
        $tileFromRow = function(array $row): Tile { return new Tile(intval($row["tile_id"]), TileType::from($row["type"])); };

        $row = $this->db->getObjectList("SELECT t.id AS tile_id, t.type AS type, drawn_tile
                                         FROM tiles t
                                         INNER JOIN zglobals g ON t.id = g.drawn_tile");
        $drawn = Tile::empty();
        if ($row <> null && count($row) > 0) {
            $drawn = $tileFromRow($row[0]);
        }
        $select = function (string $tblname) use (&$tileFromRow): array {
            return array_map(
                $tileFromRow,
                $this->db->getObjectList("SELECT p.tile_id AS tile_id, t.type AS type FROM $tblname p INNER JOIN tiles t ON t.id = p.tile_id ORDER BY p.seq_id"));
        };
        return new Stock($select('primary_stock'), $select('endgame_stock'), $drawn);
    }

    public function updateStock(Stock $stock): void {
        $id = $stock->drawn->id;
        $this->db->execute("UPDATE zglobals SET drawn_tile = {$id}");
        $this->db->execute("DELETE FROM primary_stock WHERE tile_id = $id");
    }

    /** @return Truck[] */
    public function retrieveTrucks(): array {
            $rows = $this->db->getObjectList(
                'SELECT tr.id, tr.taken_by, tr.tile_id1, tr.tile_id2, tr.tile_id3, t1.type AS type1, t2.type AS type2, t3.type AS type3
                FROM trucks AS tr
                LEFT OUTER JOIN tiles AS t1 ON tr.tile_id1 = t1.id
                LEFT OUTER JOIN tiles AS t2 ON tr.tile_id2 = t2.id
                LEFT OUTER JOIN tiles AS t3 ON tr.tile_id3 = t3.id
                ORDER BY tr.id');
            return array_map(function (array $row) : Truck {
                $tile = function(int $pos) use (&$row): ?Tile {
                    return new Tile(intval($row["tile_id{$pos}"]), TileType::from($row["type{$pos}"]));
                };
                return new Truck(intval($row['id']), [$tile(1), $tile(2), $tile(3)], intval($row["taken_by"]));
            }, $rows);
    }

    public function updateTruck(Truck $truck): void {
        $tiles = $truck->getAllTiles();
        $this->db->execute(
            "UPDATE trucks
             SET tile_id1={$tiles[1]->id}, tile_id2={$tiles[2]->id}, tile_id3={$tiles[3]->id}, taken_by={$truck->taken_by}
             WHERE id = {$truck->id}");
    }

    /**
     * Returns enclosure mapped by enclosure_id.
     *
     * @return Enclosure[]
     */
    public function getEnclosuresForPlayer(int $player_id) : array {
        // FIXME: need to handle un-purchased extensions!
        // Either a boolean on the Extension (easy)
        //   or not returning it.
        $rows = $this->db->getObjectList("SELECT enclosure_id, animal_capacity, stall_capacity
                                          FROM enclosures e
                                          WHERE player_id = {$player_id}");
        /** @var Enclosure[] */
        $encl = [];
        foreach ($rows as $row) {
            $eid = intval($row['enclosure_id']);
            if ($eid == 0) {
                $encl[$eid] = Enclosure::barn();
            } else {
                $encl[$eid] = Enclosure::create($eid, intval($row['animal_capacity']), intval($row['stall_capacity']));
            }
        }

        $rows = $this->db->getObjectList("SELECT ec.enclosure_id, ec.pos, ec.tile_id, t.type
                                          FROM enclosure_contents ec
                                          INNER JOIN tiles t
                                          ON ec.tile_id = t.id
                                          WHERE ec.player_id = {$player_id}
                                          ORDER BY ec.enclosure_id, ec.pos");
        foreach ($rows as $row) {
            $eid = intval($row['enclosure_id']);
            $pos = intval($row['pos']);
            $tileid = intval($row['tile_id']);
            $type = TileType::from($row["type"]);
            if (!$type->isEmpty()) {
                $encl[$eid]->placeTile(new Tile($tileid, $type), $pos);
            }
        }
        return $encl;
    }

    /** @param Enclosure[] $enclosures */
    public function updateEnclosures(int $player_id, array $enclosures): void {
        $ids = implode(',', array_map(fn ($e) => "{$e->id}", $enclosures));
        $this->db->execute("DELETE FROM enclosure_contents WHERE player_id={$player_id} AND enclosure_id={$ids}");
        $values = [];
        foreach ($enclosures as $enclosure) {
            foreach ($enclosure->nonEmptyContents() as $pos => $t) {
                $values[] = "($player_id, $enclosure->id, $pos, $t->id)";
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
