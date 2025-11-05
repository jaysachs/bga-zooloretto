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

use \Bga\GameFramework\Table;

/*
  Basic guideline: all mutations are done through Model public methods. While it may return modeled objects with mutable fields,
  mutations should not be made directly on those objects.
*/

class Model {

    public function __construct(private Table $game, private Db $db = new DefaultDb()) {}

    public function createNewGame(int $player_count): void {
        $tilepool = Tile::createInitialPool($player_count);
		$stock = Stock::create($tilepool);

        // Now add "tiles" that should not be part of stock
        // NOTE: we hardcode insertion of the "block", excluding it from the pool
        // FIXME: (re)-evaluate this choice of distinguished tile ID, and also reusing the same tile.
        $block = new Tile(0, TileType::BLOCK);
        $tilepool[] = $block;

		$make = function (Tile $tile): string {
			$id = $tile->id;
			$ty = $tile->type->value;
			return "($id,'$ty')";
		};

        // Insert overall tile -> type map.
        $this->db->execute("INSERT INTO tiles (id, type) VALUES "
                           . implode(',', array_map($make, $tilepool)));

        // Insert stock piles
        $make2 = function (Tile $tile) : string { return "($tile->id)"; };
		$this->db->execute("INSERT INTO primary_stock (tile_id) VALUES "
                           . implode(',', array_map($make2, $stock->primary)));
		$this->db->execute("INSERT INTO endgame_stock (tile_id) VALUES "
                           . implode(',', array_map($make2, $stock->endgame)));

        // Trucks
        $trucks = [];
        if ($player_count == 2) {
            $trucks[] = new Truck(1);
            $trucks[] = new Truck(2, null, null, $block);
            $trucks[] = new Truck(3, null, $block, $block);
        }
        else {
			for ($x = 1; $x <= $player_count; $x++) {
                $trucks[] = new Truck($x);
            }
        }
        $values = [];
        foreach ($trucks as $truck) {
            $nv = function(?Tile $t): string { return $t == null ? "NULL" : "$t->id"; };
            $values[] = sprintf("(%d, %s, %s, %s)",
                               $truck->id,
                               $nv($truck->tile1),
                               $nv($truck->tile2),
                               $nv($truck->tile3));
        }
        $this->db->execute("INSERT INTO trucks (id, tile_id1, tile_id2, tile_id3) VALUES "
                           . implode(',', $values));

        // Extra player info
        $this->db->execute("UPDATE player SET money = 2");

        $this->game->globals->set('drawn', 0);
    }


    private function validatePlayer(Player $player): void {
        if ($player !== $this->getPlayer($player->id)) {
            throw new \Exception("Got unknown player");
        }
    }

    public function getActivePlayer(): Player {
        return $this->getPlayer(intval($this->game->getActivePlayerId()));
    }


    private function getPlayer(int $id): Player {
        $players = $this->getPlayers();
        if (isset($players[$id])) {
            return $players[$id];
        }
        throw new \Exception("attempt to retrieve unknown player $id");
    }

    /** @var Player[] */
    private ?array $_players = [];

    /** @returns Player[] */
    public function getPlayers(): array {
        if ($this->_players == null) {
            $this->_players = [];
            $data = $this->db->getObjectList("SELECT player_id, player_no, money FROM player");
            $numPlayers = count($data);
            $potentialExtensions = $numPlayers == 2 ? 2 : 1;
            foreach ($data as $row) {
                $id = intval($row["player_id"]);
                $this->_players[$id] = new Player($id, intval($row["player_no"]), intval($row["money"]), 0, 0, false);
            }
        }
        return $this->_players;
    }

    private ?Stock $_stock = null;

