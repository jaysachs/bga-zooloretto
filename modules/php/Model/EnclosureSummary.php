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

class EnclosureSummary implements Serializable {
    public function __construct(
        public readonly int $player_id,
        public readonly int $enclosure_id,
        public readonly TileType $animal_type,
        public readonly int $count) {}

    public static function forEnclosure(int $player_id, Enclosure $enc): EnclosureSummary {
        if ($enc->isBarn()) {
            return new EnclosureSummary($player_id, 0, TileType::EMPTY, 0);
        }
        $at = $enc->animalType();
        $count = 0;
        if ($at->isAnimal()) {
            $count = count($enc->filledAnimalPositions($at));
        }
        return new EnclosureSummary($player_id, $enc->id, $enc->animalType(), $count);
    }

    /** @return array{player_id: int, enclosure_id: int, animal_type: string, count: int} */
    public function serialize() : array {
        return [
            'player_id' => $this->player_id,
            'enclosure_id' => $this->enclosure_id,
            'animal_type' => $this->animal_type->canonicalType()->value,
            'animal_description' => $this->animal_type->translated(),
            'count' => $this->count,
        ];
    }
}