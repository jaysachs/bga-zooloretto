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

class Deck {
    /**
     * The available tiles. Both arrays are keyed by the tile ID.
     *
     * @param $tiles Tile[]
     * @param $lastset Tile[]
     */
    public function __construct(public array $tiles, public array $lastset, public ?Tile $drawn) {
        shuffle($this->tiles);
        shuffle($this->lastset);
    }

    public function drawTile(): Tile {
        if ($this->drawn != null) {
            throw new \BgaUserException("Attmpt to draw a 2nd tile");
        }
        $tile = array_shift($this->tiles);
        if ($tile == null) {
            $tile = array_shift($this->lastset);
            if ($tile == null) {
                throw new \BgaUserException("No tiles left!");
            }
        }
        $this->drawn = $tile;
        return $tile;
    }

    private const LASTSET_SIZE = 15;

    public function waslastRoundTriggered(): bool {
        return $this->drawn != null && count($this->lastset) == self::LASTSET_SIZE - 1;
    }

    public function inLastRound(): bool {
        return count($this->lastset) < self::LASTSET_SIZE;
    }

    /**
     * @return Tile[]
     */
    public function all(): array {
        return array_merge($this->tiles, $this->lastset);
    }

    public static function create(int $player_count): Deck {
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

        shuffle($values);
        $lastset = array_splice($values, 0, self::LASTSET_SIZE);

        return new Deck($values, $lastset, null);
    }

    public static function shuffle(array &$arr): void {
        $e = sizeof($arr) - 1;
        for ($i = 0; $i < $e; ++$i) {
            $j = random_int($i, $e);
            if ($j <> $i) {
                $tmp = $arr[$j];
                $arr[$j] = $arr[$i];
                $arr[$i] = $tmp;
            }
        }
    }

}

/*
$deck = Deck::create(2);
var_dump($deck);

$make = function (Tile $tile): string {
			$tv = $tile->type->value;
			$id = $tile->id;
			return "($id,'','','','$tv','AVAILABLE')";
		};
		$values = array_merge(
			array_map(function (Tile $tile) use (&$make): string { return $make($tile, 'AVAILABLE'); }, $deck->tiles),
			array_map(function (Tile $tile)use (&$make): string { return $make($tile, 'LASTSET'); }, $deck->lastset)
		);
        var_dump($values);
        */