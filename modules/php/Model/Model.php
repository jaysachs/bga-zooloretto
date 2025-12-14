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
    public function __construct(private int $player_id, private PersistentStore $ps = new PersistentStore((new DefaultDb()))) {
    }

    /** @param int[] $player_ids */
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
        $ps->setBankMoney(30 - 2 * $player_count);

        foreach ($player_ids as $player_id) {
            $player = new Player($player_id, 2, $player_count, 0, 0);
            $ps->updatePlayer($player);
        }
    }

    public function getActivePlayer(): Player {
        return $this->getPlayer($this->player_id);
    }

    public function getPlayer(int $id): Player {
        $players = $this->getAllPlayers();
        if (isset($players[$id])) {
            return $players[$id];
        }
        throw new ModelException("attempt to retrieve unknown player $id");
    }

    /** @var Player[] */
    private ?array $_players = null;

    /** @return Player[] */
    public function getAllPlayers(): array {
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

    /** @var null|list<Truck> */
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
                array_filter($this->getTrucks(), fn ($t) => $t->taken_by === null)
            )
        );
    }

    /** @var array<int,list<Enclosure>> keyed by player_id */
    private $_enclosures = [];

    /**
     * Returns enclosure mapped by enclosure_id.
     *
     * @return list<Enclosure>
     */
    public function getEnclosuresForPlayer(?int $player_id = 0) : array {
        if ($player_id === null || $player_id === 0) {
            $player_id = $this->player_id;
        }
        if (isset($this->_enclosures[$player_id])) {
            return $this->_enclosures[$player_id];
        }
        $player = $this->getPlayer($player_id);
        $encs = $this->ps->populateEnclosures($player_id, Enclosure::forPlayer($player));
        $this->_enclosures[$player_id] = $encs;
        return $encs;
    }

    /**
     * @param Space[] $spaces keyed by truck position
     *
     * @return Delivery[]
    */
    public function takeTruckAndPlaceTiles(int $truck_id, array $spaces): array {
        $truck = $this->getTruck($truck_id);
        if ($truck->taken_by > 0) {
            throw new ModelException("Truck {$truck_id} already taken by player {$truck->taken_by}");
        }
        $encs = $this->getEnclosuresForPlayer();
        $barn = $encs[0];
        $toUpdate = [];
        $player = $this->getActivePlayer();
        $deliveries = [];

        foreach ($truck->getAllTiles() as $truck_pos => $tile) {
            if ($tile->isEmpty() || $tile->type->isBlock()) {
                continue;
            }

            if (!isset($spaces[$truck_pos])) {
                if ($tile->type != TileType::COIN) {
                    throw new ModelException("Unplaced non-coin {$tile->type->value} at position {$truck_pos} in truck {$truck_id}");
                }
                $deliveries[] = new Delivery($truck_pos, $tile);
                continue;
            }

            $space = $spaces[$truck_pos];
            $encl = $encs[$space->enclosure_id];
            $toUpdate[] = $encl;
            $tile = $truck->removeTileAt($truck_pos);

            $placement = $encl->placeTile($tile);
            if ($placement->completedEnclosure) {
                $this->payPlayer($player, $encl->coin_bonus);
            }
            $pos = $placement->space->pos;
            if ($pos <> $space->pos) {
                // FIXME: this exception should be correct but it isn't.
                // throw new ModelException("put {$truck_id}:{$placement->truck_pos} into {$placement->enclosure_id}:{$placement->enclosure_pos} but it went in {$pos}");
                $space->pos = $pos;
            }
            $offspring = $encl->checkForOffspring($barn);

            $deliveries[] = new Delivery($truck_pos, $tile, new Destination($space, $offspring));
            if ($offspring) {
                $this->saveOffspring($offspring);
                // FIXME: check fo completion bonus

                // FIXME: return info on new child -- add to return value
            }
        }
        $player = $this->getActivePlayer();
        $player->takeTruck($truck_id);

        $truck = $this->getTruck($truck_id);
        $amt = $truck->takeCoins();

        $player->receiveMoney($amt);
        $this->ps->updatePlayer($player);

        $truck->taken_by = $this->player_id;
        $this->ps->updateTruck($truck);

        $this->ps->updateEnclosures($this->player_id, $toUpdate);

        return $deliveries;
    }

    private function getPossiblePlacements(Truck $truck): PossiblePlacement {
        return PossiblePlacement::possiblePlacementFor(
            $this->player_id,
            $truck,
            $this->getEnclosuresForPlayer()
        );
    }

    /** @return AvailableTruck[] */
    public function getAvailableTrucks(): array {
		$player = $this->getActivePlayer();

		$trucks_available = [];
		if (!$player->truck_taken) {
			foreach ($this->getTrucks() as $truck) {
				if ($truck->canBeTaken()) {
					$trucks_available[] = new AvailableTruck($player->id, $truck->id, $this->getPossiblePlacements($truck), $truck->coinPositions());
				}
			}
		}
        return $trucks_available;
    }

    /** @return array{truck_ids: list<int>, dumpedTiles: list<Tile>} */
    public function prepareNextRound(): array {
        $players = $this->getAllPlayers();
        /** @var list<int> */
        $truck_ids = [];
        /** @var list<Tile>  */
        $dumped = [];
        foreach ($this->getTrucks() as $truck) {
            $pid = $truck->taken_by;
            if ($pid > 0) {
                $truck->returnTruck();
                $taken = $players[$pid]->returnTruck();
                if ($taken != $truck->id) {
                    throw new ModelException("Truck {$truck->id} taken by {$truck->taken_by} but that player has no truck");
                }
                $truck_ids[] = $truck->id;
            }
            else {
                $dumped = array_merge($dumped, $truck->dumpTiles());
            }
        }
        foreach ($this->getTrucks() as $truck) {
            $this->ps->updateTruck($truck);
        }
        return [
            'truck_ids' => $truck_ids,
            'dumpedTiles' => $dumped,
        ];
    }

    public function canExpand(): bool {
        return $this->getActivePlayer()->canExpand();
    }

    public function expandZoo(): Player {
        $player = $this->getActivePlayer();

        $player->addExtension();
        $this->chargePlayer($player, Cost::EXPAND);
        $this->ps->updatePlayer($player);

        // $enc = Enclosure::extension($player->purchased_extensions);
        // clear cache of enclosures per player
        unset($this->_enclosures[$this->player_id]);

        return $player;
    }

    public function canDraw(): bool {
        return $this->getStock()->drawn->isEmpty() && $this->spacesOnTrucks() > 0;
    }

    /** @return Destination[] positions in barn that are discardable */
    public function getDiscardables(): array {
        if (! $this->getActivePlayer()->canAfford(Cost::DISCARD)) {
            return [];
        }
        $barn = $this->getEnclosuresForPlayer()[0];
        return array_map(
            fn ($p) => new Destination(new Space(0, $p), null, Moneys::costPlayerDelta($this->player_id, Cost::DISCARD)),
            array_keys($barn->nonEmptyContents()));
    }

    public function discardBarnTile(int $pos): Tile {
        $player = $this->getActivePlayer();
        $barn = $this->getEnclosuresForPlayer()[0];

        $this->chargePlayer($player, Cost::DISCARD);
        $tile = $barn->takeTileAt($pos);

        $this->ps->updateEnclosures($this->player_id, [$barn]);

        return $tile;
    }

    private function makePossibleDest(Enclosure $enc, Tile $tile, Enclosure $buyer_barn): Destination | null {
        $ap = $enc->availablePos($tile->type);
        if ($ap > 0) {
            $enc = $enc->clone();
            $buyer_barn = $buyer_barn->clone();
            $pl = $enc->placeTile($tile, $ap);
            $offspring = $enc->checkForOffspring($buyer_barn);
            $moneyDelta = null;
            if ($pl->completedEnclosure || ($offspring && $offspring->enclosureCompleted)) {
                $moneyDelta = Moneys::chargePlayerDelta($this->player_id, -$enc->coin_bonus);
            }
            return new Destination(new Space($enc->id, $ap), $offspring, $moneyDelta);
        }
        return null;
    }

    /** @return list<PossibleMove> */
    public function getPossibleMoves(): array {
        if (! $this->getActivePlayer()->canAfford(Cost::MOVE)) {
            return [];
        }
        $moneyDelta = Moneys::costPlayerDelta($this->player_id, Cost::MOVE);
        $result = [];
        $enclosures = $this->getEnclosuresForPlayer();
        $barn = $enclosures[0];
        array_splice($enclosures, 0, 1);
        // moves a single animal tile from the barn to an empty enclosure space or he
        //  moves any one vending stall tile from it's current location to any eligible space in his zoo

        // animal or stall from barn
        foreach ($barn->nonEmptyContents() as $pos => $tile) {
            $src = new Space($barn->id, $pos);
            /** @var Destination[] */
            $dests = [];
            foreach ($enclosures as $enc) {
                $dest = $this->makePossibleDest($enc, $tile, $barn);
                if ($dest) {
                    $dests[] = $dest;
                }
            }
            if (count($dests) > 0) {
                $result[] = new PossibleMove($src, $dests, $moneyDelta);
            }
        }
        // or stall from one (non-barn) enclosure to another
        foreach ($enclosures as $enc) {
            foreach ($enc->nonEmptyContents() as $pos => $tile) {
                /** @var Destination[] */
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
                    $result[] = new PossibleMove($src, $dests, $moneyDelta);
                }
            }
        }
        return $result;
    }

    /** @return array{tile: Tile, offspring: Offspring|null, enclosureBonus: int|null} */
    public function moveTile(Space $src, Space $dest): array {
        $found = false;
        foreach ($this->getPossibleMoves() as $pm) {
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

        $encs = $this->getEnclosuresForPlayer();
        $player = $this->getActivePlayer();

        $this->chargePlayer($player, Cost::MOVE);
        $srcenc = $encs[$src->enclosure_id];
        $destenc = $encs[$dest->enclosure_id];
        $tile = $srcenc->takeTileAt($src->pos);
        $placement = $destenc->placeTile($tile, $dest->pos);
        $amt = null;
        if ($placement->completedEnclosure) {
            $amt = $this->payPlayer($player, $destenc->coin_bonus);
        }

        $offspring = $destenc->checkForOffspring($encs[0]);
        if ($offspring) {
            // FIXME: then check fo completion bonus
            $this->saveOffspring($offspring);
        }

        $this->ps->updateEnclosures($this->player_id, $encs);
        return [ 'tile' => $tile, 'offspring' => $offspring, 'enclosureBonus' => $amt ];
    }

    /** @return array{tiles: list<PlacedTile>, offspring: Offspring | null, enclosureBonus: int|null} */
    public function purchaseTile(int $seller_player_id, int $barn_pos, Space $target): array {
        $player = $this->getActivePlayer();
        $src = new Space(0, $barn_pos);

        $found = null;
        foreach ($this->getPurchaseableTiles() as $pt) {
            if ($pt->from_player_id == $seller_player_id && $pt->move->src == $src) {
                foreach ($pt->move->dests as $dest) {
                    if ($dest->space == $target) {
                        $found = $dest;
                        break 2;
                    }
                }
            }
        }
        if (!$found) {
            throw new ModelException("Illegal purchase {$seller_player_id} {$barn_pos} {$target}");
        }

        $result = [
            'tiles' => [],
            'offspring' => null,
            'enclosureBonus' => null,
        ];
        $seller = $this->getPlayer($seller_player_id);
        $player->payMoney(Cost::PURCHASE);
        $this->ps->updatePlayer($player);
        $this->incBankMoney(1);
        $seller->receiveMoney(1);
        $this->ps->updatePlayer($seller);
        $seller_barn = $this->getEnclosuresForPlayer($seller_player_id)[0];

        $encs = $this->getEnclosuresForPlayer();
        $enc = $encs[$target->enclosure_id];
        $buyer_barn = $encs[0];

        $tile = $seller_barn->takeTileAt($src->pos);
        $placement = $enc->placeTile($tile, $target->pos);
        $result['tiles'][] = new PlacedTile($tile, $placement->space);
        $enclosureCompleted = $placement->completedEnclosure;

        $offspring = $enc->checkForOffspring($buyer_barn);
        $toUpdate = [$enc];
        if ($offspring) {
            $this->saveOffspring($offspring);
            $te = $offspring->child->space->enclosure_id;
            if ($te <> $enc->id) {
                $toUpdate[] = $encs[$te];
            }
            if ($offspring->enclosureCompleted) {
                $enclosureCompleted = true;
            }
            $result['offspring'] = $offspring;
            $result['tiles'][] = $offspring->child;
        }
        if ($enclosureCompleted) {
            $result['enclosureBonus'] = $this->payPlayer($player, $enc->coin_bonus);
        }

        // Update the selling player first, to avoid violating uniqueness constraint in DB.
        $this->ps->updateEnclosures($seller_player_id, [$seller_barn]);
        $this->ps->updateEnclosures($this->player_id, $toUpdate);
        return $result;
    }

    /** @return PossibleBuy[] */
    public function getPurchaseableTiles(): array {
        if (! $this->getActivePlayer()->canAfford(Cost::PURCHASE)) {
            return [];
        }
        // FIXME: We need to gather up all the Buys into an object, and attach
        //   a MoneyDelta there for the purchase price.
        $enclosures = $this->getEnclosuresForPlayer();
        /** @var PossibleBuy[] */
        $result = [];
        foreach ($this->getAllPlayers() as $player) {
            if ($player->id == $this->player_id) {
                continue;
            }
            $seller_barn = $this->getEnclosuresForPlayer($player->id)[0];
            foreach ($seller_barn->nonEmptyContents() as $pos => $tile) {
                $dests = [];
                foreach ($enclosures as $enc) {
                    $dest = $this->makePossibleDest($enc, $tile, $enclosures[0]);
                    if ($dest) {
                        $dests[] = $dest;
                    }
                }
                if (count($dests) > 0) {
                    $delta = new Moneys(1, [ $player->id => 1, $this->player_id => -Cost::PURCHASE->amount() ]);
                    $result[] = new PossibleBuy($player->id, $delta, new PossibleMove(new Space($seller_barn->id, $pos), $dests, new Moneys(0)));
                }
            }
        }
        return $result;
    }

    /** @return list<PossibleExchange> */
    public function getPossibleExchanges() : array {
        if (! $this->getActivePlayer()->canAfford(Cost::EXCHANGE)) {
            // can't afford it.
            return [];
        }
        return PossibleExchange::getPossibleExchanges(
            $this->getEnclosuresForPlayer(),
            Moneys::costPlayerDelta($this->player_id, Cost::EXCHANGE));
    }

    private function saveOffspring(Offspring $offspring): void {
        // new child inserted
        $this->ps->insertTiles([$offspring->child->tile]);
        // update parents, marked reproduced.
        $this->ps->updateTile($offspring->mother);
        $this->ps->updateTile($offspring->father);
    }

    public function exchange(PossibleExchange $ex): CompletedExchange {
        $found = false;
        $pxs = $this->getPossibleExchanges();
        foreach ($pxs as $px) {
            if ($ex->matches($px)) {
                $found = true;
                break;
            }
        }
        if (! $found) {
            throw new ModelException("Illegal exchange of {$ex}");
        }

        $player = $this->getActivePlayer();
        $this->chargePlayer($player, Cost::EXCHANGE);

        $encs = $this->getEnclosuresForPlayer();
        $se = $encs[$ex->src_enclosure_id];
        $de = $encs[$ex->dest_enclosure_id];

        /** @var list<PlacedTile> */
        $placedTiles = [];

        $stype = TileType::EMPTY;
        $dtype = TileType::EMPTY;
        for ($p = 0; $p < count($ex->src_positions); $p++) {
            $srctile = Tile::empty();
            $desttile = Tile::empty();
            if (!$se->tileAt($ex->src_positions[$p])->isEmpty()) {
                $srctile = $se->takeTileAt($ex->src_positions[$p]);
            }
            if (!$de->tileAt($ex->dest_positions[$p])->isEmpty()) {
                $desttile = $de->takeTileAt($ex->dest_positions[$p]);
            }
            if (!$desttile->isEmpty()) {
                $dtype = $desttile->type->canonicalType();
                $placement = $se->placeTile($desttile, $ex->src_positions[$p]);
                $placedTiles[] = new PlacedTile($desttile, $placement->space);
            }
            if (!$srctile->isEmpty()) {
                $stype = $srctile->type->canonicalType();
                $placement = $de->placeTile($srctile, $ex->dest_positions[$p]);
                $placedTiles[] = new PlacedTile($srctile, $placement->space);
            }
        }

        $barn = $encs[0];
        $offspring = $se->checkForOffspring($barn);
        if ($offspring) {
            $this->saveOffspring($offspring);
            $placedTiles[] = $offspring->child;
        }
        $offspring = $de->checkForOffspring($barn);
        if ($offspring) {
            $this->saveOffspring($offspring);
            $placedTiles[] = $offspring->child;
        }

        // no check fo completion bonus in enclosures

        $this->ps->updateEnclosures($this->player_id, [$se, $de, $barn]);

        return new CompletedExchange($ex->src_enclosure_id, $stype, $ex->dest_enclosure_id, $dtype, $placedTiles, $offspring);
    }

    private ?int $_bankMoney = null;
    public function bankMoney() : int {
        if ($this->_bankMoney === null) {
            $this->_bankMoney = $this->ps->getBankMoney();
        }
        return $this->_bankMoney;
    }

    private function incBankMoney(int $amt): void {
        $this->_bankMoney = $this->bankMoney() + $amt;
        $this->ps->setBankMoney($this->_bankMoney);
    }

    private function chargePlayer(Player $player, Cost $cost) : void {
        $player->payMoney($cost);
        $this->ps->updatePlayer($player);
        $this->incBankMoney($cost->amount());
    }

    private function payPlayer(Player $player, int $amt) : int {
        $amt = min($this->ps->getBankMoney(), $amt);
        $player->receiveMoney($amt);
        $this->ps->updatePlayer($player);
        $this->incBankMoney(-$amt);
        return $amt;
    }

    /** @return array<string,int> */
    private function computeScore(Player $player): array {
        $detail = [
            'player_id' => $player->id,
            'money' => $player->money,
            'full_enclosures' => 0,
            'full_enclosure_points' => 0,
            'near_full_enclosures' => 0,
            'near_full_enclosure_points' => 0,
            'other_enclosures' => 0,
            'other_enclosure_points' => 0,
            'barn_stall_types' => 0,
            'barn_animal_types' => 0,
            'barn_stall_points' => 0,
            'barn_animal_points' => 0,
            'stall_points' => 0,
        ];
        $encs = $this->getEnclosuresForPlayer($player->id);
        $stall_types = [];
        foreach ($encs as $enc) {
            if ($enc->isBarn()) {
                $barnAnimalTypes = [];
                $barnStallTypes = [];
                foreach ($enc->nonEmptyContents() as $tile) {
                    if ($tile->type->isAnimal()) {
                        $barnAnimalTypes[$tile->type->value] = 1;
                    } else { // if $tile->type->isStall() {
                        $barnStallTypes[$tile->type->value] = 1;
                    }
                }
                $detail['barn_stall_types'] = count($barnStallTypes);
                $detail['barn_animal_types'] = count($barnAnimalTypes);
                $detail['barn_stall_points'] = -2 * count($barnStallTypes);
                $detail['barn_animal_points'] = -2 * count($barnAnimalTypes);
            } else {
                switch ($enc->emptyAnimalCount()) {
                    case 0:
                        $detail['full_enclosures']++;
                        $detail['full_enclosure_points'] += $enc->completion_points;
                        break;
                    case 1:
                        $detail['near_full_enclosures']++;
                        $detail['near_full_enclosure_points'] += $enc->near_completion_points;
                        break;
                    default:
                        if (count($enc->stallTypes()) > 0) {
                            $detail['other_enclosures']++;
                            $detail['other_enclosure_points'] += count($enc->filledAnimalPositions());
                            foreach (array_keys($enc->stallTypes()) as $st) {
                                $stall_types[$st] = 1;
                            }
                        }
                };
            }
        }
        $detail['stall_points'] = 2 * count($stall_types);
        $detail['total'] =
              $detail['full_enclosure_points']
            + $detail['near_full_enclosure_points']
            + $detail['other_enclosure_points']
            + $detail['stall_points']
            + $detail['barn_stall_points']
            + $detail['barn_animal_points'];

        return $detail;
    }

    /** @return array<int,array<string,int>> */
    public function computeScores(?bool $persist = false): array {
        $scores = [];
        foreach ($this->getAllPlayers() as $player) {
            $details = $this->computeScore($player);
            $scores[$player->id] = $details;
            if ($persist) {
                $this->ps->updateScore($player->id, $details['total']);
            }
        }
        return $scores;
    }

    public function currentMoneys(): Moneys {
        return new Moneys($this->bankMoney(), array_map(fn (Player $p) => $p->money, $this->getAllPlayers()));
    }
}
