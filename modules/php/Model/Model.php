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

    public function __construct(private Table $game, private PersistentStore $ps = new PersistentStore((new DefaultDb()))) { }

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

        $this->ps->insertTiles($tilepool);
        $this->ps->insertStock($stock);
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
        $this->ps->insertTrucks($trucks);

        /* FIXME: find some way to have this auto-sync with the FE?
           bonus points: also sync with the CSS!
          from frontend:
                      + enclosure(1, 6)
                      + enclosure(2, 6)
                      + enclosure(3, 7)
                      + enclosure(4, 6)
                      + (this.twoPlayer ? enclosure(5, 6) : '') + `
        */

        /** @var Enclosure[] */
        $encl = [
            1 => new Enclosure(1, 5, 1),
            2 => new Enclosure(2, 4, 2),
            3 => new Enclosure(3, 6, 1),
        ];
        // FIXME: these need to get inserted only when purchased.
        //     4 => new Enclosure(4, 5, 1),
        // ];
        // if ($player_count == 2) {
        //     $encl[5] = new Enclosure(5, 5, 1);
        // }
        foreach ($player_ids as $player_id) {
            $this->ps->insertEnclosures($player_id, $encl);
        }

        $this->ps->updateBankMoney(30 - 2 * $player_count);
        $available = $player_count == 2 ? 2 : 1;
        foreach ($player_ids as $player_id) {
            $this->ps->updatePlayer(new Player($player_id, 0, 2, $available, 0, 0));
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
            $this->_players = $this->ps->retrievePlayers();
        }
        return $this->_players;
    }

    private ?Stock $_stock = null;

    public function getStock(): Stock {
        if ($this->_stock == null) {
            $this->_stock = $this->ps->retrieveStock();
        }
        return $this->_stock;
    }

    private function updateStock(): void {
        $stock = $this->_stock;
        if ($stock == null) {
            throw new ModelException("Stock updaate before retrieval");
        }
        $this->ps->updateStock($this->_stock);
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
            $this->_trucks = $this->ps->retrieveTrucks();
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
        $this->ps->updateTruck($truck);
    }

    public function placeDrawnTileOnTruck(int $truck_id, int $pos): Tile {
        $stock = $this->getStock();
        if ($stock->drawn->isEmpty()) {
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
        return $this->ps->getEnclosuresForPlayer($player_id);
    }

    private function updateEnclosure(Enclosure $enclosure): void {
        $this->ps->updateEnclosure($this->getActivePlayer()->id, $enclosure);
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
        $this->ps->updatePlayer($player);

        $truck->taken_by = $player_id;
        $this->updateTruck($truck);
        // $this->updatePlayer($player);

        foreach ($this->getEnclosuresForPlayer($player_id) as $enclosure) {
            $this->updateEnclosure($enclosure);
        }
        return $placements;
    }

    public function getPossiblePlacements(int $player_id, int $truck_id): PossiblePlacement {
        return PossiblePlacement::possiblePlacementFor(
            $this->getTruck($truck_id),
            $this->getEnclosuresForPlayer($player_id)
        );
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

    public function purchaseExtension(int $player_id): Player {
        $player = $this->getPlayer($player_id);
        $player->purchaseExtension();
        return $player;
    }
}
