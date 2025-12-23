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

class Scorer {
    /**
     * @param array<int,Enclosure> $encs
     * @return array{'player_id':int,'money':int,'full_enclosures':int,'full_enclosure_points':int,'near_full_enclosures':int,'near_full_enclosure_points':int,'other_enclosures':int,'other_enclosure_points':int,'barn_stall_types':int,'barn_animal_types':int,'barn_stall_points':int,'barn_animal_points':int,'stall_points':int,'total':int}
     */
    public static function scoreForPlayer(Player $player, array $encs): array {
        $detail = [
            'player_id' => $player->id,
            'money' => $player->money,
            'full_enclosures' => 0,
            'full_enclosure_points' => 0,
            'near_full_enclosures' => 0,
            'near_full_enclosure_points' => 0,
            'other_enclosures' => 0,
            'other_enclosure_points' => 0,
            'barn_stall_types' => 0,
            'barn_animal_types' => 0,
            'barn_stall_points' => 0,
            'barn_animal_points' => 0,
            'stall_points' => 0,
        ];
        $stall_types = [];
        foreach ($encs as $enc) {
            $sts = $enc->stallTypes();
            if ($enc->isBarn()) {
                $barnAnimalTypes = [];
                $barnStallTypes = [];
                foreach ($enc->nonEmptyContents() as $tile) {
                    if ($tile->type->isAnimal()) {
                        $barnAnimalTypes[$tile->type->value] = 1;
                    } else { // if $tile->type->isStall() {
                        $barnStallTypes[$tile->type->value] = 1;
                    }
                }
                $detail['barn_stall_types'] = count($barnStallTypes);
                $detail['barn_animal_types'] = count($barnAnimalTypes);
                $detail['barn_stall_points'] = -2 * count($barnStallTypes);
                $detail['barn_animal_points'] = -2 * count($barnAnimalTypes);
            } else {
                foreach (array_keys($sts) as $st) {
                    $stall_types[$st] = 1;
                }
                switch ($enc->emptyAnimalCount()) {
                    case 0:
                        $detail['full_enclosures']++;
                        $detail['full_enclosure_points'] += $enc->completion_points;
                        break;
                    case 1:
                        $detail['near_full_enclosures']++;
                        $detail['near_full_enclosure_points'] += $enc->near_completion_points;
                        break;
                    default:
                        if (count($sts) > 0) {
                            $detail['other_enclosures']++;
                            $detail['other_enclosure_points'] += count($enc->filledAnimalPositions());
                        }
                }
            }
        }
        $detail['stall_points'] = 2 * count($stall_types);
        $detail['total'] =
              $detail['full_enclosure_points']
            + $detail['near_full_enclosure_points']
            + $detail['other_enclosure_points']
            + $detail['stall_points']
            + $detail['barn_stall_points']
            + $detail['barn_animal_points'];

        return $detail;
    }

}
