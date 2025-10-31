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

class Player {
    public function __construct(public readonly int $id, public readonly int $no, public int $money, public int $available_enclosures) {}

    private int $spent = 0;
    private const ENCLOSURE_COST = 3;

    public function moneySpent(): int {
        return $this->spent;
    }

    public function buyEnclosure(): void {

		if ($this->money < self::ENCLOSURE_COST) {
			throw new \BgaUserException("Insufficient funds to buy enclosure");
		}
		if ($this->available_enclosures <= 0) {
			throw new \BgaUserException("No space for new enclosures");
		}
        $this->money -= self::ENCLOSURE_COST;
        $this->spent += self::ENCLOSURE_COST;
        $this->available_enclosures -= 1;
    }
}
