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

namespace Bga\Games\zoolorettoalpha\Model;

use Bga\GameFramework\UserException;
use Bga\Games\zoolorettoalpha\Utils\DefaultDb;
use Bga\Games\zoolorettoalpha\Utils\Arrays;
use Bga\Games\zoolorettoalpha\Utils\Db;

/*
  Basic guideline: all mutations are done through Model public methods. While it may return modeled objects with mutable fields,
  mutations should not be made directly on those objects.
*/

class Model {
    private PersistentStore $ps;
    public function __construct(private int $player_id, Db $db = new DefaultDb()) {
        $this->ps = new PersistentStore($db);
    }

    /** @param list<int> $player_ids */
    public static function createNewGame(array $player_ids, PersistentStore $ps = new PersistentStore()): void {
        $player_count = count($player_ids);
        $stockpool = Tile::createInitialPool($player_count);
        Arrays::shuffle($stockpool);
        $ps->insertStock($stockpool);

        // Trucks
        $trucks = Truck::forPlayerCount($player_count);

        if ($player_count == 2) {
            $extras = [new Tile(1000, TileType::BLOCK), new Tile(1001, TileType::BLOCK), new Tile(1002, TileType::BLOCK)];
            $ps->insertTiles($extras);
            $trucks[2]->placeTileAt($extras[0], 3);
            $trucks[3]->placeTileAt($extras[1], 2);
            $trucks[3]->placeTileAt($extras[2], 3);
        }
        foreach ($trucks as $truck) {
            $ps->updateTruck($truck);
        }

        foreach ($player_ids as $player_id) {
            $player = new Player($player_id, 2, $player_count, 0, 0);
            $ps->updatePlayer($player);
        }
    }

    public function getActivePlayer(): Player {
        return $this->getPlayer($this->player_id);
    }

    private function getPlayer(int $id): Player {
        $players = $this->getAllPlayers();
        if (isset($players[$id])) {
            return $players[$id];
        }
        throw new ModelException("attempt to retrieve unknown player $id");
    }

    /** @var null|array{stock:Stock,trucks:array<int,Truck>,enclosures:array<int,array<int,Enclosure>>} */
    private ?array $_data = null;
    /** @return array{stock:Stock,trucks:array<int,Truck>,enclosures:array<int,array<int,Enclosure>>} */
    private function retrieveAll(): array {
        if ($this->_data === null) {
            /*
            try {
                throw new \Exception("foo");
            } catch (\Exception $e) {
                $this->game->error($e->getTraceAsString());
            }
            */
            $this->_data = $this->ps->retrieveAll($this->getAllPlayers());
        }
        return $this->_data;
    }

    /** @var null|array<int,Player> */
    private ?array $_players = null;

    /** @return array<int,Player> */
    public function getAllPlayers(): array {
        if ($this->_players == null) {
            $this->_players = $this->ps->retrievePlayers();
        }
        return $this->_players;
    }

    public function getStock(): Stock {
        return $this->retrieveAll()["stock"];
    }

    public function drawTile(): Stock {
        if ($this->getActivePlayer()->truck_taken) {
            throw new UserException("player already took truck");
        }
        if ($this->spacesOnTrucks() == 0) {
            throw new UserException("all trucks filled");
        }
        $stock = $this->getStock();
        $stock->drawTile();
        $this->ps->updateStock($stock);
        return $stock;
    }

    /** @return array<int,Truck> */
    public function getTrucks(): array {
        return $this->retrieveAll()["trucks"];
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
        if ($truck->taken_by) {
            throw new ModelException("Can't load on a taken truck");
        }
        $tile = $stock->removeDrawnTile();
        $truck->placeTileAt($tile, $pos);

        $this->ps->updateTruck($truck);

        return $tile;
    }

    private function spacesOnTrucks() : int {
        return intval(array_sum(
            array_map(
                function (Truck $t): int { return $t->freeSpaces(); },
                array_filter($this->getTrucks(), fn ($t) => $t->taken_by == 0)
            )
        ));
    }

