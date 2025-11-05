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

    public function __construct(
        public readonly int $id,
        public ?Tile $tile1 = null,
        public ?Tile $tile2 = null,
        public ?Tile $tile3 = null,
        public int $taken_by = 0) {
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

    public function freeSpaces(): int {
        return ($this->tile1 == null ? 1 : 0) + ($this->tile3 == null ? 1 : 0) + ($this->tile3 == null ? 1 : 0);
    }

    /**
     * @param $pos 1-based position on the truck
     */
    public function placeTileAt(Tile $tile, int $pos): void {
        switch ($pos) {
        case 1:
            if ($this->tile1 != null) {
                throw new ModelException("Cannot place tile in already occupied truck position $pos");
            }
            $this->tile1 = $tile;
            break;
        case 2:
            if ($this->tile2 != null) {
                throw new ModelException("Cannot place tile in already occupied truck position $pos");
            }
            $this->tile2 = $tile;
            break;
        case 3:
            if ($this->tile3 != null) {
                throw new ModelException("Cannot place tile in already occupied truck position $pos");
            }
            $this->tile3 = $tile;
            break;
        default:
            throw new ModelException("Cannot place tile in position $pos of truck");
        }
    }
}
