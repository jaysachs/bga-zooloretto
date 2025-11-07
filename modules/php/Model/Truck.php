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

class Truck {
    public const CAPACITY = 3;

    /** @param $tiles ?Tile[] */
    public function __construct(
        public readonly int $id,
        private array $tiles = [null, null, null],
        public int $taken_by = 0) {
        $c = count($tiles);
        if ($c != self::CAPACITY) {
            throw new \Exception("Attempt to construct Truck with contents of size {$c} different than {" + self::CAPACITY);
        }
    }

    /** @return Tile[] indexed by position */
    public function getAllTiles(): array {
        $result = [];
        $pos = 1;
        foreach ($this->tiles as $tile) {
            $result[$pos++] = $tile;
        }
        return $result;
    }

    public function setTakenBy(int $player_id): void {
        if ($player_id == 0) {
            throw new ModelException("0 player cannot take a truck");
        }
        if ($this->taken_by == 0) {
            $this->taken_by = $player_id;
        } else {
            throw new ModelException("Attempt to take truck $this->id by $player_id that was already taken by $this->taken_by");
        }
    }

    public function tileAt(int $pos): ?Tile {
        if ($pos <= 0 || $pos > self::CAPACITY) {
            throw new ModelException("Cannot get tile in position $pos of truck");
        }
        return $this->tiles[$pos-1];
    }

    public function freeSpaces(): int {
        return array_reduce($this->tiles, fn ($s, $t) => ($s + ($t == null ? 1 : 0)), 0);
    }

    /**
     * @param $pos 1-based position on the truck
     */
    public function placeTileAt(Tile $tile, int $pos): void {
        if ($pos <= 0 || $pos > self::CAPACITY) {
            throw new ModelException("Cannot place tile in position $pos of truck");
        }
        $p = $pos - 1;
        if ($this->tiles[$p] != null) {
            throw new ModelException("Cannot place tile in already occupied truck position $pos");
        }
        $this->tiles[$p] = $tile;
    }
}
