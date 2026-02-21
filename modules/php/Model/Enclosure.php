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

use Bga\Games\zoolorettoalpha\Utils\Arrays;

class Enclosure {
    /** @var Tile[] */
    private array $contents = [];

    private const int BARN_ID = 0;

    public static function barn(): Enclosure {
        return new Enclosure(self::BARN_ID, 20, 20, 20, 0, 0, 0);
    }

    public static function extension(int $extension_number): Enclosure {
        return self::create(3+$extension_number, 5, 1, 1, 9, 5);
    }

    /** @return list<Enclosure> */
    public static function forPlayer(Player $player): array {
        $encs = [
            self::barn(),
            self::create(1, 5, 1, 2, 8, 5),
            self::create(2, 4, 2, 1, 5, 4),
            self::create(3, 6, 1, 0, 10, 6),
        ];
        for ($i = 1; $i <= $player->purchased_extensions; $i++) {
            $encs[] = self::extension($i);
        }
        return $encs;
    }

    public static function forTest(int $id, int $animal_capacity, int $stall_capacity, int $coin_bonus = 0): Enclosure {
        return self::create($id, $animal_capacity, $stall_capacity, $coin_bonus, 0, 0);
    }

    private static function create(int $id, int $animal_capacity, int $stall_capacity,
            int $coin_bonus,
        int $completion_points,
        int $near_completion_points): Enclosure {
        if ($id <= 0 || $id > 10) {
            throw new ModelException("invalid id {$id} for enclosure");
        }
        return new Enclosure(
            $id,
            $animal_capacity,
            $stall_capacity,
            $animal_capacity + $stall_capacity,
            $coin_bonus,
            $completion_points,
            $near_completion_points
        );
    }

    public function isBarn() : bool {
        return $this->id == Enclosure::BARN_ID;
    }

    public function clone(): Enclosure {
        $e = new Enclosure(
            $this->id,
            $this->animal_capacity,
            $this->stall_capacity,
            $this->total_capacity,
            $this->coin_bonus,
            $this->completion_points,
            $this->near_completion_points);
        foreach ($this->contents as $p => $c) {
            $e->contents[$p] = $c->clone();
        }
        return $e;
    }

    /**
     * @param int $id
     * @param int $animal_capacity
     * @param int $stall_capacity
     */
    private function __construct(
        public readonly int $id,
        readonly int $animal_capacity,
        readonly int $stall_capacity,
        readonly int $total_capacity,
        readonly int $coin_bonus,
        readonly int $completion_points,
        readonly int $near_completion_points) {
        for ($pos = 1; $pos <= $this->total_capacity; $pos++) {
            $this->contents[$pos] = Tile::empty();
        }
    }

    public function availablePos(TileType $type): int {
        if ($type->isAnimal()) {
            return $this->availableAnimalPos($type);
        }
        if ($type->isStall()) {
            return $this->availableStallPos();
        }
        return 0;
    }

    private function availableStallPos(): int {
        if ($this->isBarn()) {
            return $this->availableBarnPos();
        }
        for ($pos = $this->animal_capacity + 1; $pos <= $this->total_capacity; $pos++) {
            if ($this->contents[$pos]->isEmpty()) {
                return $pos;
            }
        }
        return 0;
    }

    private function availableAnimalPos(TileType $type): int {
        if ($this->isBarn()) {
            return $this->availableBarnPos();
        }
        $firstEmpty = 0;
        for ($pos = 1; $pos <= $this->animal_capacity; $pos++) {
            $t = $this->contents[$pos]->type;
            if ($t->isEmpty() && !$firstEmpty) {
                $firstEmpty = $pos;
            }
            if (!$t->isEmpty() && !$type->isSameSpecies($t)) {
                return 0;
            }
        }
        return $firstEmpty;
    }

    private function availableBarnPos(): int {
        for ($pos = 1; $pos <= $this->total_capacity; $pos++) {
            if ($this->contents[$pos]->isEmpty()) {
                return $pos;
            }
        }
        return 0;
    }

    public function animalType(): TileType {
        foreach ($this->contents as $tile) {
            if ($tile->type->isAnimal()) {
                return $tile->type->canonicalType();
            }
        }
        return TileType::EMPTY;
    }

    public function tileAt(int $pos): Tile {
        if ($pos > 0 && $pos <= $this->total_capacity) {
            return $this->contents[$pos];
        }
        throw new ModelException("No position $pos in enclosure $this->id");
    }

    public function takeTileAt(int $pos): Tile {
        if ($pos > 0 && $pos <= $this->total_capacity) {
            $t = $this->contents[$pos];
            if ($t->isEmpty()) {
                throw new ModelException("Attempt to take empty tile in enclosure {$this->id} position {$pos}");
            }
            $this->contents[$pos] = Tile::empty();
            return $t;
        }
        throw new ModelException("No position $pos in enclosure $this->id");
    }

    /** @return Tile[]  where key is position */
    public function nonEmptyContents(): array {
        return array_filter($this->contents, fn ($t) => !$t->isEmpty());
    }

