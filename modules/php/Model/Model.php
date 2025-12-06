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
use Bga\Games\zooloretto\Utils;

/*
  Basic guideline: all mutations are done through Model public methods. While it may return modeled objects with mutable fields,
  mutations should not be made directly on those objects.
*/

class Model {
    public function __construct(private Table $game, private int $player_id, private PersistentStore $ps = new PersistentStore((new DefaultDb()))) {
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


    /** @var array<int,Enclosure[]> keyed by player_id */
    private $_enclosures = [];

    /**
     * Returns enclosure mapped by enclosure_id.
     *
     * @return Enclosure[]
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
     * @param Delivery[] $deliveries
     *
     * @return Delivery[]
    */
    public function placeTilesInZooAndTakeTruck(int $truck_id, array $deliveries): array {
        $encs = $this->getEnclosuresForPlayer();
        $barn = $encs[0];
        $toUpdate = [];
        $player = $this->getActivePlayer();
        foreach ($deliveries as $delivery) {
            $truck = $this->getTruck($truck_id);
            $encl = $encs[$delivery->space->enclosure_id];
            $toUpdate[] = $encl;
            $tile = $truck->removeTileAt($delivery->truck_pos);
            $placement = $encl->placeTile($tile);
            if ($placement->completedEnclosure) {
                $amt = min($this->ps->getBankMoney(), $encl->coin_bonus);
                $this->payPlayer($player, $amt);
            }
            $pos = $placement->space->pos;
            if ($pos <> $delivery->space->pos) {
                // FIXME: this exception should be correct but it isn't.
                // throw new ModelException("put {$truck_id}:{$placement->truck_pos} into {$placement->enclosure_id}:{$placement->enclosure_pos} but it went in {$pos}");
                $delivery->space->pos = $pos;
            }
            $offspring = $encl->checkForOffspring($barn);

            if ($offspring) {
                $delivery->offspring = $offspring;
                $this->saveOffspring($offspring);
                // FIXME: check fo completion bonus

                // FIXME: return info on new child -- add to return value
            } else {
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

    /** @return array<int,array<int>> keyed by truck ID; if null, truck is returned, otherwise it's the positions dumped. */
    public function prepareNextRound(): array {
        $players = $this->getAllPlayers();
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

    /** return Destination[] positions in barn that are discardable */
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

    public function getPossibleMoves(): PossibleMoves {
        if (! $this->getActivePlayer()->canAfford(Cost::MOVE)) {
            return new PossibleMoves(new Moneys(0, []), []);
        }
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
                $result[] = new PossibleMove($src, $dests);
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
                    $result[] = new PossibleMove($src, $dests);
                }
            }
        }
        return new PossibleMoves(Moneys::costPlayerDelta($this->player_id, Cost::MOVE), $result);
    }

    public function moveTile(Space $src, Space $dest): void {
        $pms = $this->getPossibleMoves();
        $found = false;
        foreach ($pms->moves as $pm) {
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
        if ($placement->completedEnclosure) {
            $amt = min($this->ps->getBankMoney(), $destenc->coin_bonus);
            $this->payPlayer($player, $amt);
        }

        $offspring = $destenc->checkForOffspring($encs[0]);
        if ($offspring) {
            // FIXME: then check fo completion bonus
            $this->saveOffspring($offspring);
        }

        $this->ps->updateEnclosures($this->player_id, $encs);
    }

    public function purchaseTile(int $seller_player_id, int $barn_pos, Space $target): Tile {
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
        if ($placement->completedEnclosure) {
            $amt = min($this->ps->getBankMoney(), $enc->coin_bonus);
            $this->payPlayer($player, $amt);
        }

        $offspring = $enc->checkForOffspring($buyer_barn);
        $toUpdate = [$enc];
        if ($offspring) {
            $this->saveOffspring($offspring);
            $te = $offspring->childSpace->enclosure_id;
            if ($te <> $enc->id) {
                $toUpdate[] = $encs[$te];
            }
            // FIXME: then check fo completion bonus
        }
        // Update the selling player first, to avoid violating uniqueness constraint in DB.
        $this->ps->updateEnclosures($seller_player_id, [$seller_barn]);
        $this->ps->updateEnclosures($this->player_id, $toUpdate);
        return $tile;
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
                    $result[] = new PossibleBuy($player->id, $delta, new PossibleMove(new Space($seller_barn->id, $pos), $dests));
                }
            }
        }
        return $result;
    }

    public function getPossibleExchanges() : ?PossibleExchanges {
        if (! $this->getActivePlayer()->canAfford(Cost::EXCHANGE)) {
            // can't afford it.
            return null;
        }
        return new PossibleExchanges(
            Moneys::costPlayerDelta($this->player_id, Cost::EXCHANGE),
            PossibleExchange::getPossibleExchanges($this->getEnclosuresForPlayer()));
    }

    private function saveOffspring(Offspring $offspring): void {
        // new child inserted
        $this->ps->insertTiles([$offspring->child]);
        // update parents, marked reproduced.
        $this->ps->updateTile($offspring->mother);
        $this->ps->updateTile($offspring->father);
    }

    /** @return TileType[]  length 2 of the form [srctype, desttype] */
    public function exchange(PossibleExchange $ex): array {
        $found = false;
        $pex = $this->getPossibleExchanges();
        if (!$pex) {
            throw new ModelException("No exchanges possible");
        }
        foreach ($pex->exchanges as $px) {
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
                $dtype = $desttile->type;
                $se->placeTile($desttile, $ex->src_positions[$p]);
            }
            if (!$srctile->isEmpty()) {
                $stype = $srctile->type;
                $de->placeTile($srctile, $ex->dest_positions[$p]);
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

        $this->ps->updateEnclosures($this->player_id, [$se, $de, $barn]);

        return [$stype, $dtype];
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

    private function payPlayer(Player $player, int $amt) : void {
        $player->receiveMoney($amt);
        $this->ps->updatePlayer($player);
        $this->incBankMoney(-$amt);
    }

    private function computeScore(Player $player): mixed {
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
        ];
        $encs = $this->getEnclosuresForPlayer($player->id);
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
                        }
                };
            }
        }
        $detail['total'] =
              $detail['full_enclosure_points']
            + $detail['near_full_enclosure_points']
            + $detail['other_enclosure_points']
            + $detail['barn_stall_points']
            + $detail['barn_animal_points'];

        return $detail;
    }

    /** @return array<int,array> */
    public function computeScores(): array {
        $scores = [];
        foreach ($this->getAllPlayers() as $player) {
            $details = $this->computeScore($player);
            $scores[$player->id] = $details;
            $this->ps->updateScore($player->id, $details['total']);
        }
        // FIXME: persist details?
        // FIXME: persist scores
        $sql = "UPDATE player set player_score_aux = money";
        $this->game->DbQuery( $sql );
        return $scores;
    }

    public function currentMoneys(): Moneys {
        return new Moneys($this->bankMoney(), array_map(fn (Player $p) => $p->money, $this->getAllPlayers()));
    }
}
