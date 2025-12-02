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

class Truck {
    public const int CAPACITY = 3;

    /** @var Tile[] */
    private array $tiles;

    /** @param $tiles Tile[] */
    public function __construct(
        public readonly int $id,
        ?array $tiles = null,
        public ?int $taken_by = null) {
        if ($this->taken_by === 0) {
            $this->taken_by = null;
        }
        if ($tiles == null) {
            $this->tiles = [Tile::empty(), Tile::empty(), Tile::empty()];
        } else {
            $this->tiles = $tiles;
        }
        $c = count($this->tiles);
        if ($c != self::CAPACITY) {
            throw new ModelException("Attempt to construct Truck with contents of size {$c} different than " . self::CAPACITY);
        }
        foreach ($this->tiles as $tile) {
            if ($tile == null) {
                throw new ModelException("Cannot have null tiles in a truck");
            }
        }
    }

    public function isEmpty(): bool {
        foreach ($this->getAllTiles() as $tile) {
            if (!$tile->isEmpty()) { return false; }
        }
        return true;
    }

    public function firstFreePosition(): int {
        foreach ($this->getAllTiles() as $pos => $tile) {
            if ($tile->isEmpty()) {
                return $pos;
            }
        }
        return 0;
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

    /** return int[] */
    public function coinPositions(): array {
        $result = [];
        for ($pos = 0; $pos < count($this->tiles); $pos++) {
            if ($this->tiles[$pos]->type == TileType::COIN) {
                $result[] = $pos + 1;
            }
        }
        return $result;
    }

    public function takeCoins(): int {
        $amt = 0;
        foreach ($this->tiles as $i => $tile) {
            if ($tile->type == TileType::COIN) {
                $amt++;
                $this->tiles[$i] = Tile::empty();
            }
        }
        return $amt;
    }

    /** @return int[] the positions emptied. */
    public function dumpTiles(): array {
        if ($this->taken_by) {
            throw new ModelException("Cannot dump a non-taken truck");
        }
        $p = [];
        for ($i = 0; $i < count($this->tiles); $i++) {
            if (!$this->tiles[$i]->type->isEmpty() && !$this->tiles[$i]->type->isBlock()) {
                $this->tiles[$i] = Tile::empty();
                $p[] = $i + 1;
            }
        }
        return $p;
    }

    public function returnTruck(): int {
        if (!$this->taken_by) {
            throw new ModelException("Cannot return a non-taken truck");
        }
        foreach ($this->tiles as $tile) {
            if (!$tile->isEmpty() && !$tile->type->isBlock()) {
                throw new ModelException("Cannot return a non-empty truck");
            }
        }
        $pid = $this->taken_by;
        $this->taken_by = null;
        return $pid;
    }

    public function setTakenBy(int $player_id): void {
        if ($player_id == 0) {
            throw new ModelException("0 player cannot take a truck");
        }
        if (!$this->taken_by) {
            $this->taken_by = $player_id;
        } else {
            throw new ModelException("Attempt to take truck $this->id by $player_id that was already taken by $this->taken_by");
        }
    }

    public function canBeTaken(): bool {
        if ($this->taken_by) {
            return false;
        }
        foreach ($this->tiles as $tile) {
            if (! $tile->isEmpty() && !$tile->type->isBlock()) {
                return true;
            }
        }
        return false;
    }

    // public function tileAt(int $pos): Tile {
    //     if ($pos <= 0 || $pos > self::CAPACITY) {
    //         throw new ModelException("Cannot get tile in position $pos of truck");
    //     }
    //     return $this->tiles[$pos-1];
    // }

    public function removeTileAt(int $pos): Tile {
        if ($pos <= 0 || $pos > self::CAPACITY) {
            throw new ModelException("Cannot get tile in position $pos of truck");
        }
        $tile = $this->tiles[$pos-1];
        if ($tile->isEmpty()) {
            throw new ModelException("Cannot remove from an empty space $pos of truck");
        }
        $this->tiles[$pos-1] = Tile::empty();
        return $tile;
    }

    public function freeSpaces(): int {
        return array_reduce($this->tiles, fn ($s, Tile $t): int => ($s + ($t->isEmpty() ? 1 : 0)), 0);
    }

    /**
     * @param $pos 1-based position on the truck
     */
    public function placeTileAt(Tile $tile, int $pos): void {
        if ($tile->isEmpty()) {
            throw new ModelException("Cannot place empty tile into truck");
        }
        if (!$tile->type->isAnimal() && !$tile->type->isStall() && $tile->type != TileType::COIN) {
            $type = $tile->type->value;
            throw new ModelException("Cannot place tile of type $type on truck");
        }
        if ($pos <= 0 || $pos > self::CAPACITY) {
            throw new ModelException("Cannot place tile in position $pos of truck");
        }
        $p = $pos - 1;
        if (!$this->tiles[$p]->isEmpty()) {
            throw new ModelException("Cannot place tile in already occupied truck position $pos");
        }
        $this->tiles[$p] = $tile;
    }

    public function __toString(): string
    {
        return "Truck(id=$this->id,tiles=" . Utils::arrayToString($this->tiles) . ")";
    }
}
