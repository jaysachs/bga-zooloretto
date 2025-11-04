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
		$make = function (Tile $tile): string {
			$id = $tile->id;
			$ty = $tile->type->value;
			return "($id,'$ty')";
		};

        // Overall tile -> type map.
        $this->db->execute("INSERT INTO tiles (id, type) VALUES "
                           . implode(',', array_map($make, $tilepool)));

        // Stock
        $notBlock = function (Tile $t) : bool {
            return $t->type != TileType::BLOCK;
        };
        $negate = function (\Closure $c): \Closure {
            return function($t)  use(&$c) : bool { return !$c($t); };
        };

		$stock = Stock::create(array_filter($tilepool, $notBlock));

        $make2 = function (Tile $tile) : string { return "($tile->id)"; };
        $values = array_map($make2, $stock->primary);
		$this->db->execute("INSERT INTO primary_stock (tile_id) VALUES "
                           . implode(',', $values));
        $values = array_map($make2, $stock->endgame);
		$this->db->execute("INSERT INTO endgame_stock (tile_id) VALUES "
                           . implode(',', $values));

        // Trucks
        $trucks = [];
        if ($player_count == 2) {
            $blocks = array_filter($tilepool, $negate($notBlock));
            if (count($blocks) != 3) {
                throw new ModelException("Expected exactly 3 block tiles");
            }
            $trucks[] = new Truck(1);
            $trucks[] = new Truck(2, null, null, array_shift($blocks));
            $trucks[] = new Truck(3, null, array_shift($blocks), array_shift($blocks));
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

}

/*
echo "hi\n";
$model = new Model( null, new TestDb() );
$model->createNewGame(2);
*/
