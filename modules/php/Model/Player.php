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

class Player {
    public function __construct(
        public readonly int $id,
        public private(set) int $money,
        int $num_players,
        public private(set) int $purchased_extensions,
        public private(set) int $truck_taken,
        public private(set) int $delivering_truck) {
        $this->extension_limit = $num_players == 2 ? 2 : 1;
    }

    private int $spent = 0;
    private int $received = 0;
    private int $extension_limit;

    public function __toString(): string
    {
        return "Player(id={$this->id},money={$this->money},el={$this->extension_limit},"
             . "pe={$this->purchased_extensions},truck_taken={$this->truck_taken},delivering={$this->delivering_truck},spent={$this->spent},rec={$this->received})";
    }

    public function extensionAvailable(): int {
        if (!$this->canAfford(Cost::EXPAND)) {
            return 0;
        }
        if ($this->purchased_extensions == $this->extension_limit) {
            return 0;
        }
        return $this->purchased_extensions + 1;
    }

    public function moneySpent(): int {
        return $this->spent;
    }

    public function returnTruck(): int {
        if ($this->truck_taken == 0) {
            throw new ModelException("No truck taken by player $this->id");
        }
        $t = $this->truck_taken;
        $this->truck_taken = 0;
        return $t;
    }

    public function startDeliveryForTruck(Truck $truck): void {
        if ($this->delivering_truck != 0) {
            throw new ModelException("Truck already being delivered by player $this->id");
        }
        if ($this->truck_taken != 0) {
            throw new ModelException("Truck already taken by player $this->id");
        }
        $this->delivering_truck = $truck->id;
    }

    public function takeTruck(int $truck_id): void {
        if ($this->truck_taken != 0) {
            throw new ModelException("Truck already taken by player $this->id");
        }
        $this->truck_taken = $truck_id;
    }

    public function takeDeliveringTruck(): void {
        if ($this->truck_taken != 0) {
            throw new ModelException("Truck already taken by player $this->id");
        }
        if ($this->delivering_truck == 0) {
            throw new ModelException("No truck being delivered by player $this->id");
        }
        $this->truck_taken = $this->delivering_truck;
        $this->delivering_truck = 0;
    }

    public function receiveMoney(int $amount): void {
        if ($amount < 0) {
            throw new ModelException("must give nonnegative money not $amount");
        }
        $this->money += $amount;
        $this->received += $amount;
    }

    /** @param int | Cost $val */
    public function payMoney(mixed $val): void {
        $amount = is_int($val) ? intval($val) : $val->amount();

        if ($amount < 0) {
            throw new ModelException("must pay nonnegative money not $amount");
        }
        if ($amount > $this->money) {
            throw new ModelException("player with {$this->money} cannot affort {$amount}");
        }
        $this->money -= $amount;
        $this->spent += $amount;
    }

    /** @return int the number of the extension */
    public function addExtension(): int {
        if ($this->money < Cost::EXPAND->amount()) {
			throw new ModelException("Insufficient funds to buy enclosure");
		}
        if ($this->purchased_extensions >= $this->extension_limit) {
			throw new ModelException("No space for new enclosures");
		}
        return ++$this->purchased_extensions;
    }

    public function canAfford(Cost $cost): bool {
        return $this->money >= $cost->amount();
    }
}
