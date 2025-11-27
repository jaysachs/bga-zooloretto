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
    public int $enclosure_id;
    public int $enclosure_pos; // not clear if needed

    // FIXME: put these in a class?
    public int $offspring_pos = 0;
    public TileType $offspring_type = TileType::EMPTY;

    public ?PossiblePlacement $next = null;

    public function serialize(): array {
        $data = [
            'enclosure_id' => $this->enclosure_id,
            'enclosure_pos' => $this->enclosure_pos,
            // 'next' => $this->next ? array_map(fn (PossiblePlacement $n) => $n->serialize(), $this->next->placements) : [],
        ];
        if ($this->next) {
            $data['next'] = $this->next->serialize();
        }

        if ($this->offspring_pos) {
            $data['offspring_pos'] = $this->offspring_pos;
            $data['offspring_type'] = $this->offspring_type;
        }
        return $data;
    }
}

class PlacementsForTruckPos {
    public int $truck_pos = 0;
    public TileType $tile_type = TileType::EMPTY;

    /** @var PlacementForEnclosure[] */
    private array $next = [];

    public function addNext(PlacementForEnclosure $p2): void {
        $this->next[] = $p2;
    }

    public function serialize(): array {
        return [
            'truck_pos' => $this->truck_pos,
            'tile_type' => $this->tile_type->value,
            'encs' => array_map(fn (PlacementForEnclosure $pp) => $pp->serialize(), $this->next),
        ];
    }
}

class PossiblePlacement {
    public function __construct() {}

    /** var PlacementsForTruckPos[] */
    private array $placements = [];

    public function add(PlacementsForTruckPos $p1): void {
        $this->placements[] = $p1;
    }

    public function serialize(): array {
        return array_map(fn (PlacementsForTruckPos $p) => $p->serialize(), $this->placements);
    }


    /** @param Enclosure[] $enclosures */
    private static function getPlacementsForEnclosure(Truck $truck, Tile $tile, array $enclosures, Enclosure $enclosure, int $enclosure_pos): PlacementForEnclosure {
        $p2 = new PlacementForEnclosure();
        $p2->enclosure_id = $enclosure->id;
        $p2->enclosure_pos = $enclosure_pos;
        $enclosure->placeTile($tile, $enclosure_pos);
        // FIXME: check for offspring here
        $p2->next = self::possiblePlacementFor($truck, $enclosures);
        $enclosure->takeTileAt($enclosure_pos);
        return $p2;
    }

    /** @param Enclosure[] $enclosures */
    private static function getPlacementForTruckPos(Truck $truck, int $truck_pos, Tile $tile, array $enclosures): PlacementsForTruckPos {
        $p1 = new PlacementsForTruckPos();
        $p1->truck_pos = $truck_pos;
        $p1->tile_type = $tile->type;
        foreach ($enclosures as $enclosure) {
            $ep = $enclosure->availablePos($tile->type);
            if ($ep > 0) {
                $p1->addNext(self::getPlacementsForEnclosure($truck, $tile, $enclosures, $enclosure, $ep));
            }
        }
        return $p1;
    }

    /** @param Enclosure[] $enclosures */
    public static function possiblePlacementFor(Truck $truck, array $enclosures): PossiblePlacement {
        $pp = new PossiblePlacement();
        foreach ($truck->getAllTiles() as $pos => $tile) {
            if ($tile->type->isPlaceable()) {
                $tile = $truck->removeTileAt($pos);
                $pp->add(self::getPlacementForTruckPos($truck, $pos, $tile, $enclosures));
                $truck->placeTileAt($tile, $pos);
            }
        }
        return $pp;

    }

}
