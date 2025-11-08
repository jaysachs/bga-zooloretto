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

use Bga\Games\zooloretto\Utils;

class Stock {
    /**
     * The available tiles. Both arrays are keyed by the tile ID.
     *
     * @param $tiles Tile[]
     * @param $lastset Tile[]
     */
    public function __construct(private array $primary, private array $endgame, public private(set) ?Tile $drawn) {
    }

    public function primaryCount(): int {
        return count($this->primary);
    }

    public function endgameCount(): int {
        return count($this->endgame);
    }

    /** @return int[] */
    public function primaryIds(): array {
        return array_map(fn (Tile $t):int => $t->id, $this->primary);
    }

    /** @return int[] */
    public function endgameIds(): array {
        return array_map(fn (Tile $t):int => $t->id, $this->endgame);
    }

    public function removeDrawnTile(): Tile {
        if ($this->drawn == null) {
            throw new ModelException("Attmpt to remove a drawn tile but none drawn");
        }
        $tile = $this->drawn;
        $this->drawn = null;
        return $tile;
    }

    public function lastDrawFromEndgamePile(): bool {
        return $this->waslastRoundTriggered() || count($this->primary) == 0;
    }

    public function drawTile(): Tile {
        if ($this->drawn != null) {
            throw new ModelException("Attmpt to draw a 2nd tile");
        }
        $tile = array_shift($this->primary);
        if ($tile == null) {
            $tile = array_shift($this->endgame);
            if ($tile == null) {
                throw new ModelException("No tiles left!");
            }
        }
        $this->drawn = $tile;
        return $tile;
    }

    private const LASTSET_SIZE = 15;

    public function waslastRoundTriggered(): bool {
        return $this->drawn != null && count($this->endgame) == self::LASTSET_SIZE - 1;
    }

    public function inLastRound(): bool {
        return count($this->endgame) < self::LASTSET_SIZE;
    }

    /**
     * @return Tile[]
     */
    public function all(): array {
        return array_merge($this->primary, $this->endgame);
    }

    /** @param Tile[] $pool */
    public static function create(array $pool): Stock {
        $values = array_values($pool);

        Utils::shuffle($values);
        $lastset = array_splice($values, 0, self::LASTSET_SIZE);

        return new Stock($values, $lastset, null);
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
