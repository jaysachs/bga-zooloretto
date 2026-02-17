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

use Bga\Games\zoolorettoalpha\Utils\Arrays;

class Truck {
    public const int CAPACITY = 3;

    /** @var array<int, Tile> */
    private array $tiles;

    /** @param array<int, Tile> $tiles */
    public function __construct(
        public readonly int $id,
        ?array $tiles = null,
        public private(set) int $taken_by = 0) {
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
            if (!$tile->isEmpty() && !$tile->type->isBlock()) { return false; }
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

    /** @return array<int, Tile> indexed by position */
    public function getAllTiles(): array {
        $result = [];
        $pos = 1;
        foreach ($this->tiles as $tile) {
            $result[$pos++] = $tile;
        }
        return $result;
    }

    /** @return list<int> */
    public function coinPositions(): array {
        $result = [];
        for ($pos = 1; $pos <= count($this->tiles); $pos++) {
            if ($this->tiles[$pos-1]->type == TileType::COIN) {
                $result[] = $pos;
            }
        }
        return $result;
    }

    /** @return list<Tile> the tiles emptied. */
    public function dumpTiles(): array {
        if ($this->taken_by != 0) {
            throw new ModelException("Cannot dump a non-taken truck");
        }
        $t = [];
        for ($i = 0; $i < count($this->tiles); $i++) {
            if (!$this->tiles[$i]->type->isEmpty() && !$this->tiles[$i]->type->isBlock()) {
                $t[] = $this->tiles[$i];
                $this->tiles[$i] = Tile::empty();
            }
        }
        return $t;
    }

    public function returnTruck(): int {
        if ($this->taken_by == 0) {
            throw new ModelException("Cannot return a non-taken truck {$this}");
        }
        if (!$this->isEmpty()) {
            throw new ModelException("Cannot return a non-empty truck {$this}");
        }
        $pid = $this->taken_by;
        $this->taken_by = 0;
        return $pid;
    }

    public function setTakenBy(int $player_id): void {
        if ($player_id == 0) {
            throw new ModelException("0 player cannot take a truck");
        }
        if ($this->taken_by == 0) {
            $this->taken_by = $player_id;
        } else {
            throw new ModelException("Attempt to take truck {$this->id} by {$player_id} that was already taken by {$this->taken_by}");
        }
    }

    public function canBeTaken(): bool {
        return ($this->taken_by == 0) && !$this->isEmpty();
    }

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
        return array_reduce($this->tiles, fn (int $s, Tile $t): int => ($s + ($t->isEmpty() ? 1 : 0)), 0);
    }

    /**
     * @param $pos 1-based position on the truck
     */
    public function placeTileAt(Tile $tile, int $pos): void {
        if ($tile->isEmpty()) {
            throw new ModelException("Cannot place empty tile into truck {$this->id}");
        }
        if ($pos <= 0 || $pos > self::CAPACITY) {
            throw new ModelException("Cannot place {$tile} in non-existent position {$pos} of truck {$this->id}");
        }
        $p = $pos - 1;
        if (!$this->tiles[$p]->isEmpty()) {
            throw new ModelException("Cannot place {$tile} in occupied position {$pos} of truck {$this->id}");
        }
        $this->tiles[$p] = $tile;
    }

    public function tileAt(int $pos): Tile {
        if ($pos <= 0 || $pos > self::CAPACITY) {
            throw new ModelException("invalid position {$pos} of truck {$this->id}");
        }
        return $this->tiles[$pos-1];
    }

    public function __toString(): string
    {
        return "Truck(id={$this->id},taken_by={$this->taken_by},tiles=" . Arrays::arrayToString($this->tiles) . ")";
    }

    public static function translated(int $truck_id): string {
        return match ($truck_id) {
            1 => clienttranslate("truck 1"),
            2 => clienttranslate("truck 2"),
            3 => clienttranslate("truck 3"),
            default => "unknown truck"
        };
    }

    /** @return array<int,Truck> keyed by id */
    public static function forPlayerCount(int $player_count): array {
        $trucks = [];
        $n = max($player_count, 3);
        for ($i = 1; $i <= $n; $i++) {
            $trucks[$i] = new Truck($i);
        }
        return $trucks;
    }

    /** @return array<string,mixed> */
    public function serialize(): array {
        $contents = [];
        foreach ($this->getAllTiles() as $pos => $tile) {
            $contents[] = ['pos' => $pos, 'tile' => $tile == null ? null : $tile->serialize() ];
        }
        return [
            'truck_id' => $this->id,
            'taken_by_player_id' => $this->taken_by,
            'contents' => $contents,
        ];
    }
}