    /**
     * Returns enclosure mapped by enclosure_id.
     *
     * @return array<int,Enclosure>
     */
    public function getEnclosuresForPlayer(?int $player_id = 0) : array {
        if ($player_id === null || $player_id === 0) {
            $player_id = $this->player_id;
        }
        return $this->retrieveAll()["enclosures"][$player_id];
    }

    /** @return array<int,list<PlacedTile>> keys are truck positions */
    public function getPossibleDeliveries(int $truck_id): array {
        $truck = $this->getTruck($truck_id);
        $enclosures = $this->getEnclosuresForPlayer($this->player_id);
        return $this->possibleDeliveriesFor($truck, $enclosures, $this->player_id);
    }

    /**
     * FIXME: this shouldn't be public, but it is for testing. Better to
     * mock PersistentStore or use a fake DB.
     *
     * @param array<int,Enclosure> $enclosures
     * @return array<int,list<PlacedTile>>
     */
    public static function possibleDeliveriesFor(Truck $truck, array $enclosures, int $player_id): array {
        $result = [];
        foreach ($truck->getAllTiles() as $pos => $tile) {
            if ($tile->type->isPlaceable()) {
                foreach ($enclosures as $eid => $enclosure) {
                    $epos = $enclosure->availablePos($tile->type);
                    if ($epos > 0) {
                        $enc = $enclosure->clone();
                        $pl = $enc->placeTile($tile, $enclosures[0]->clone(), $epos);
                        if (!isset($result[$pos])) {
                            $result[$pos] = [];
                        }
                        $result[$pos][] = $pl;
                    }
                }
            }
        }
        return $result;
    }

    /**
	 * @param list<Delivery> $deliveries
     * @return array{deliveries:list<CompletedDelivery>,coins:list<Tile>}
     */
    public function takeTruckAndDeliverTiles(int $truck_id, array $deliveries): array {
        $player = $this->getActivePlayer();
        $truck = $this->getTruck($truck_id);
        $encs = $this->getEnclosuresForPlayer($this->player_id);
        $result = $this->deliverPendingTruckTiles($truck_id, $deliveries);

        $completionCoins = 0;
        foreach ($result as $delivery) {
            $pt = $delivery->placed_tile;
            $completionCoins += $pt->completionCoins() ?? 0;
            if ($pt->offspring) {
                $this->saveOffspring($pt->offspring);
            }
        }
        if ($completionCoins) {
            $player->receiveMoney($completionCoins);
        }
        // Collect the coins, add them as deliveries with no destination.
        $coins = [];
        foreach ($truck->coinPositions() as $coin_pos) {
            $tile = $truck->removeTileAt($coin_pos);
            $coins[] = $tile;
            $player->receiveMoney(1);
        }
        if (!$truck->isEmpty()) {
            throw new UserException("Not all tiles delivered for {$truck_id} {$truck}");
        }
        $player->takeTruck($truck_id);
        // Update DB.
        foreach ($coins as $coin) {
            $this->ps->deleteTile($coin);
        }
        // for now, update all enclosures, optimize later
        $this->ps->updateEnclosures($player->id, array_values($encs));
        $this->ps->updatePlayer($player);
        return [ 'deliveries' => $result, 'coins' => $coins ];
    }

    /**
	 * @param list<Delivery> $deliveries
     * @return list<CompletedDelivery>
     */
    public function deliverPendingTruckTiles(int $truck_id, array $deliveries): array {
        $truck = $this->getTruck($truck_id);
        $encs = $this->getEnclosuresForPlayer($this->player_id);
        $barn = $encs[0];
        $result = [];
        foreach ($deliveries as $delivery) {
            $encl = $encs[$delivery->enclosure_id];
            $tile = $truck->removeTileAt($delivery->truck_pos);
            $completed = $encl->placeTile($tile, $barn);
            if ($completed->space->pos <> $delivery->enclosure_pos) {
                throw new ModelException("delivered {$truck_id}:{$delivery} but it went into {$completed->space}");
            }
            $result[] = new CompletedDelivery($delivery->truck_pos, $completed);
        }

        return $result;
    }

