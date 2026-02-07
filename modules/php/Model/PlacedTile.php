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

class PlacedTile implements Serializable {
    public function __construct(
        public readonly Tile $tile,
        public readonly Space $space,
        public bool $completedEnclosure = false,
        public readonly ?Moneys $money_delta = null,
    ) {}

    /** @return array<string,mixed> */
    public function serialize(): array {
        $result = [
            'tile' => $this->tile->serialize(),
            'space' => $this->space->serialize(),
        ];
        if ($this->money_delta) {
            $result['money_delta'] = $this->money_delta->serialize();
        }
        // FIXME: not needed clientside (but maybe could be?)
        // if ($this->completedEnclosure) {
        //     $result['completed_enclosure'] = $this->completedEnclosure;
        // }
        return $result;
    }

    public function equals(PlacedTile $other): bool {
        return $this->tile == $other->tile
            && $this->space == $other->space
            && $this->completedEnclosure == $other->completedEnclosure
            && $this->money_delta == $other->money_delta
            ;
    }

    public function __toString()
    {
        return "PlacedTile{tile={$this->tile},space={$this->space},completed_enclosure={$this->completedEnclosure}}";
    }
}