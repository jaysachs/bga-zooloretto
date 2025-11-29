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

class PossibleExchange {
    /**
     * @param int[] $src_positions
     * @param int[] $dest_positions
     * @param Space[] $children
     */
    public function __construct(
        public readonly int $src_enclosure_id,
        public readonly array $src_positions,
        public readonly int $dest_enclosure_id,
        public readonly array $dest_positions,
        // FIXME: not included in equals/toString, only needed as "output". Maybe move to a separate object.
        public readonly array $children,
) {
        if (count($src_positions) <> count($dest_positions)) {
            throw new ModelException("Size of src positions and dest positions must be the same");
        }
        if ($src_enclosure_id == $dest_enclosure_id) {
            throw new ModelException("src and dest enclosure_id must be different");
        }
    }

    public function matches(PossibleExchange $other): bool {
        return $this->src_enclosure_id == $other->src_enclosure_id
            && count(array_diff($this->src_positions, $other->src_positions)) == 0
            && $this->dest_enclosure_id == $other->dest_enclosure_id
            && count(array_diff($this->dest_positions, $other->dest_positions)) == 0
            ;
    }

    public function __toString(): string
    {
        return "PossEx(src:{$this->src_enclosure_id} " . Utils::arrayToString($this->src_positions)
                  . "dest:{$this->dest_enclosure_id} " . Utils::arrayToString($this->src_positions) . ")";
    }

    /**
     * @param Enclosure[] $encs
     * @return PossibleExchange[]
     */
    public static function getPossibleExchanges(array $encs) : array {
        $result = [];
        foreach ($encs as $enc) {
            // we can skip the barn, since every exchange must involve a "real" enclosure,
            //   only let "real" enclosures be the source.
            //   This simplifies the logic in possibleExchangesFor().
            if (!$enc->isBarn()) {
                $result = array_merge($result, self::possibleExchangesFor($enc, $encs));
            }
        }
        return $result;
    }

    /**
     * @param Enclosure[] $encs
     * @return PossibleExchange[]
     */
    private static function possibleExchangesFor(Enclosure $src_enc, array $encs) : array {
        if ($src_enc->isBarn()) {
            throw new ModelException("barns cannot be exchange sources");
        }
        $result = [];
        $animalType = $src_enc->animalType();
        if ($animalType->isEmpty()) {
            return $result;
        }

        foreach ($encs as $dest_enc) {
            if ($dest_enc->id == $src_enc->id) {
                continue;
            }
            if ($dest_enc->isBarn()) {
                foreach (TileType::allCanonicalAnimals() as $anim) {
                    if ($x = self::possibleExchange($src_enc, $dest_enc, $anim, $encs[0])) {
                        $result[] = $x;
                    }
                }
            }
            else if ($x = self::possibleExchange($src_enc, $dest_enc, $dest_enc->animalType(), $encs[0])) {
                $result[] = $x;
            }
        }

        return $result;
    }

     private static function possibleExchange(Enclosure $src_enc, Enclosure $dest_enc, TileType $animalType, Enclosure $barn): PossibleExchange | null {
        if ($animalType->isEmpty()) {
            // no animals of that type at destination
            return null;
        }
        if (!$animalType->isAnimal()) {
            throw new ModelException("tile type {$animalType->value} is not an animal");
        }
        if ($src_enc->animalType()->isSameSpecies($animalType)) {
            return null;
        }
        $src_pos = $src_enc->filledAnimalPositions();
        $dest_pos = $dest_enc->filledAnimalPositions($animalType);
        if (count($src_pos) > $dest_enc->animal_capacity) {
            // no room in the destination enclosure
            return null;
        }
        if (count($dest_pos) == 0) {
            // no animals in destination
            return null;
        }
        if (count($dest_pos) > $src_enc->animal_capacity) {
            // not enough room in src enc for all the dest animals
            return null;
        }

        $src_enc = $src_enc->clone();
        $dest_enc = $dest_enc->clone();
        $barn = $barn->clone();

        $srcTiles = [];
        foreach ($src_pos as $pos) {
            $srcTiles[] = $src_enc->takeTileAt($pos);
        }
        $destTiles = [];
        foreach ($dest_pos as $pos) {
            $destTiles[] = $dest_enc->takeTileAt($pos);
        }

        $i = 0;
        foreach ($srcTiles as $tile) {
            $p = $i < count($dest_pos) ? $dest_pos[$i] : 0;
            $dest_pos[$i++] = $dest_enc->placeTile($tile, $p)->pos;
        }
        $i = 0;
        foreach ($destTiles as $tile) {
            $p = $i < count($src_pos) ? $src_pos[$i] : 0;
            $src_pos[$i++] = $src_enc->placeTile($tile, $p)->pos;
        }

        $spaces = [];
        $offspring = $src_enc->checkForOffspring($barn);
        if ($offspring) {
            $spaces[] = $offspring->childSpace;
        }
        $offspring = $dest_enc->checkForOffspring($barn);
        if ($offspring) {
            $spaces[] = $offspring->childSpace;
        }
        return new PossibleExchange($src_enc->id, $src_pos, $dest_enc->id, $dest_pos, $spaces);
    }
}