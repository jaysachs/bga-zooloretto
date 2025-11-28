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
     */
    public function __construct(
        public readonly PositionSet $src,
        public readonly PositionSet $dest
        // FIXME: needs to handle possible children. Add to PositionSet? (and maybe rename that?)
) {
        if (count($src->positions) <> count($dest->positions)) {
            throw new ModelException("Size of src positions and dest positions must be the same");
        }
        if ($src->enclosure_id == $dest->enclosure_id) {
            throw new ModelException("src and dest enclosure_id must be different");
        }
    }

    public function equals(PossibleExchange $other): bool {
        return $this->src == $other->src
            && $this->dest == $other->dest
            ;
    }

    public function __toString(): string
    {
        return "PossibleExchange(" . $this->src . ',' . $this->dest . ")";
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
                    if ($x = self::possibleExchange($src_enc, $dest_enc, $anim)) {
                        $result[] = $x;
                    }
                }
            }
            else if ($x = self::possibleExchange($src_enc, $dest_enc, $dest_enc->animalType())) {
                $result[] = $x;
            }
        }

        return $result;
    }

     private static function possibleExchange(Enclosure $src_enc, Enclosure $dest_enc, TileType $animalType): PossibleExchange | null {
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
        // now we have src and dest positions.
        // now "pad" the smaller one with the "right" positions.
        if (count($src_pos) > count($dest_pos)) {
            $dest_pos = self::normalizeExchangePositions($dest_enc, $dest_pos, $src_enc, $src_pos);
        } else if (count($src_pos) < count($dest_pos)) {
            $src_pos = self::normalizeExchangePositions($src_enc, $src_pos, $dest_enc, $dest_pos);
        }
        // FIXME: check for offspring in both enclosures (which should only happen if one is a barn
        //        otherwise the offspring would've already happened)
        return new PossibleExchange(new PositionSet($src_enc->id, $src_pos), new PositionSet($dest_enc->id, $dest_pos));
    }

    /**
     * @param int[] $smaller_pos
     * @param int[] $larger_pos
     * @return int[]
     */
    private static function normalizeExchangePositions(Enclosure $smaller, array $smaller_pos, Enclosure $larger, array $larger_pos): array {
        $copy = $smaller->clone();
        $tiles = [];
        foreach ($smaller_pos as $pos) {
            $tiles[] = $copy->takeTileAt($pos);
        }
        $newpos = [];
        $i = 0;
        foreach ($larger_pos as $lpos) {
            $pos = 0;
            if ($i < count($smaller_pos)) {
                $pos = $smaller_pos[$i];
                $i++;
            }
            $newpos[] = $copy->placeTile($larger->tileAt($lpos), $pos);
        }

        // verify that $smaller_pos is a "subset" of $newpos
        if (count($smaller_pos) >= count($newpos)) {
            throw new ModelException("should be bigger");
        }
        if (count($newpos) <> count($larger_pos)) {
            throw new ModelException("should be same size as larger");
        }
        $d = array_intersect($smaller_pos, $newpos);
        if (count($d) <> count($smaller_pos)) {
            throw new ModelException("not a subset: " . Utils::arrayToString($smaller_pos)
                                        . " " . Utils::arrayToString($newpos));
        }
        //     throw new ModelException("not a prefix: " . Utils::arrayToString($smaller_pos)
        //                                 . " " . Utils::arrayToString($newpos));

        return $newpos;
    }
}