    /** @return list<array{truck_id:int,coin_tiles:list<Tile>}> */
    public function getAvailableTrucks(): array {
		$player = $this->getActivePlayer();
        if ($player->truck_taken) {
            return [];
        }
        return array_values(array_map(fn ($t)=> [
            'truck_id' => $t->id,
            'coin_tiles' => array_map(fn ($p) => $t->tileAt($p), $t->coinPositions()),
        ], array_filter($this->getTrucks(), fn ($t) => $t->canBeTaken())));
    }

    /** @return array{truck_ids: list<int>, dumped_tiles: list<Tile>} */
    public function prepareNextRound(): array {
        $players = $this->getAllPlayers();
        /** @var list<int> */
        $truck_ids = [];
        /** @var list<Tile>  */
        $dumped = [];
        $pids = [];
        foreach ($this->getTrucks() as $truck) {
            $pid = $truck->taken_by;
            if ($pid > 0) {
                $truck->returnTruck();
                $taken = $players[$pid]->returnTruck();
                if ($taken != $truck->id) {
                    throw new ModelException("Truck {$truck->id} taken by {$truck->taken_by} but that player has no truck");
                }
                $truck_ids[] = $truck->id;
                $pids[] = $pid;
            }
            else {
                /** @var list<Tile> */
                $dumped = array_merge($dumped, $truck->dumpTiles());
            }
        }
        foreach ($dumped as $tile) {
            $this->ps->deleteTile($tile);
        }
        foreach ($pids as $pid) {
            $this->ps->updatePlayer($players[$pid]);
        }
        return [
            'truck_ids' => $truck_ids,
            'dumped_tiles' => $dumped,
        ];
    }

    public function extensionAvailable(): int {
        return $this->getActivePlayer()->extensionAvailable();
    }

    public function expandZoo(): Player {
        $player = $this->getActivePlayer();

        $extnum = $player->addExtension();
        $this->chargePlayer($player, Cost::EXPAND);
        $this->ps->updatePlayer($player);

        // maybe update cache
        if ($this->_data !== null) {
            $this->_data["enclosures"][$this->player_id][] =
                Enclosure::extension($extnum);
        }

        return $player;
    }

    public function canDraw(): bool {
        return $this->getActivePlayer()->truck_taken == 0
            && $this->getStock()->drawn->isEmpty()
            && $this->spacesOnTrucks() > 0;
    }

    /** @return list<Space> positions in barn that are discardable */
    public function getDiscardables(): array {
        if (! $this->getActivePlayer()->canAfford(Cost::DISCARD)) {
            return [];
        }
        $money_delta = Moneys::costPlayerDelta($this->player_id, Cost::DISCARD);
        $barn = $this->getEnclosuresForPlayer()[0];
        $result = [];
        foreach ($barn->nonEmptyContents() as $pos => $tile) {
            // $result[] = new PlacedTile($tile, new Space(0, $pos), false, $money_delta);
            $result[] = new Space(0, $pos);
        }
        return $result;
    }

    public function discardBarnTile(int $pos): Tile {
        $player = $this->getActivePlayer();
        $barn = $this->getEnclosuresForPlayer()[0];

        $this->chargePlayer($player, Cost::DISCARD);
        $tile = $barn->takeTileAt($pos);
        $this->ps->deleteTile($tile);

        return $tile;
    }

