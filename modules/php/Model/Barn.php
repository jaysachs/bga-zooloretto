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

class Barn {
    /** @param Tile[] $tiles */
    public function __construct(public readonly int $player_id, public array $tiles) { }

    public array $discarded = [];

    public function discard(int $tileid): Tile {
        for ($i = 0; $i < count($this->tiles); $i++) {
            $tile = $this->tiles[$i];
            if ($tile == null) {
                continue;
            }
            if ($tile->id == $tileid) {
                $this->discarded[] = $tile;
                array_splice($this->tiles, $i, 1);
                return $tile;
            }
        }
        throw new ModelException("Attempt to discard tile $tileid from player $this->player_id barn but it's not there");
    }
}
