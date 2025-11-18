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
use \Bga\GameFramework\Components\Counters\PlayerCounter;
use \Bga\GameFramework\Components\Counters\TableCounter;



/*
  Basic guideline: all mutations are done through Model public methods. While it may return modeled objects with mutable fields,
  mutations should not be made directly on those objects.
*/

class Model {

    public TableCounter $bankMoney;
    public PlayerCounter $playerMoney;

    public function __construct(private Table $game, private Db $db = new DefaultDb()) {
        $this->bankMoney = $this->game->counterFactory->createTableCounter('bankmoney');
        $this->playerMoney = $this->game->counterFactory->createPlayerCounter('playermoney');
    }

    /** @param $player_ids int[] */
    public function createNewGame(array $player_ids): void {
        $player_count = count($player_ids);
        $tilepool = Tile::createInitialPool($player_count);
		$stock = Stock::create($tilepool);

        // Now add "tiles" that should not be part of stock
        // NOTE: we hardcode insertion of the "block", excluding it from the pool
        // FIXME: (re)-evaluate this choice of distinguished tile ID, and also reusing the same tile.
        $block = new Tile(10000, TileType::BLOCK);
        $tilepool[] = $block;
        $tilepool[] = Tile::empty();

		$make = function (Tile $tile): string {
			$id = $tile->id;
			$ty = $tile->type->value;
			return "($id,'$ty')";
		};

        // Insert overall tile -> type map.
        $this->db->execute("INSERT INTO tiles (id, type) VALUES "
                           . implode(',', array_map($make, $tilepool)));

        // Insert stock piles
		$this->db->execute("INSERT INTO primary_stock (tile_id) VALUES "
                           . implode(',', array_map(fn ($i) => "($i)", $stock->primaryIds())));
		$this->db->execute("INSERT INTO endgame_stock (tile_id) VALUES "
                           . implode(',', array_map(fn ($i) => "($i)", $stock->endgameIds())));

        // Trucks
        $trucks = [];
        if ($player_count == 2) {
            $trucks[] = new Truck(1);
            $trucks[] = new Truck(2, [Tile::empty(), Tile::empty(), $block]);
            $trucks[] = new Truck(3, [Tile::empty(), $block, $block]);
        }
        else {
			for ($x = 1; $x <= $player_count; $x++) {
                $trucks[] = new Truck($x);
            }
        }
        $values = [];
        foreach ($trucks as $truck) {
            $ts = array_map(fn (Tile $t): string => "$t->id", $truck->getAllTiles());
            $values[] = sprintf("(%d, %s, %s, %s)", $truck->id, $ts[1], $ts[2], $ts[3]);
        }
        $this->db->execute("INSERT INTO trucks (id, tile_id1, tile_id2, tile_id3) VALUES "
                           . implode(',', $values));


        // Enclosures - no DB init needed, as missing contents is interpreted as empty.

        // Extra player info
        $this->db->execute("UPDATE player SET money = 2");

        $this->game->globals->set('drawn', 0);
        $this->bankMoney->initDb(30);
        $this->playerMoney->initDb($player_ids);
        foreach ($this->getPlayers() as $player) {
            $this->giveMoneyFromBank($player, 2);
        }
    }

    private function payMoney(Player $player, int $amount): void {
        $player->payMoney($amount);
        $this->bankMoney->inc($amount);
    }

    private function giveMoneyFromBank(Player $player, int $amount): void {
        $bank = $this->bankMoney->get();
        if ($bank < $amount) {
            $amount = $bank;
        }
        $player->receiveMoney($amount);
        $this->playerMoney->inc($player->id, $amount);
        $this->bankMoney->inc(-$amount);
    }

