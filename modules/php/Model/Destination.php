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

class Destination {
    public function __construct(
        public readonly Space $space,
        public readonly ?Offspring $offspring = null,
        public readonly ?MoneyDelta $moneyDelta = null) {
    }

    public function __toString(): string
    {
        return "es(encid:{$this->space},offspring:{$this->offspring})";
    }

    public function equals(Destination $other): bool
    {
        return $this->space == $other->space && $this->offspring == $other->offspring
         && $this->moneyDelta == $other->moneyDelta;
    }

    public function serialize(): mixed {
		return [
			'space' => $this->space->serialize(),
			'offspring' => $this->offspring ? $this->offspring->serialize() : null,
            'money_delta' => $this->moneyDelta ? $this->moneyDelta->serialize() : null,
		];
	}


}