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

class Wagon {
    /**
     * @param Tile[] $tiles
     */
    public function __construct(public int $id, public int $capacity, public array $tiles, public WagonStatus $status) {}

    public function setTaken(): void {
        if ($this->status == WagonStatus::AVAILABLE) {
            $this->status = WagonStatus::TAKEN;
        } else {
            throw new \BgaUserException("Attempt to take a wagon in status $this->status");
        }
    }

    /** @return Tile[] */
    public function getTiles(): array {
        return array_filter($this->tiles, function ($t) : bool { return $t != null; });
    }

    public function placeTileAt(Tile $tile, int $pos): void {
        if ($pos < 0 || $pos >= $this->capacity) {
            throw new \BgaUserException("Cannot place tile in position $pos of wagon with capacity $this->capacity");
        }
        if ($this->tiles[$pos] != null) {
            throw new \BgaUserException("Cannot place tile in already occupied wagon position $pos");
        }
        $this->tiles[$pos] = $tile;
    }

    // FIXME: this breaks encapsulation boundaries a bit, but this is probably the best place for this until
    //   the frontend isn't so tightly coupled to this.
    public function valAt(int $pos): string {
        if ($pos >= 0 && $pos < count($this->tiles)) {
            $tile = $this->tiles[$pos];
            return ($tile == null) ? "" : $tile->type->value;
        } else {
            return "";
        }
    }

    public function tileIdAt(int $pos): string {
        if ($pos >= 0 && $pos < count($this->tiles)) {
            $tile = $this->tiles[$pos];
            return ($tile == null) ? "" : "$tile->id";
        } else {
            return "";
        }
    }
}