    public function getStock(): Stock {
        if ($this->_stock == null) {
            $tileFromRow = function(array $row): Tile { return new Tile(intval($row["tile_id"]), TileType::from($row["type"])); };
            $d = $this->game->globals->get('drawn', 0);
            $drawn = null;
            if ($d != 0) {
                $row = $this->db->getObjectList("SELECT id AS tile_id, type FROM tiles WHERE id = $d");
                // $this->game->notify->all("fetchedRow", "Fetched row for drawn tile $d", $row);
                $drawn = $tileFromRow($row[0]);
            }
            $select = function (string $tblname) use (&$tileFromRow): array {
                return array_map(
                    $tileFromRow,
                    $this->db->getObjectList("SELECT p.tile_id AS tile_id, t.type AS type FROM $tblname p INNER JOIN tiles t ON t.id = p.tile_id ORDER BY p.seq_id"));
            };
            $this->_stock = new Stock($select('primary_stock'), $select('endgame_stock'), $drawn);
        }
        return $this->_stock;
    }

    private function updateStock(): void {
        $stock = $this->_stock;
        if ($stock == null) {
            return;
        }
        // FIXME: Come back and re-evaulate this approach.
        // As long as all mutations go through model-owned objects, we can handle this.
        // For now, we only handle newly-drawn tile updates.
        if ($stock->drawn == null) {
            $this->game->globals->set('drawn', 0);
            return;
        }
        $id = $stock->drawn->id;
        $this->game->globals->set('drawn', $id);
        $this->db->execute("DELETE FROM primary_stock WHERE tile_id = $id");
    }

    public function drawTile(): Stock {
        $stock = $this->getStock();
        $stock->drawTile();
        $this->updateStock();
        return $stock;
    }

    private ?array $_trucks = null;

    /** @return Truck[] */
    public function getTrucks(): array {
        if ($this->_trucks == null) {
            $rows = $this->db->getObjectList(
                'SELECT tr.id, tr.taken_by, tr.tile_id1, tr.tile_id2, tr.tile_id3, t1.type AS type1, t2.type AS type2, t3.type AS type3
                FROM trucks AS tr
                LEFT OUTER JOIN tiles AS t1 ON tr.tile_id1 = t1.id
                LEFT OUTER JOIN tiles AS t2 ON tr.tile_id2 = t2.id
                LEFT OUTER JOIN tiles AS t3 ON tr.tile_id3 = t3.id
                ORDER BY tr.id');
            $this->_trucks = array_map(function (array $row) : Truck {
                $tile = function(int $pos) use (&$row): ?Tile {
                    $id = $row["tile_id{$pos}"];
                    if ($id == null) { return null; }
                    return new Tile(intval($id), TileType::from($row["type{$pos}"]));
                };
                return new Truck(intval($row['id']), $tile(1), $tile(2), $tile(3));
            }, $rows);
        }
        return $this->_trucks;
    }

    public function getTruck(int $truck_id): Truck {
        foreach ($this->getTrucks() as $truck) {
            if ($truck->id == $truck_id) {
                return $truck;
            }
        }
        throw new \Exception("No truck $truck_id found");
    }

    private function updateTruck(Truck $truck): void {
        $nv = function (?Tile $tile): string { return $tile == null ? "NULL": "{$tile->id}"; };
        $this->db->execute("UPDATE trucks
                            SET tile_id1=" . $nv($truck->tile1) . ", tile_id2=" . $nv($truck->tile2) . ", tile_id3=" . $nv($truck->tile3) .
                            " WHERE id = {$truck->id}");
    }

    public function placeDrawnTileOnTruck(int $truck_id, int $pos): Tile {
        $stock = $this->getStock();
        if ($stock->drawn == null) {
            throw new \Exception("No tile drawn");
        }
        $truck = $this->getTruck($truck_id);
        $tile = $stock->removeDrawnTile();

        $truck->placeTileAt($tile, $pos);
        $this->updateStock();
        $this->updateTruck($truck);

        return $tile;
    }

    public function spacesOnTrucks() : int {
        return array_sum(array_map(function (Truck $t): int { return $t->freeSpaces(); }, $this->getTrucks()));
    }
}

/*
echo "hi\n";
$model = new Model( null, new TestDb() );
$model->createNewGame(2);
*/