    private function validatePlayer(Player $player): void {
        if ($player !== $this->getPlayer($player->id)) {
            throw new ModelException("Got unknown player");
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
        throw new ModelException("attempt to retrieve unknown player $id");
    }

    /** @var Player[] */
    private ?array $_players = [];

    /** @returns Player[] */
    public function getPlayers(): array {
        if ($this->_players == null) {
            $this->_players = [];
            $data = $this->db->getObjectList("SELECT player_id, player_no, money, t.id AS taken_by
                                              FROM player AS p
                                              LEFT OUTER JOIN trucks AS t
                                              ON p.player_id = t.taken_by");
            $numPlayers = count($data);
            $potentialExtensions = $numPlayers == 2 ? 2 : 1;
            foreach ($data as $row) {
                $id = intval($row["player_id"]);
                $taken = intval($row["taken_by"]);
                $this->_players[$id] = new Player($id, intval($row["player_no"]), $this->playerMoney->get($id), 2, 0, $taken);
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
                    return new Tile(intval($row["tile_id{$pos}"]), TileType::from($row["type{$pos}"]));
                };
                return new Truck(intval($row['id']), [$tile(1), $tile(2), $tile(3)], intval($row["taken_by"]));
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
        throw new ModelException("No truck $truck_id found");
    }

    private function updateTruck(Truck $truck): void {
        $tiles = $truck->getAllTiles();
        $this->db->execute("UPDATE trucks
                            SET tile_id1=" . $tiles[1]->id . ", "
                             . "tile_id2=" . $tiles[2]->id . ", "
                             . "tile_id3=" . $tiles[3]->id . ", "
                             . "taken_by=" . $truck->taken_by
                             . " WHERE id = {$truck->id}");
    }

    public function placeDrawnTileOnTruck(int $truck_id, int $pos): Tile {
        $stock = $this->getStock();
        if ($stock->drawn == null) {
            throw new ModelException("No tile drawn");
        }
        $truck = $this->getTruck($truck_id);
        $tile = $stock->removeDrawnTile();

        $truck->placeTileAt($tile, $pos);
        $this->updateStock();
        $this->updateTruck($truck);

        return $tile;
    }

    public function spacesOnTrucks() : int {
        return array_sum(
            array_map(
                function (Truck $t): int { return $t->freeSpaces(); },
                array_filter($this->getTrucks(), fn ($t) => ! $t->taken_by)
            )
        );
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
        /*
        from frontend:
                      + enclosure(1, 6)
                      + enclosure(2, 6)
                      + enclosure(3, 7)
                      + enclosure(4, 6)
                      + (this.twoPlayer ? enclosure(5, 6) : '') + `
                      */
        /** @var Enclosure[] */
        // FIXME: find some way to have this auto-sync with the FE
        //   bonus points: also with the CSS!
        $encl = [
            1 => new Enclosure(1, 5, 1),
            2 => new Enclosure(2, 4, 2),
            3 => new Enclosure(3, 6, 1),
            4 => new Enclosure(4, 5, 1),
        ];
        if (count($this->getPlayers()) == 2) {
            $encl[5] = new Enclosure(5, 5, 1);
        }

        $rows = $this->db->getObjectList("SELECT e.enclosure_id, e.pos, e.tile_id, t.type
                                          FROM enclosure_contents e
                                          INNER JOIN tiles t
                                          ON e.tile_id = t.id
                                          WHERE e.player_id = $player_id
                                          ORDER BY e.enclosure_id, e.pos");
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

    private function updateEnclosure(Enclosure $enclosure): void {
        $player_id = $this->getActivePlayer()->id;
        $this->db->execute("DELETE FROM enclosure_contents WHERE player_id=$player_id AND enclosure_id=$enclosure->id");
        $values = [];
        foreach ($enclosure->allContents() as $pos => $t) {
            $values[] = "($player_id, $enclosure->id, $pos, $t->id)";
        }
        $sql = 'INSERT INTO enclosure_contents (player_id, enclosure_id, pos, tile_id) VALUES '
             . implode(',', $values);
        $this->db->execute($sql);
    }

    /** @return int the position in the enclosure it was plased in */
    public function placeTileInZoo(int $truck_id, int $truck_pos, int $enclosure_id): int {
        $truck = $this->getTruck($truck_id);
        $encl = $this->getEnclosuresForPlayer($this->getActivePlayer()->id)[$enclosure_id];
        $tile = $truck->removeTileAt($truck_pos);
        $pos = $encl->placeTile($tile);
        $this->updateTruck($truck);
        $this->updateEnclosure($encl);
        return $pos;
    }

    /**
     * @param Placement[] $placements
     *
     * @return Placement[]
    */
    public function placeTilesInZooAndTakeTruck(int $player_id, int $truck_id, array $placements): array {
        $truck_id = $placements[0]->truck_id;
        foreach ($placements as $placement) {
            $epos = $this->placeTileInZoo($placement->truck_id, $placement->truck_pos, $placement->enclosure_id);
            if ($epos <> $placement->enclosure_pos) {
                throw new ModelException("put {$truck_id}:{$placement->truck_pos} into {$placement->enclosure_id}:{$placement->enclosure_pos} but it went in {$epos}");
                $placement->enclosure_pos = $epos;
            }
        }
        $player = $this->getPlayer($player_id);
        $player->takeTruck($truck_id);

        $truck = $this->getTruck($truck_id);
        $amt = $truck->takeCoins();
        $player->receiveMoney($amt);
        $this->playerMoney->inc($player->id, $amt);

        $truck->taken_by = $player_id;
        $this->updateTruck($truck);
        // $this->updatePlayer($player);

        foreach ($this->getEnclosuresForPlayer($player_id) as $enclosure) {
            $this->updateEnclosure($enclosure);
        }
        return $placements;
    }

    public function getPossiblePlacements(int $player_id, int $truck_id): PossiblePlacement {
        return PossiblePlacement::possiblePlacementFor($this->getTruck($truck_id), $this->getEnclosuresForPlayer($player_id));
    }

    /** @return int[] IDs of trucks returning to depot */
    public function prepareNextTurn(): array {
        $returning_truck_ids = [];
        foreach ($this->getPlayers() as $player_id => $player) {
            $tid = $player->truck_taken;
            if ($tid > 0) {
                $returning_truck_ids[] = $tid;
                $pid = $this->getTruck($tid)->returnTruck();
                if ($pid <> $player_id) {
                    throw new ModelException("Truck {$tid} owned by {$pid} but {$player_id} owned it too");
                }
            }
        }
        foreach ($this->getTrucks() as $truck) {
            if ($truck->taken_by > 0) {
                throw new ModelException("Truck {$truck->id} taken by {$truck->taken_by} but that player has no truck");
            }
            foreach ($truck->getAllTiles() as $tile) {
                $truck->dumpTiles();
            }
        }
        foreach ($this->getTrucks() as $truck) {
            $this->updateTruck($truck);
        }
        return $returning_truck_ids;
    }
}

/*
echo "hi\n";
$model = new Model( null, new TestDb() );
$model->createNewGame(2);
*/
