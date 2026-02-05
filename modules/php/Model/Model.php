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

/*
  Basic guideline: all mutations are done through Model public methods. While it may return modeled objects with mutable fields,
  mutations should not be made directly on those objects.
*/

class Model {
    public function __construct(private int $player_id, private PersistentStore $ps = new PersistentStore((new DefaultDb()))) {
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
            $player = new Player($player_id, 2, $player_count, 0, 0, 0);
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

    /** @return array<int,list<Destination>> */
    public function getPossibleDeliveries(int $truck_id): array {
        $truck = $this->getTruck($truck_id);
        $enclosures = $this->getEnclosuresForPlayer($this->player_id);
        return $this->possibleDeliveriesFor($truck, $enclosures);
    }

    /**
     * FIXME: this shouldn't be public, but it is for testing. Better to
     * mock PersistentStore or use a fake DB.
     *
     * @param array<int,Enclosure> $enclosures
     * @return array<int,list<Destination>>
     */
    public function possibleDeliveriesFor(Truck $truck, array $enclosures): array {
        $result = [];
        foreach ($truck->getAllTiles() as $pos => $tile) {
            if ($tile->type->isPlaceable()) {
                foreach ($enclosures as $eid => $enclosure) {
                    $epos = $enclosure->availablePos($tile->type);
                    if ($epos > 0) {
                        $enc = $enclosure->clone();
                        $pl = $enc->placeTile($tile, $epos);
                        $offspring = $enc->checkForOffspring($enclosures[0]->clone());
                        /** @var Moneys|null */
                        $moneyDelta = null;
                        if ($pl->completedEnclosure || ($offspring && $offspring->child->completedEnclosure)) {
                            $moneyDelta = Moneys::chargePlayerDelta($this->player_id, -$enc->coin_bonus);
                        }
                        if (!isset($result[$pos])) {
                            $result[$pos] = [];
                        }
                        $result[$pos][] = new Destination(new Space($eid,$epos), $offspring, $moneyDelta);
                    }
                }
            }
        }
        return $result;
    }

    public function deliverTruckTile(int $truck_pos, Space $space): Delivery {
        $player = $this->getActivePlayer();
        $truck_id = $player->delivering_truck;
        if (!$truck_id) {
            throw new UserException("no truck selected");
        }
        $truck = $this->getTruck($truck_id);
        $tile = $truck->removeTileAt($truck_pos);
        $encl = $this->getEnclosuresForPlayer($this->player_id)[$space->enclosure_id];
        $barn = $this->getEnclosuresForPlayer($this->player_id)[0];
        $toUpdate = [$encl];
        $placement = $encl->placeTile($tile);
        if ($placement->completedEnclosure) {
            $this->payPlayer($player, $encl->coin_bonus);
        }
        $pos = $placement->space->pos;
        if ($pos <> $space->pos) {
            throw new ModelException("put {$truck_id}:{truck_pos} into {$space->enclosure_id}:{$space->pos} but it went into {$placement->space->enclosure_id}:{$placement->space->pos}");
        }
        $offspring = $encl->checkForOffspring($barn);

        $result = new Delivery($truck_id, $truck_pos, $tile, new Destination($space, $offspring));
        if ($offspring) {
            $this->saveOffspring($offspring);
            $toUpdate[] = $barn;
            // FIXME: check fo completion bonus

            // FIXME: return info on new child -- add to return value
        }

        $this->ps->updateEnclosures($this->player_id, $toUpdate);
        $this->ps->updateTruck($truck);
        return $result;
    }

    public function setDeliveryCompleted(): Truck {
        $player = $this->getActivePlayer();
        $t1 = $this->getTruck(2);
        $player->takeTruck();
        $truck = $this->getTruck($player->truck_taken);
        $truck->setTakenBy($player->id);
        $this->ps->updatePlayer($player);
        return $truck;
    }

    /** @return array{truck:Truck,coin_positions:list<int>} */
    public function startTruckDelivery(int $truck_id): array {
        $player = $this->getActivePlayer();
        $player->startDeliveryForTruck($this->getTruck($truck_id));

        $truck = $this->getTruck($truck_id);
        $coinPositions = $truck->coinPositions();
        $coins = 0;
        foreach ($truck->coinPositions() as $coin_pos) {
            $tile = $truck->removeTileAt($coin_pos);
            // assert tile is coin
            $coins++;
            $this->ps->deleteTile($tile);
        }
        $player->receiveMoney($coins);

        $this->ps->updateTruck($truck);
        $this->ps->updatePlayer($this->getActivePlayer());
        return [
            "truck" => $truck,
            "coin_positions" => $coinPositions,
        ];
    }

    /** @return list<int> */
    public function getAvailableTruckIds(): array {
		$player = $this->getActivePlayer();
        if ($player->truck_taken) {
            return [];
        }
        return array_values(array_map(fn ($t)=> $t->id, array_filter($this->getTrucks(), fn ($t) => $t->canBeTaken())));
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

    private function makePossibleDest(Enclosure $enc, Tile $tile, Enclosure $buyer_barn): Destination | null {
        $ap = $enc->availablePos($tile->type);
        if ($ap > 0) {
            $enc = $enc->clone();
            $buyer_barn = $buyer_barn->clone();
            $pl = $enc->placeTile($tile, $ap);
            $offspring = $enc->checkForOffspring($buyer_barn);
            $moneyDelta = null;
            if ($pl->completedEnclosure || ($offspring && $offspring->child->completedEnclosure)) {
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
        // FIXME: I think you're allowed to move a stall into the barn.

        foreach ($barn->nonEmptyContents() as $pos => $tile) {
            $src = new Space($barn->id, $pos);
            /** @var list<Destination> */
            $dests = [];
            foreach ($enclosures as $enc) {
                $dest = $this->makePossibleDest($enc, $tile, $barn);
                if ($dest) {
                    $dests[] = $dest;
                }
            }
            if (count($dests) > 0) {
                $result[] = new PossibleMove($this->player_id, $src, $dests, $moneyDelta);
            }
        }
        // or stall from one (non-barn) enclosure to another
        foreach ($enclosures as $enc) {
            foreach ($enc->nonEmptyContents() as $pos => $tile) {
                /** @var list<Destination> */
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
                    $result[] = new PossibleMove($this->player_id, $src, $dests, $moneyDelta);
                }
            }
        }
        return $result;
    }

    /** @return array{placed_tile: PlacedTile, offspring: Offspring|null, enclosureBonus: int|null} */
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

        $this->ps->updateEnclosures($this->player_id, [$srcenc, $destenc, $encs[0]]);
        return [ 'placed_tile' => $placement, 'offspring' => $offspring, 'enclosureBonus' => $amt ];
    }

    /** @return array{tiles: list<PlacedTile>, offspring: Offspring | null, enclosure_bonus: int|null} */
    public function purchaseTile(int $seller_player_id, int $barn_pos, Space $target): array {
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

        $result = [
            'tiles' => [],
            'offspring' => null,
            'enclosure_bonus' => null,
        ];
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
        $placement = $enc->placeTile($tile, $target->pos);
        $result['tiles'][] = new PlacedTile($tile, $placement->space);
        $completed_enclosure = $placement->completedEnclosure;

        $offspring = $enc->checkForOffspring($buyer_barn);
        $toUpdate = [$enc];
        if ($offspring) {
            $this->saveOffspring($offspring);
            $te = $offspring->child->space->enclosure_id;
            if ($te <> $enc->id) {
                // it's the barn.
                $toUpdate[] = $encs[$te];
            }
            if ($offspring->child->completedEnclosure) {
                $completed_enclosure = true;
            }
            $result['offspring'] = $offspring;
            $result['tiles'][] = $offspring->child;
        }
        if ($completed_enclosure) {
            $result['enclosure_bonus'] = $this->payPlayer($player, $enc->coin_bonus);
        }

        $this->ps->updateEnclosures($this->player_id, $toUpdate);
        return $result;
    }

    /** @return list<PossibleMove> */
    public function getPurchaseableTiles(): array {
        if (! $this->getActivePlayer()->canAfford(Cost::PURCHASE)) {
            return [];
        }
        // FIXME: We need to gather up all the Buys into an object, and attach
        //   a MoneyDelta there for the purchase price.
        $enclosures = $this->getEnclosuresForPlayer();
        /** @var list<PossibleMove> */
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
                    $result[] = new PossibleMove($player->id, new Space($seller_barn->id, $pos), $dests, $delta);
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
        return PossibleExchange::getPossibleExchanges($this->getEnclosuresForPlayer());
    }

    private function saveOffspring(Offspring $offspring): void {
        // new child inserted
        $this->ps->insertTiles([$offspring->child->tile]);
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

    private function chargePlayer(Player $player, Cost $cost) : void {
        $player->payMoney($cost);
        $this->ps->updatePlayer($player);
    }

    private function payPlayer(Player $player, int $amt) : int {
        $player->receiveMoney($amt);
        $this->ps->updatePlayer($player);
        return $amt;
    }

    /** @return array<int,array<string,int>> */
    public function computeScores(?bool $persist = false): array {
        $scores = [];
        foreach ($this->getAllPlayers() as $player) {
            $details = Scorer::scoreForPlayer($player, $this->getEnclosuresForPlayer($player->id));
            $scores[$player->id] = $details;
            if ($persist) {
                $this->ps->updateScore($player->id, $details['total']);
            }
        }
        return $scores;
    }

    public function currentMoneys(): Moneys {
        return new Moneys(array_map(fn (Player $p) => $p->money, $this->getAllPlayers()));
    }
}
