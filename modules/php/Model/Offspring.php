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

class Offspring implements Serializable {
    public function __construct(
        public readonly PlacedTile $child,
        public readonly Tile $mother,
        public readonly Tile $father) {}

    public function __toString()
    {
        return "Offspring(child={$this->child},mother={$this->mother},father={$this->father})";
    }

    public function equals(Offspring $other): bool {
        return $this->child == $other->child
            && $this->mother == $other->mother
            && $this->father == $other->father
            ;
    }

    /** @return array<string,mixed> */
    public function serialize(): array {
        return [
			'placed_tile' => $this->child->serialize(),
            'mother' => $this->mother->serialize(),
            'father' => $this->father->serialize(),
		];

    }
}