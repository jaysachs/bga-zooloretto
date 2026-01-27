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

class PlacementForEnclosure {

    public function __construct(
        public Space $space,
        // FIXME: allowed to be null, but we never make it null. Allow that.
        public ?PossiblePlacement $next = null,
        public ?Offspring $offspring = null,
        public ?Moneys $moneyDelta = null) {}

    /** @return array<string,mixed> */
    public function serialize(): array {
        return [
            'space' => $this->space->serialize(),
            'next' => $this->next ? $this->next->serialize() : [],
            'offspring' => $this->offspring ? $this->offspring->serialize() : null,
            'money_delta' => $this->moneyDelta ? $this->moneyDelta->serialize() : null,
        ];
    }

    /** @return array<string,mixed> */
    public function serializeNoNext(): array {
        return [
            'space' => $this->space->serialize(),
            'offspring' => $this->offspring ? $this->offspring->serialize() : null,
            'money_delta' => $this->moneyDelta ? $this->moneyDelta->serialize() : null,
        ];
    }
}

class PlacementsForTruckPos {
    /** @param list<PlacementForEnclosure> $next */
    public function __construct(
        public int $truck_pos = 0,
        public TileType $tile_type = TileType::EMPTY,
        public array $next = []) {}

    public function addNext(PlacementForEnclosure $p2): void {
        $this->next[] = $p2;
    }

    /** @return array<string,mixed> */
    public function serialize(): array {
        return [
            'truck_pos' => $this->truck_pos,
            'tile_type' => $this->tile_type->value,
            'encs' => array_map(fn (PlacementForEnclosure $pp) => $pp->serialize(), $this->next),
        ];
    }
}

class PossiblePlacement {
    /** @param list<PlacementsForTruckPos> $placements */
    public function __construct(public array $placements = []) {}


    public function add(PlacementsForTruckPos $p1): void {
        $this->placements[] = $p1;
    }

    /** @return list<mixed> */
    public function serialize(): array {
        return array_map(fn (PlacementsForTruckPos $p) => $p->serialize(), $this->placements);
    }


    /** @param array<int,Enclosure> $enclosures */
    private static function getPlacementsForEnclosure(int $player_id, Truck $truck, Tile $tile, array $enclosures, int $eid, int $enclosure_pos): PlacementForEnclosure {
        $pfe = new PlacementForEnclosure(new Space($eid,$enclosure_pos));

        $clones = array_map(fn ($e) => $e->clone(), $enclosures);
        $enc = $clones[$eid];
        $pl = $enc->placeTile($tile, $enclosure_pos);

        $offspring = $enc->checkForOffspring($clones[0]);
        $pfe->offspring = $offspring;

        if ($pl->completedEnclosure || ($offspring && $offspring->child->completedEnclosure)) {
            $pfe->moneyDelta = Moneys::chargePlayerDelta($player_id, -$enc->coin_bonus);
        }

        if (!$truck->isEmpty()) {
            $pfe->next = self::possiblePlacementFor($player_id, $truck, $clones);
        }
        return $pfe;
    }

    /** @param array<int,Enclosure> $enclosures */
    private static function getPlacementForTruckPos(int $player_id, Truck $truck, int $truck_pos, array $enclosures): PlacementsForTruckPos {
        $tile = $truck->removeTileAt($truck_pos);
        $pftp = new PlacementsForTruckPos();
        $pftp->truck_pos = $truck_pos;
        $pftp->tile_type = $tile->type;
        foreach ($enclosures as $eid => $enclosure) {
            $epos = $enclosure->availablePos($tile->type);
            if ($epos > 0) {
                $pftp->addNext(self::getPlacementsForEnclosure($player_id, $truck, $tile, $enclosures, $eid, $epos));
            }
        }
        $truck->placeTileAt($tile, $truck_pos);
        return $pftp;
    }

    /** @param array<int,Enclosure> $enclosures */
    public static function possiblePlacementFor(int $player_id, Truck $truck, array $enclosures): PossiblePlacement {
        $pp = new PossiblePlacement();
        foreach ($truck->getAllTiles() as $pos => $tile) {
            if ($tile->type->isPlaceable()) {
                $pp->add(self::getPlacementForTruckPos($player_id, $truck, $pos, $enclosures));
            }
        }
        return $pp;

    }

}
