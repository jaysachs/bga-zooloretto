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

class Enclosure {
    /**
     * @param int $id
     * @param int $capacity
     * @param Tile[] $animals
     */
    public function __construct(public readonly int $id, public readonly int $capacity, public array $animals, public Tile $stall) {
        while (count($animals) < $capacity) {
            $animals[] = null;
        }
        foreach ($animals as $spot) {
            if ($spot != null) {
                $t = $spot->type->value;
                if (!$spot->type->isAnimal()) {
                    throw new \BgaUserException("Enclosure $id should not contain non-animal tile id $spot->id of type $t");
                }
                if ($spot->x != $id) {
                    throw new \BgaUserException("Enclosure $id has animal tile $spot->id but that is in $spot->x");
                }
            }
        }
    }

    public function placeTile(Tile $tile) {
        for ($i = 0; $i < $this->capacity; $i++) {
            if ($this->animals[$i] == null) {
                $this->animals[$i] = $tile;
                return;
            }
        }
        throw new \BgaUserException("No open space in enclosure $this->id");
    }
}