    private function makePossibleDest(Enclosure $enc, Tile $tile, Enclosure $buyer_barn): PlacedTile | null {
        $ap = $enc->availablePos($tile->type);
        if ($ap > 0) {
            $enc = $enc->clone();
            $buyer_barn = $buyer_barn->clone();
            return $enc->placeTile($tile, $buyer_barn, $ap);
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
        // FIXME: I think you're allowed to move a stall into the barn.

        foreach ($barn->nonEmptyContents() as $pos => $tile) {
            $src = new Space($barn->id, $pos);
            /** @var list<PlacedTile> */
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
                /** @var list<PlacedTile> */
                $dests = [];
                $src = new Space($enc->id, $pos);
                if ($tile->type->isStall()) {
                    foreach ($enclosures as $other) {
                        if ($other <> $enc) {
                            $sp = $other->availablePos($tile->type);
                            if ($sp > 0) {
                                $dests[] = new PlacedTile($tile, new Space($other->id, $sp));
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

    public function moveTile(Space $src, Space $dest): PlacedTile {
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
        $placement = $destenc->placeTile($tile, $encs[0], $dest->pos);
        $cc = $placement->completionCoins();
        if ($cc) {
            $this->payPlayer($player, $cc);
        }

        if ($placement->offspring) {
            // FIXME: then check fo completion bonus
            $this->saveOffspring($placement->offspring);
        }

        $this->ps->updateEnclosures($this->player_id, [$srcenc, $destenc, $encs[0]]);
        // FIXME: don't need to explicitly return enclosure bonus
        return $placement;
    }

    public function purchaseTile(int $seller_player_id, int $barn_pos, Space $target): PlacedTile {
        $player = $this->getActivePlayer();
        $src = new Space(0, $barn_pos);

        $found = null;
        foreach ($this->getPurchaseableTiles() as $pt) {
            if ($pt->src_player_id == $seller_player_id && $pt->src == $src) {
                foreach ($pt->dests as $dest) {
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
        $seller->receiveMoney(1);
        $this->ps->updatePlayer($seller);
        $seller_barn = $this->getEnclosuresForPlayer($seller_player_id)[0];

        $encs = $this->getEnclosuresForPlayer();
        $enc = $encs[$target->enclosure_id];
        $buyer_barn = $encs[0];

        $tile = $seller_barn->takeTileAt($src->pos);
        $placement = $enc->placeTile($tile, $buyer_barn, $target->pos);

        $bonus = $placement->completionCoins();
        $toUpdate = [$enc];
        if ($placement->offspring) {
            $this->saveOffspring($placement->offspring);
            $te = $placement->offspring->child->space->enclosure_id;
            if ($te <> $enc->id) {
                // it's the barn.
                $toUpdate[] = $encs[$te];
            }
        }

        if ($bonus !== null) {
            $this->payPlayer($player, $bonus);
        }
        $this->ps->updateEnclosures($this->player_id, $toUpdate);
        return $placement;
    }

    /** @return list<PossiblePurchase> */
    public function getPurchaseableTiles(): array {
        if (! $this->getActivePlayer()->canAfford(Cost::PURCHASE)) {
            return [];
        }
        // FIXME: We need to gather up all the Buys into an object, and attach
        //   a MoneyDelta there for the purchase price.
        $enclosures = $this->getEnclosuresForPlayer();
        /** @var list<PossiblePurchase> */
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
                    $delta = new Moneys([ $player->id => 1, $this->player_id => -Cost::PURCHASE->amount() ]);
                    $result[] = new PossiblePurchase($player->id, new Space($seller_barn->id, $pos), $dests, $delta);
                }
            }
        }
        return $result;
    }

    public function getPossibleExchanges() : ?Exchanges {
        if (! $this->getActivePlayer()->canAfford(Cost::EXCHANGE)) {
            // can't afford it.
            return null;
        }
        return Exchanges::forEnclosures($this->getEnclosuresForPlayer($this->player_id));
    }

    private function saveOffspring(Offspring $offspring): void {
        // new child inserted
        $this->ps->insertTiles([$offspring->child->tile]);
    }

    /**
     * @param list<int> $dest_positions
     */
    public function exchange(int $src_enclosure_id, int $dest_enclosure_id, ?array $dest_positions): CompletedExchange {
        $player = $this->getActivePlayer();
        if (!$player->canAfford(Cost::EXCHANGE)) {
            throw new ModelException("player cannot afford exchange");
        }
        $this->chargePlayer($player, Cost::EXCHANGE);
        if ($src_enclosure_id == 0) {
            throw new ModelException("Exchange source must not be barn.");
        }
        if ($src_enclosure_id == $dest_enclosure_id) {
            throw new ModelException("Source and destination exchange enclosures must be different");
        }
        $encs = $this->getEnclosuresForPlayer();
        $se = $encs[$src_enclosure_id];
        $de = $encs[$dest_enclosure_id];
        $barn = $encs[0];

        if ($dest_enclosure_id == 0) {
            if ($dest_positions === null || count($dest_positions) == 0) {
                throw new ModelException("Exchange into barn requires positions to be specified");
            }
            $animalType = $barn->tileAt($dest_positions[0])->type->canonicalType();
            foreach ($dest_positions as $pos) {
                $t = $barn->tileAt($pos);
                if (!$t->type->isAnimal()) {
                    throw new ModelException("Non-animal tile exchange attempted");
                }
                if ($t->type->canonicalType() != $animalType) {
                    throw new ModelException("More than one dest animal type found");
                }
            }

            // Now make sure we didn't leave any animals behind
            foreach ($barn->filledAnimalPositions() as $pos) {
                if (array_search($pos, $dest_positions) === false) {
                    if ($barn->tileAt($pos)->type->canonicalType() == $animalType) {
                        throw new ModelException("Barn destinations did not specify all animals of type {$animalType->value} {$pos} ".Arrays::arrayToString($dest_positions));
                    }
                }
            }
        } else if ($dest_positions) {
            throw new ModelException("Exchange into non-barn cannot specify positions");
        } else {
            $dest_positions = $de->filledAnimalPositions();
        }
        $src_positions = $se->filledAnimalPositions();

        $srcType = $se->tileAt($src_positions[0])->type->canonicalType();
        $destType = $de->tileAt($dest_positions[0])->type->canonicalType();

        /** @var list<PlacedTile> */
        $placedTiles = [];

        $srcTiles = array_map(fn ($p) => $se->takeTileAt($p), $src_positions);
        $destTiles = array_map(fn ($p) => $de->takeTileAt($p), $dest_positions);
        // FIXME: no completion bonus here
        foreach($srcTiles as $t) {
            $placedTiles[] = $de->placeTile($t, $barn, array_shift($dest_positions) ?? 0);
        }
        foreach($destTiles as $t) {
            $placedTiles[] = $se->placeTile($t, $barn, array_shift($src_positions) ?? 0);
        }

        foreach ($placedTiles as $pt) {
            if ($pt->offspring) {
                $this->saveOffspring($pt->offspring);
            }
        }
        // not possible to generate offspring in the destination:
        //   barn simply not done
        //   if another enclosure, the offspring would have already been generated in the src
        // The only possible way to generate offspringin the source is if the dest is the barn

        // FIXME need to ignore any completion bonus in enclosures

        $this->ps->updateEnclosures($this->player_id, [$se, $de, $encs[0]]);

        return new CompletedExchange($src_enclosure_id, $srcType, $dest_enclosure_id, $destType, $placedTiles);
    }

    private function chargePlayer(Player $player, Cost $cost) : void {
        $player->payMoney($cost);
        $this->ps->updatePlayer($player);
    }

    private function payPlayer(Player $player, ?int $amt) : void {
        if ($amt) {
            $player->receiveMoney($amt);
            $this->ps->updatePlayer($player);
        }
    }

    /** @return array<int,array<string,int>> */
    public function computeScores(): array {
        $scores = [];
        foreach ($this->getAllPlayers() as $player) {
            $scores[$player->id] = Scorer::scoreForPlayer($player, $this->getEnclosuresForPlayer($player->id));
        }
        return $scores;
    }

    public function currentMoneys(): Moneys {
        return new Moneys(array_map(fn (Player $p) => $p->money, $this->getAllPlayers()));
    }
}
