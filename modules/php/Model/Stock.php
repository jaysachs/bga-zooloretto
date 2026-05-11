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

class Stock {

    /** @var list<Tile> */
    private array $primary;
    /** @var list<Tile> */
    private array $endgame;

    /**
     * @param list<Tile> $undrawn_tiles
     */
    public function __construct(array $undrawn_tiles, public private(set) Tile $drawn) {
        $this->endgame = array_splice($undrawn_tiles, -self::LASTSET_SIZE);
        $this->primary  = $undrawn_tiles;
    }

    public function primaryCount(): int {
        return count($this->primary) + ((!$this->drawn->isEmpty() && !$this->inLastRound()) ? 1 : 0);
    }

    public function endgameCount(): int {
        return count($this->endgame) + ((!$this->drawn->isEmpty() && $this->inLastRound()) ? 1 : 0);
    }

    public function removeDrawnTile(): Tile {
        if ($this->drawn->isEmpty()) {
            throw new ModelException("Attmpt to remove a drawn tile but none drawn");
        }
        $tile = $this->drawn;
        $this->drawn = Tile::empty();
        return $tile;
    }

    public function drawTile(): Tile {
        if (!$this->drawn->isEmpty()) {
            throw new ModelException("Attmpt to draw a 2nd tile");
        }
        $tile = array_shift($this->primary);
        if ($tile == null) {
            $tile = array_shift($this->endgame);
            if ($tile == null) {
                throw new ModelException("No tiles left!");
            }
        }
        $this->drawn = $tile;
        return $tile;
    }

    public const int LASTSET_SIZE = 15;

    public function waslastRoundTriggered(): bool {
        return !$this->drawn->isEmpty() && count($this->endgame) == self::LASTSET_SIZE - 1;
    }

    public function inLastRound(): bool {
        return count($this->endgame) < self::LASTSET_SIZE;
    }
}
