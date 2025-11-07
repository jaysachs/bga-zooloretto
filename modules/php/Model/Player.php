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
    public function __construct(
        public readonly int $id,
        public readonly int $no,
        public private(set) int $money,
        public private(set) int $available_extensions,
        public private(set) int $purchased_extensions,
        public private(set) int $truck_taken) {}

    private int $spent = 0;
    private int $received = 0;
    private const ENCLOSURE_COST = 3;
    private const DISCARD_COST = 2;

    public function canPurchaseExtension(): bool {
        return $this->available_extensions > 0 && $this->money >= SELF::ENCLOSURE_COST;
    }

    public function moneySpent(): int {
        return $this->spent;
    }

    public function discardBarnTile(): void {
        $this->receiveMoney(self::DISCARD_COST);
    }

    public function takeTruck(int $truck_id): void {
        if ($this->truck_taken) {
            throw new ModelException("Truck already taken by player $this->id");
        }
        $this->truck_taken = $truck_id;
    }

    public function receiveMoney(int $amount): void {
        if ($amount < 0) {
            throw new ModelException("must give nonnegative money not $amount");
        }
        $this->money += $amount;
        $this->received += $amount;
    }

    public function payMoney(int $amount): void {
        if ($amount < 0) {
            throw new ModelException("must pay nonnegative money not $amount");
        }
        $this->money -= $amount;
        $this->spent += $amount;
    }

    public function buyEnclosure(): void {
		if ($this->money < self::ENCLOSURE_COST) {
			throw new ModelException("Insufficient funds to buy enclosure");
		}
		if ($this->available_extensions <= 0) {
			throw new ModelException("No space for new enclosures");
		}
        $this->payMoney(self::ENCLOSURE_COST);
        $this->available_extensions--;
        $this->purchased_extensions++;
    }
}
