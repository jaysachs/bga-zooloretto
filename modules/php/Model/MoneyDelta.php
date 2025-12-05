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

class MoneyDelta {
    /** @param int[] $player_delta keyed by player_id */
    public function __construct(
        public readonly int $bank_delta,
        public readonly array $player_delta = [])
    {}

    public static function chargePlayer(int $player_id, Cost $cost): MoneyDelta {
        return new MoneyDelta($cost->amount(), [ $player_id => -$cost->amount() ]);
    }

    public static function payPlayer(int $player_id, int $amt): MoneyDelta {
        return new MoneyDelta(0, [ $player_id => $amt ]);
    }

    public function equals(MoneyDelta $other) {
        return $this->bank_delta == $other->bank_delta
           && $this->player_delta == $other->player_delta;
    }

    public function serialize(): array {
        return [
            'bank' => $this->bank_delta,
            'players' => $this->player_delta,
        ];
    }
}