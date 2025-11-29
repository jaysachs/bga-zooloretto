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

        $ps->setBankMoney(30 - 2 * $player_count);
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

    private function spacesOnTrucks() : int {
        return array_sum(
            array_map(
                function (Truck $t): int { return $t->freeSpaces(); },
                array_filter($this->getTrucks(), fn ($t) => ! $t->taken_by)
            )
        );
    }


    /** @var array<int,Enclosure[]> keyed by player_id */
    private $_enclosures = [];

    /**
     * Returns enclosure mapped by enclosure_id.
     *
     * @return Enclosure[]
     */
    public function getEnclosuresForPlayer(int $player_id) : array {
        if (isset($this->_enclosures[$player_id])) {
            return $this->_enclosures[$player_id];
        }
        $encs = $this->ps->getEnclosuresForPlayer($player_id);
        $this->_enclosures[$player_id] = $encs;
        return $encs;
    }

    /**
     * @param Delivery[] $placements
     *
     * @return Delivery[]
    */
    public function placeTilesInZooAndTakeTruck(int $truck_id, array $deliveries): array {
        $encs = $this->getEnclosuresForPlayer($this->player_id);
        $barn = $encs[0];
        foreach ($deliveries as $delivery) {
            $truck = $this->getTruck($truck_id);
            $encl = $encs[$delivery->enclosure_id];
            $tile = $truck->removeTileAt($delivery->truck_pos);
            $pos = $encl->placeTile($tile)->pos;
            if ($pos <> $delivery->enclosure_pos) {
                // FIXME: this exception should be correct but it isn't.
                // throw new ModelException("put {$truck_id}:{$placement->truck_pos} into {$placement->enclosure_id}:{$placement->enclosure_pos} but it went in {$pos}");
                $delivery->enclosure_pos = $pos;
            }
            $offspring = $encl->checkForOffspring($barn);
            // FIXME: check fo completion bonus

            if ($offspring) {
                $this->game->warn("\n\nOffspring for {$encl->id}: {$offspring}\n\n");
                $delivery->offspring = $offspring;
                $this->saveOffspring($offspring);

                // FIXME: return info on new child -- add to return value
            } else {
                $this->game->warn("\n\nNo offspriing for {$encl->id}\n\n");
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

        foreach ($encs as $enclosure) {
            $this->ps->updateEnclosure($this->player_id, $enclosure);
        }
        return $deliveries;
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
                $result[$truck->id] = [];
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

    public function canExpand(): bool {
        return $this->getPlayer($this->player_id)->canExpand();
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

    public function expandZoo(int $pid): Player {
        $this->game->warn("expandZoo {$this->player_id} ({$pid})");
        $player = $this->getPlayer($this->player_id);
        $this->game->warn("player {$player}");

        $eid = $player->addExtension();
        $this->game->warn("eid {$eid} player {$player}");
        $this->pay($player, Cost::EXPAND);
        $e = Model::makeEnclosure($eid);

        $this->ps->insertEnclosures($this->player_id, [$e]);

        return $player;
    }

    public function canDraw(): bool {
        return $this->getStock()->drawn->isEmpty() && $this->spacesOnTrucks() > 0;
    }

    /** return int[] positions in barn that are discardable */
    public function getDiscardbleBarnPos(): array {
        if ($this->getPlayer($this->player_id)->money < 1) {
            return [];
        }
        $barn = $this->getEnclosuresForPlayer($this->player_id)[0];
        return array_keys($barn->nonEmptyContents());
    }

    public function discardBarnTile(int $pos): Tile {
        $player = $this->getPlayer($this->player_id);
        $barn = $this->getEnclosuresForPlayer($this->player_id)[0];

        $this->pay($player, Cost::DISCARD);
        $tile = $barn->takeTileAt($pos);

        $this->ps->updateEnclosure($this->player_id, $barn);

        return $tile;
    }

    /** @return PossibleMove[] */
    public function getPossibleMoves(): array {
        if ($this->ps->retrievePlayers()[$this->player_id]->money < 1) {
            return [];
        }
        $result = [];
        $enclosures = $this->getEnclosuresForPlayer($this->player_id);
        $barn = $enclosures[0];
        array_splice($enclosures, 0, 1);
        // moves a single animal tile from the barn to an empty enclosure space or he
        //  moves any one vending stall tile from it's current location to any eligible space in his zoo

        // animal or stall from barn
        foreach ($barn->nonEmptyContents() as $pos => $tile) {
            $src = new Space($barn->id, $pos);
            /** @var Space[] */
            $dests = [];
            foreach ($enclosures as $enc) {
                $ap = $enc->availablePos($tile->type);
                if ($ap > 0) {
                    $offspring = $enc->checkForOffspring($barn);
                    $childSpace = null;
                    $childTile = null;
                    if ($offspring) {
                        $childTile = $offspring->child;
                        $childSpace = $offspring->childSpace;
                    }
                    $dests[] = new Destination(new Space($enc->id, $ap), $childSpace, $childTile);
                }
            }
            if (count($dests) > 0) {
                $result[] = new PossibleMove($src, $dests);
            }
        }
        // or stall from one (non-barn) enclosure to another
        foreach ($enclosures as $enc) {
            foreach ($enc->nonEmptyContents() as $pos => $tile) {
                /** @var Space[] */
                $dests = [];
                $src = new Space($enc->id, $pos);
                if ($tile->type->isStall()) {
                    foreach ($enclosures as $other) {
                        if ($other <> $enc) {
                            $sp = $other->availablePos($tile->type);
                            if ($sp > 0) {
                                $dests[] = new Destination(new Space($other->id, $sp));
                            }
                        }
                    }
                }
                if (count($dests) > 0) {
                    $result[] = new PossibleMove($src, $dests);
                }
            }
        }
        return $result;
    }

    public function moveTile(Space $src, Space $dest): void {
        $pms = $this->getPossibleMoves();
        $found = false;
        foreach ($pms as $pm) {
            if ($pm->src == $src) {
                foreach ($pm->dests as $d) {
                    if ($d->space == $dest) {
                        $found = true;
                        break 2;
                    }
                }
            }
        }
        if (!$found) {
            throw new ModelException("illegal move {$src} {$dest}");
        }

        $encs = $this->getEnclosuresForPlayer($this->player_id);
        $player = $this->ps->retrievePlayers()[$this->player_id];

        $this->pay($player, Cost::MOVE);
        $srcenc = $encs[$src->enclosure_id];
        $destenc = $encs[$dest->enclosure_id];
        $tile = $srcenc->takeTileAt($src->pos);
        $destenc->placeTile($tile, $dest->pos);

        $offspring = $destenc->checkForOffspring($encs[0]);
        if ($offspring) {
            $this->saveOffspring($offspring);
        }
        // FIXME: then check fo completion bonus

        $this->ps->updateEnclosure($this->player_id, $encs[0]);
        $this->ps->updateEnclosure($this->player_id, $srcenc);
        $this->ps->updateEnclosure($this->player_id, $destenc);
    }

    public function purchaseTile(int $from_player_id, int $barn_pos, Space $target): Tile {
        $player = $this->ps->retrievePlayers()[$this->player_id];
        $src = new Space(0, $barn_pos);

        $found = false;
        foreach ($this->getPurchaseableTiles() as $pt) {
            if ($pt->player_id == $from_player_id && $pt->move->src == $src) {
                foreach ($pt->move->dests as $dest) {
                    if ($dest == $target) {
                        $found = true;
                        break 2;
                    }
                }
            }
        }
        if (!$found) {
            throw new ModelException("Illegal purchase {$from_player_id} {$barn_pos} {$target}");
        }

        $from_player = $this->getPlayer($from_player_id);
        $player->payMoney(Cost::PURCHASE);
        $this->ps->updatePlayer($player);
        $this->ps->incBankMoney(1);
        $from_player->receiveMoney(1);
        $this->ps->updatePlayer($from_player);

        $frombarn = $this->ps->getEnclosuresForPlayer($from_player_id)[0];
        $enc = $this->ps->getEnclosuresForPlayer($this->player_id)[$dest->space->enclosure_id];
        $barn = $this->ps->getEnclosuresForPlayer($this->player_id)[0];
        $tile = $frombarn->takeTileAt($src->pos);
        $enc->placeTile($tile, $dest->space->pos);

        $offspring = $enc->checkForOffspring($barn);
        if ($offspring) {
            $this->saveOffspring($offspring);
            $this->ps->updateEnclosure($this->player_id, $barn);
        }
        // FIXME: then check fo completion bonus

        $this->ps->updateEnclosure($from_player_id, $frombarn);
        $this->ps->updateEnclosure($this->player_id, $enc);
        return $tile;
    }

    /** @return PossibleBuy[] */
    public function getPurchaseableTiles(): array {
        if ($this->getPlayer($this->player_id)->money < 2) {
            return [];
        }
        $enclosures = $this->getEnclosuresForPlayer($this->player_id);
        /** @var PossibleBuy[] */
        $result = [];
        foreach ($this->getPlayers() as $player) {
            if ($player->id == $this->player_id) {
                continue;
            }
            $barn = $this->ps->getEnclosuresForPlayer($player->id)[0];
            foreach ($barn->nonEmptyContents() as $pos => $tile) {
                $dests = [];
                foreach ($enclosures as $enclosure) {
                    $p = $enclosure->availablePos($tile->type);
                    if ($p > 0) {
                        $dests[] = new Destination(new Space($enclosure->id, $p));
                    }
                }
                if (count($dests) > 0) {
                    $result[] = new PossibleBuy($player->id, new PossibleMove(new Space($barn->id, $pos), $dests));
                }
            }
        }
        return $result;
    }

    /** @return PossibleExchange[] */
    public function getPossibleExchanges() : array {
        if ($this->ps->retrievePlayers()[$this->player_id]->money < 1) {
            // can't afford it.
            return [];
        }
        return PossibleExchange::getPossibleExchanges($this->ps->getEnclosuresForPlayer($this->player_id));
    }

    private function saveOffspring(Offspring $offspring): void {
        // new child inserted
        $this->ps->insertTiles([$offspring->child]);
        // update parents, marked reproduced.
        $this->ps->updateTile($offspring->mother);
        $this->ps->updateTile($offspring->father);
    }

    /** @return TileType[]  length 2 of the form [srctype, desttype] */
    public function exchange(PositionSet $src, PositionSet $dest): array {
        $found = false;
        foreach ($this->getPossibleExchanges() as $px) {
            if ($src == $px->src && $dest == $px->dest) {
                $found = true;
                break;
            }
        }
        if (! $found) {
            throw new ModelException("Illegal exchange of {$src} and {$dest}");
        }

        $player = $this->ps->retrievePlayers()[$this->player_id];
        $this->pay($player, Cost::EXCHANGE);

        $encs = $this->ps->getEnclosuresForPlayer($this->player_id);
        $se = $encs[$src->enclosure_id];
        $de = $encs[$dest->enclosure_id];

        $stype = TileType::EMPTY;
        $dtype = TileType::EMPTY;
        for ($p = 0; $p < count($src->positions); $p++) {
            $srctile = Tile::empty();
            $desttile = Tile::empty();
            if (!$se->tileAt($src->positions[$p])->isEmpty()) {
                $srctile = $se->takeTileAt($src->positions[$p]);
            }
            if (!$de->tileAt($dest->positions[$p])->isEmpty()) {
                $desttile = $de->takeTileAt($dest->positions[$p]);
            }
            if (!$desttile->isEmpty()) {
                $dtype = $desttile->type;
                $se->placeTile($desttile, $src->positions[$p]);
            }
            if (!$srctile->isEmpty()) {
                $stype = $srctile->type;
                $de->placeTile($srctile, $dest->positions[$p]);
            }
        }

        $barn = $encs[0];
        $offspring = $se->checkForOffspring($barn);
        if ($offspring) {
            $this->saveOffspring($offspring);
        }
        $offspring = $de->checkForOffspring($barn);
        if ($offspring) {
            $this->saveOffspring($offspring);
        }

        // no check fo completion bonus in enclosures

        if ($barn !== $se && $barn !== $de) {
            $this->ps->updateEnclosure($this->player_id, $barn);
        }
        $this->ps->updateEnclosure($this->player_id, $se);
        $this->ps->updateEnclosure($this->player_id, $de);

        return [$stype, $dtype];
    }

    private function pay(Player $player, Cost $cost) : void {
        $player->payMoney($cost);
        $this->ps->updatePlayer($player);
        $this->ps->incBankMoney($cost->amount());
    }
}
