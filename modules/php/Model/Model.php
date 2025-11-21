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

    public function __construct(private Table $game, private int $player_id, private PersistentStore $ps = new PersistentStore((new DefaultDb()))) { }

    /** @param $player_ids int[] */
    public static function createNewGame(array $player_ids, PersistentStore $ps = new PersistentStore()): void {
        $player_count = count($player_ids);
        $tilepool = Tile::createInitialPool($player_count);
		$stock = Stock::create($tilepool);

        // Now add "tiles" that should not be part of stock
        // NOTE: we hardcode insertion of the "block", excluding it from the pool
        // FIXME: (re)-evaluate this choice of distinguished tile ID, and also reusing the same tile.
        $block = new Tile(10000, TileType::BLOCK);
        $tilepool[] = $block;
        $tilepool[] = Tile::empty();

        $ps->insertTiles($tilepool);
        $ps->insertStock($stock);
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
        $ps->insertTrucks($trucks);

        /** @var Enclosure[] */
        $encl = array_map(fn ($eid) => Model::makeEnclosure($eid), [ 0, 1, 2, 3 ] );
        foreach ($player_ids as $player_id) {
            $ps->insertEnclosures($player_id, $encl);
        }

        $ps->updateBankMoney(30 - 2 * $player_count);
        $available = $player_count == 2 ? 2 : 1;
        foreach ($player_ids as $player_id) {
            $ps->updatePlayer(new Player($player_id, 0, 2, $available, 0, 0));
        }
    }

    private function getPlayer(int $id): Player {
        $players = $this->getPlayers();
        if (isset($players[$id])) {
            return $players[$id];
        }
        throw new ModelException("attempt to retrieve unknown player $id");
    }

    /** @var Player[] */
    private ?array $_players = null;

    /** @return Player[] */
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

    public function drawTile(): Stock {
        $stock = $this->getStock();
        $stock->drawTile();
        $this->ps->updateStock($stock);
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

    public function placeDrawnTileOnTruck(int $truck_id, int $pos): Tile {
        $stock = $this->getStock();
        if ($stock->drawn->isEmpty()) {
            throw new ModelException("No tile drawn");
        }
        $truck = $this->getTruck($truck_id);
        $tile = $stock->removeDrawnTile();
        $truck->placeTileAt($tile, $pos);

        $this->ps->updateStock($stock);
        $this->ps->updateTruck($truck);

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

    /** @return int the position in the enclosure it was plased in */
    private function placeTileInZoo(int $truck_id, int $truck_pos, int $enclosure_id): int {
        $truck = $this->getTruck($truck_id);
        $encl = $this->getEnclosuresForPlayer($this->player_id)[$enclosure_id];
        $tile = $truck->removeTileAt($truck_pos);
        $pos = $encl->placeTile($tile);
        $this->ps->updateTruck($truck);
        $this->ps->updateEnclosure($this->player_id, $encl);
        return $pos;
    }

    /**
     * @param Placement[] $placements
     *
     * @return Placement[]
    */
    public function placeTilesInZooAndTakeTruck(int $truck_id, array $placements): array {
        foreach ($placements as $placement) {
            $epos = $this->placeTileInZoo($placement->truck_id, $placement->truck_pos, $placement->enclosure_id);
            if ($epos <> $placement->enclosure_pos) {
                throw new ModelException("put {$truck_id}:{$placement->truck_pos} into {$placement->enclosure_id}:{$placement->enclosure_pos} but it went in {$epos}");
                $placement->enclosure_pos = $epos;
            }
        }
        $player = $this->getPlayer($this->player_id);
        $player->takeTruck($truck_id);

        $truck = $this->getTruck($truck_id);
        $amt = $truck->takeCoins();

        $player->receiveMoney($amt);
        $this->ps->updatePlayer($player);

        $truck->taken_by = $this->player_id;
        $this->ps->updateTruck($truck);

        foreach ($this->getEnclosuresForPlayer($this->player_id) as $enclosure) {
        $this->ps->updateEnclosure($this->player_id, $enclosure);
        }
        return $placements;
    }

    private function getPossiblePlacements(Truck $truck): PossiblePlacement {
        return PossiblePlacement::possiblePlacementFor(
            $truck, $this->getEnclosuresForPlayer($this->player_id)
        );
    }

    public function getTrucksWithPossiblePlacements(): array {
		$player = $this->getPlayer($this->player_id);

		$trucks_available = [];
		if (!$player->truck_taken) {
			foreach ($this->getTrucks() as $truck) {
				if ($truck->canBeTaken()) {
					$trucks_available[$truck->id] = $this->getPossiblePlacements($truck);
				}
			}
		}
        return $trucks_available;
    }

    /** @return array<int,array<int>> keyed by truck ID; if null, truck is returned, otherwise it's the positions dumped. */
    public function prepareNextTurn(): array {
        $players = $this->getPlayers();
        $result = [];
        foreach ($this->getTrucks() as $truck) {
            $pid = $truck->taken_by;
            if ($pid > 0) {
                $truck->returnTruck();
                $taken = $players[$pid]->returnTruck();
                if ($taken != $truck->id) {
                    throw new ModelException("Truck {$truck->id} taken by {$truck->taken_by} but that player has no truck");
                }
                $result[$truck->id] = null;
            }
            else {
                $result[$truck->id] = $truck->dumpTiles();
            }
        }
        foreach ($this->getTrucks() as $truck) {
            $this->ps->updateTruck($truck);
        }
        return $result;
    }

    public function canPurchaseExtension(): bool {
        return $this->getPlayer($this->player_id)->canPurchaseExtension();
    }

    private static function makeEnclosure(int $id): Enclosure {
        /* FIXME: find some way to have this auto-sync with the FE?
           bonus points: also sync with the CSS!
          from frontend:
                      + enclosure(0, 20)
                      + enclosure(1, 6)
                      + enclosure(2, 6)
                      + enclosure(3, 7)
                      + enclosure(4, 6)
                      + (this.twoPlayer ? enclosure(5, 6) : '') + `
        */

        $e = match ($id) {
            0 => Enclosure::barn(),
            1 => Enclosure::create(1, 5, 1),
            2 => Enclosure::create(2, 4, 2),
            3 => Enclosure::create(3, 6, 1),
            4 => Enclosure::create(4, 5, 1),
            5 => Enclosure::create(5, 5, 1),
            default => throw new ModelException("Unexpected enclosure ID: {$id}"),
        };
        if ($id != $e->id) {
            throw new ModelException("Created enclosure for {$id} has id {$e->id}");
        }
        return $e;
    }

    public function purchaseExtension(): Player {
        $player = $this->getPlayer($this->player_id);
        $eid = $player->purchaseExtension() + 3;
        $e = Model::makeEnclosure($eid);
        $this->ps->updatePlayer($player);
        $this->ps->insertEnclosures($this->player_id, [$e]);
        return $player;
    }

    public function canDraw(): bool {
        return $this->getStock()->drawn->isEmpty() && $this->spacesOnTrucks() > 0;
    }

    public function canDiscard(): bool {
		$enclosures = $this->getEnclosuresForPlayer($this->player_id);
		$barnContents = array_filter($enclosures[0]->allContents(), fn ($t) => !$t->isEmpty());
        return count($barnContents) > 0 && $this->getPlayer($this->player_id)->money >= 2;
    }

    public function canMoveTile(): bool {
        return $this->getPlayer($this->player_id)->money >= 1;
    }

    /** return int[] positions in barn that are discardable */
    public function getDiscardbleBarnPos(): array {
        if ($this->getPlayer($this->player_id)->money < 1) {
            return [];
        }
        $barn = $this->getEnclosuresForPlayer($this->player_id)[0];
        return array_keys(array_filter($barn->allContents(), fn ($t) => !$t->isEmpty()));
    }

    public function discardBarnTile(int $pos): Tile {
        $barn = $this->getEnclosuresForPlayer($this->player_id)[0];
        $tile = $barn->takeTileAt($pos);
        $this->ps->updateEnclosure($this->player_id, $barn);
        return $tile;
    }
}
