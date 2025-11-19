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

use \Bga\Games\zooloretto\Utils;

class Enclosure {
    /** @var Tile[] */
    private array $contents = [];
    private int $total_capacity;

    /**
     * @param int $id
     * @param int $animal_capacity
     * @param int $stall_capacity
     */
    public function __construct(public readonly int $id, readonly int $animal_capacity, readonly int $stall_capacity) {
        $this->total_capacity = $animal_capacity + $stall_capacity;
        for ($pos = 1; $pos <= $this->total_capacity; $pos++) {
            $this->contents[$pos] = Tile::empty();
        }
    }

    public function availablePos(TileType $type): int {
        if ($type->isAnimal()) {
            return $this->availableAnimalPos($type);
        }
        if ($type->isStall()) {
            return $this->availableStallPos();
        }
        return 0;
    }

    private function availableStallPos(): int {
        for ($pos = $this->animal_capacity + 1; $pos <= $this->total_capacity; $pos++) {
            if ($this->contents[$pos]->isEmpty()) {
                return $pos;
            }
        }
        return 0;
    }

    private function availableAnimalPos(TileType $type): int {
        for ($pos = 1; $pos <= $this->animal_capacity; $pos++) {
            if ($this->contents[$pos]->isEmpty()) {
                return $pos;
            }
        }
        return 0;
    }

    public function takeTileAt(int $pos): Tile {
        if ($pos > 0 && $pos <= $this->total_capacity) {
            $t = $this->contents[$pos];
            $this->contents[$pos] = Tile::empty();
            return $t;
        }
        throw new ModelException("No position $pos in enclosure $this->id");
    }

    public function tileAt(int $pos) {
        if ($pos > 0 && $pos <= $this->total_capacity) {
            return $this->contents[$pos];
        }
        throw new ModelException("No position $pos in encluse $this->id");
    }

    /** @return Tile[]  where key is position */
    public function allContents(): array {
        return $this->contents;
    }

    /**
     * Positions are assigned starting with 1, animals first, and the consecutively going to stalls.
     * For example, if there are 4 animal positions and 2 stalls,
     * positions 1 through 4 inclusive are for animals, and positions 5 and 6 are for stalls.
     *
     * position 0 means "nextavailable"
     * @return int the position it was placed in
     */
    public function placeTile(Tile $tile, int $pos = 0) {
        $t = $tile->type->value;
        if ($tile->type->isAnimal()) {
            if ($pos == 0) {
                $pos = 1;
                while ($pos <= $this->animal_capacity && !$this->contents[$pos]->isEmpty()) {
                    $pos++;
                }
            }
            if ($pos > $this->animal_capacity) {
                throw new ModelException("Not position $pos for animals in encluse $this->id");
            }
            if ($this->contents[$pos]->isEmpty()) {
                $this->contents[$pos] = $tile;
                return $pos;
            }
            throw new ModelException("Position $pos is not open in enclosure $this->id for animal $t");
        }
        if ($tile->type->isStall()) {
            if ($pos == 0) {
                $pos = $this->animal_capacity + 1;
                while ($pos <= count($this->contents) && !$this->contents[$pos]->isEmpty()) {
                    $pos++;
                }
            }
            if ($pos > $this->total_capacity) {
                throw new ModelException("Not position $pos for stalls in encluse $this->id");
            }
            if ($this->contents[$pos]->isEmpty()) {
                $this->contents[$pos] = $tile;
                return $pos;
            }
            throw new ModelException("Position $pos is not open in enclosure $this->id for stall $t");
        }
        throw new ModelException("Can only place animals and stills in enclosures, not $t");
    }

    public function __toString(): string
    {
        $contents = Utils::arrayToString($this->contents);
        return "Enclosure(id=$this->id,animal_capacity=$this->animal_capacity,stall_cap=$this->stall_capacity,contents=$contents)";
    }
}
