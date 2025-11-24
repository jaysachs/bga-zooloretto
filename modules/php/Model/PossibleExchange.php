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

class PossibleExchange {
    /**
     * @param int[] $src_positions
     * @param int[] $dest_positions
     */
    public function __construct(
        public readonly PositionSet $src,
        public readonly PositionSet $dest
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
        // FIXME: this check might be redundant with the one immediately following.
        if ($animalType->isEmpty()) {
            return $result;
        }

        foreach ($encs as $enc) {
            if ($enc->id == $src_enc->id) {
                continue;
            }
            if ($enc->isBarn()) {
                foreach (TileType::allCanonicalAnimals() as $anim) {
                    if ($x = self::possibleExchange($src_enc, $enc, $anim)) {
                        $result[] = $x;
                    }
                }
            }
            else if ($x = self::possibleExchange($src_enc, $enc, $anim)) {
                $result[] = $x;
            }
        }

        return $result;
    }

     private static function possibleExchange(Enclosure $src_enc, Enclosure $dest_enc, TileType $animalType): PossibleExchange | null {
        $src_pos = $src_enc->filledAnimalPositions($animalType);
        if (count($src_pos) > $dest_enc->animal_capacity) {
            // no room in the destination enclosure
            return null;
        }
        $dest_pos = $dest_enc->filledAnimalPositions();
        if (count($dest_pos) == 0) {
            // no animals in destination
            return null;
        }
        if ($dest_enc->animalType()->isSameSpecies($animalType)) {
            // no animals, or the same animal, in this enclosure.
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
        foreach ($larger_pos as $pos) {
            $newpos[] = $copy->placeTile($larger->tileAt($pos));
        }
        // FIXME: verify that $smaller_pos is a "prefix" of $newpos
        return $newpos;
    }
}