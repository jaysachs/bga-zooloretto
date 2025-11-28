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

class Tile {

	private static ?Tile $EMPTY = null;
	public static function empty() : Tile {
		if (self::$EMPTY == null) {
			self::$EMPTY = new Tile(0, TileType::EMPTY);
		}
		return self::$EMPTY;
	}

    public function __construct(public readonly int $id, public private(set) TileType $type) {}

	/** @return Tile[] */
    public static function createInitialPool(int $player_count): array {
        $values = [];
		for ($x = 1; $x <= 7; $x++) {
			$values[] = new Tile($x, TileType::CAMEL);
		}
		for ($x = 8; $x <= 9; $x++) {
			$values[] = new Tile($x, TileType::CAMEL_MALE);
		}
		for ($x = 10; $x <= 11; $x++) {
			$values[] = new Tile($x, TileType::CAMEL_FEMALE);
		}

		for ($x = 12; $x <= 18; $x++) {
			$values[] = new Tile($x, TileType::ELEPHANT);
		}
		for ($x = 19; $x <= 20; $x++) {
			$values[] = new Tile($x, TileType::ELEPHANT_MALE);
		}
		for ($x = 21; $x <= 22; $x++) {
			$values[] = new Tile($x, TileType::ELEPHANT_FEMALE);
		}
		for ($x = 23; $x <= 29; $x++) {
			$values[] = new Tile($x, TileType::FLAMINGO);
		}
		for ($x = 39; $x <= 31; $x++) {
			$values[] = new Tile($x, TileType::FLAMINGO_MALE);
		}
		for ($x = 32; $x <= 33; $x++) {
			$values[] = new Tile($x, TileType::FLAMINGO_FEMALE);
		}
		for ($x = 34; $x <= 40; $x++) {
			$values[] = new Tile($x, TileType::KANGAROO);
		}
		for ($x = 41; $x <= 42; $x++) {
			$values[] = new Tile($x, TileType::KANGAROO_MALE);
		}
		for ($x = 43; $x <= 44; $x++) {
			$values[] = new Tile($x, TileType::KANGAROO_FEMALE);
		}
		for ($x = 45; $x <= 51; $x++) {
			$values[] = new Tile($x, TileType::LEOPARD);
		}
		for ($x = 52; $x <= 53; $x++) {
			$values[] = new Tile($x, TileType::LEOPARD_MALE);
		}
		for ($x = 54; $x <= 55; $x++) {
			$values[] = new Tile($x, TileType::LEOPARD_FEMALE);
		}

		if ($player_count >= 3) {
			for ($x = 56; $x <= 62; $x++) {
				$values[] = new Tile($x, TileType::MONKEY);
			}
			for ($x = 63; $x <= 64; $x++) {
				$values[] = new Tile($x, TileType::MONKEY_MALE);
			}
			for ($x = 65; $x <= 66; $x++) {
				$values[] = new Tile($x, TileType::MONKEY_FEMALE);
			}
		}

		if ($player_count >= 4) {
			for ($x = 67; $x <= 73; $x++) {
				$values[] = new Tile($x, TileType::PANDA);
			}
			for ($x = 74; $x <= 75; $x++) {
				$values[] = new Tile($x, TileType::PANDA_MALE);
			}
			for ($x = 76; $x <= 77; $x++) {
				$values[] = new Tile($x, TileType::PANDA_FEMALE);
			}
		}

		if ($player_count >= 5) {
			for ($x = 78; $x <= 84; $x++) {
				$values[] = new Tile($x, TileType::ZEBRA);
			}
			for ($x = 85; $x <= 86; $x++) {
				$values[] = new Tile($x, TileType::ZEBRA_MALE);
			}
			for ($x = 87; $x <= 88; $x++) {
				$values[] = new Tile($x, TileType::ZEBRA_FEMALE);
			}
		}

		for ($x = 89; $x <= 100; $x++) {
			$values[] = new Tile($x, TileType::COIN);
		}

		for ($x = 101; $x <= 103; $x++) {
			$values[] = new Tile($x, TileType::KIOSK);
		}
		for ($x = 104; $x <= 106; $x++) {
			$values[] = new Tile($x, TileType::BARROW);
		}
		for ($x = 107; $x <= 109; $x++) {
			$values[] = new Tile($x, TileType::SNACKS);
		}
		for ($x = 110; $x <= 112; $x++) {
			$values[] = new Tile($x, TileType::POPCORN);
		}

        return $values;
    }

	public function isEmpty(): bool {
		return $this->type->isEmpty();
	}

	public function markReproduced(): Tile {
		if ($this->type->isFertileFemale() || $this->type->isFertileMale()) {
			$this->type = $this->type->reproducedType();
			return $this;
		}
		throw new ModelException("Cannot mark tile of type {$this->type} as reproduced");
	}

	public function __toString()
	{
		return "Tile({$this->id},{$this->type->value})";
	}

	public function clone(): Tile {
		return new Tile($this->id, $this->type);
	}

	public function equals(Tile $other): bool {
		return $this->id == $other->id && $this->type == $other->type;
	}
}
