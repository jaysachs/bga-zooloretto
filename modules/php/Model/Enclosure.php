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
    /**
     * @param int $id
     * @param int $capacity
     * @param Tile[] $animals
     * @param Tile[] $stalls
     */
    public function __construct(public readonly int $id, int $animal_capacity, int $stall_capacity , public array $animals = [], public array $stalls = []) {
        while (count($animals) < $animal_capacity) {
            $animals[] = null;
        }
        while (count($stalls) < $stall_capacity) {
            $stalls[] = null;
        }
        foreach ($stalls as $spot) {
            if ($spot != null) {
                $t = $spot->type->value;
                if (!$spot->type->isStall()) {
                    throw new \BgaUserException("Enclosure $id should not contain non-stall tile id $spot->id of type $t");
                }
            }
        }
        foreach ($animals as $spot) {
            if ($spot != null) {
                $t = $spot->type->value;
                if (!$spot->type->isAnimal()) {
                    throw new \BgaUserException("Enclosure $id should not contain non-animal tile id $spot->id of type $t");
                }
            }
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
        foreach ($this->stalls as $i => $t) {
            if ($t == null) {
                return $i+1;
            }
        }
        return 0;
    }

    private function availableAnimalPos(TileType $type): int {
        foreach ($this->animals as $i => $t) {
            if ($t == null) {
                return $i+1;
            }
        }
        return 0;
    }

    public function tileAt(int $pos) {
        if ($pos > 0 && $pos <= count($this->animals) + count($this->stalls)) {
            if ($pos <= count($this->animals)) {
                return $this->animals[$pos-1];
            }
            return $this->stalls[$pos - 1 - count($this->animals)];
        }
        throw new \BgaUserException("No position $pos in encluse $this->id");
    }

    /**
     * Positions are assigned starting with 1, animals first, and the consecutively going to stalls.
     * For example, if there are 4 animal positions and 2 stalls,
     * positions 1 through 4 inclusive are for animals, and positions 5 and 6 are for stalls.
     */
    public function placeTile(Tile $tile, int $pos) {
        $t = $tile->type->value;
        if ($tile->type->isAnimal()) {
            if ($pos < 1 || $pos > count($this->animals)) {
                throw new \BgaUserException("Not position $pos for animals in encluse $this->id");
            }
            $p1 = $pos - 1;
            if ($this->animals[$p1] == null) {
                $this->animals[$p1] = $tile;
                return;
            }
            throw new \BgaUserException("Position $pos is not open in enclosure $this->id for animal $t");
        }
        if ($tile->type->isStall()) {
            $p2 = $pos - count($this->animals) - 1;
            if ($p2 <= 0 || $p2 > count($this->stalls)) {
                throw new \BgaUserException("Not position $pos for stalls in encluse $this->id");
            }
            if ($this->stalls[$p2] == null) {
                $this->stalls[$p2] = $tile;
                return;
            }
            throw new \BgaUserException("Position $pos is not open in enclosure $this->id for stall $t");
        }
        throw new \BgaUserException("Can only place animals and stills in enclosures, not $t");
    }

    public function __toString(): string
    {
        $anims = Utils::arrayToString($this->animals);
        $stalls = Utils::arrayToString($this->stalls);;
        return "Enclosure(id=$this->id,animals=$anims,stalls=$stalls)";
    }
}