    private function allAnimalPositionsFilled(): bool {
        if ($this->isBarn()) {
            return false;
        }
        for ($pos = 1; $pos <= $this->animal_capacity; $pos++) {
            if ($this->contents[$pos]->isEmpty()) {
                return false;
            }
        }
        return true;
    }

    public function emptyAnimalCount(): int {
        return $this->animal_capacity - count($this->filledAnimalPositions());
    }

    /** @return array<string,int> */
    public function stallTypes(): array {
        $stallTypes = [];
        foreach ($this->nonEmptyContents() as $t) {
            if ($t->type->isStall()) {
                $stallTypes[$t->type->value] = 1;
            }
        }
        return $stallTypes;
    }

    /** @return list<int> positions of animals */
    public function filledAnimalPositions(?TileType $animal = null): array {
        if ($animal) {
            if (!$animal->isAnimal() || $animal->canonicalType() != $animal) {
                throw new ModelException("Can only get filled animal positions of an animal type");
            }
            $animal = $animal->canonicalType();
        }
        return array_keys(array_filter(
            $this->contents,
            fn ($t) => $t->type->isAnimal() && (! $animal || $t->type->canonicalType() == $animal)));
    }

    private function doPlaceTile(Tile $tile, int $pos, int $start, int $end): int {
        if ($pos < 0) {
            throw new ModelException("Illegal position {$pos}");
        }
        if ($pos == 0) {
            $pos = $start;
            while ($pos <= $end && !$this->contents[$pos]->isEmpty()) {
                $pos++;
            }
        }
        if ($pos > $end) {
            throw new ModelException("No room for {$tile->type->value} in enclosure {$this->id}");
        }
        if (!$this->contents[$pos]->isEmpty()) {
            throw new ModelException("Cannot plae tile in non-empt position {$pos} in enclosure {$this->id}");
        }
        $this->contents[$pos] = $tile;
        return $pos;
    }

    /**
     * Positions are assigned starting with 1, animals first, and the consecutively going to stalls.
     * For example, if there are 4 animal positions and 2 stalls,
     * positions 1 through 4 inclusive are for animals, and positions 5 and 6 are for stalls.
     *
     * position 0 means "nextavailable"
     */
    public function placeTile(Tile $tile, int $pos = 0): PlacedTile {
        if (!$tile->type->isPlaceable()) {
            throw new ModelException("Can only place animals and stills in enclosures, not {$tile->type->value}");
        }
        if ($this->isBarn()) {
            // barns never completed
            return new PlacedTile($tile, new Space($this->id, $this->doPlaceTile($tile, $pos, 1, $this->total_capacity)));
        }
        if ($tile->type->isAnimal()) {
            $pos = $this->doPlaceTile($tile, $pos, 1, $this->animal_capacity);
            return new PlacedTile($tile, new Space($this->id, $pos), $this->allAnimalPositionsFilled());
        }
        if ($tile->type->isStall()) {
            // stalls do not complete
            return new PlacedTile($tile, new Space($this->id, $this->doPlaceTile($tile, $pos, $this->animal_capacity + 1, $this->total_capacity)));
        }
        throw new ModelException("Unexpected tile type {$tile->type->value}");
    }

    public function checkForOffspring(Enclosure $barn): ?Offspring {
        if ($this->isBarn()) {
            return null;
        }
        $mp = 0;
        $fp = 0;
        // FIXME: this only checks for one pair
        foreach ($this->contents as $pos => $tile) {
            if ($tile->isFertileMale() && $fp == 0) {
                $fp = $pos;
            } else if ($tile->isFertileFemale() && $mp == 0) {
                $mp = $pos;
            }
        }
        if ($mp == 0 || $fp == 0) {
            return null;
        }


        $mother = $this->contents[$mp];
        $father = $this->contents[$fp];
        // this will work: baby ID is 300 more than parent ID
        $child = new Tile($mother->id * 10000 + $father->id, $mother->type->childType());
        $mother = $mother->clone()->markReproduced();
        $father = $father->clone()->markReproduced();
        $this->contents[$fp] = $father;
        $this->contents[$mp] = $mother;

        $space = ($this->availablePos($child->type) == 0)
            ? $barn->placeTile($child)->space
            : $this->placeTile($child)->space;
        $completed = ($space->enclosure_id == $this->id) && $this->allAnimalPositionsFilled();
        return new Offspring(new PlacedTile($child, $space, $completed), $mother, $father);
    }

    public function __toString(): string
    {
        $contents = Arrays::arrayToString(array_filter($this->contents, fn ($t) => !$t->isEmpty()), true);
        return "Enclosure(id=$this->id,animal_capacity=$this->animal_capacity,stall_cap=$this->stall_capacity,contents=$contents)";
    }

    public function translatedId(): string {
        return self::translated($this->id);
    }

    public static function translated(int $enclosure_id): string {
        return match ($enclosure_id) {
            0 => clienttranslate("the barn"),
            1 => clienttranslate("enclosure 1"),
            2 => clienttranslate("enclosure 2"),
            3 => clienttranslate("enclosure 3"),
            4 => clienttranslate("enclosure 4"),
            5 => clienttranslate("enclosure 5"),
            default => "??"
        };
    }
}
