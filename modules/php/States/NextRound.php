<?php

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * babylonia implementation : © Jay Sachs <vagabond@covariant.org>
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

namespace Bga\Games\zooloretto\States;

use Bga\GameFramework\StateType;
use Bga\Games\zooloretto\Game;

class NextRound extends AbstractState
{
    function __construct(Game $game)
    {
        parent::__construct(
            game: $game,
            id: 6,
            type: StateType::GAME,
            description: clienttranslate('Finishing the round ...'),
            updateGameProgression: true,
        );
    }

    public function onEnteringState(): mixed
    {
        $model = $this->createModel(0);
        $returned = $model->prepareNextRound();
        $this->notify->all(
            "EndRound",
            clienttranslate('Finishing the round'),
            [
                'dumped_tiles' => array_map(fn ($t) => $t->serialize(), $returned['dumpedTiles']),
                'truck_ids_returned' => $returned['truck_ids'],
                'last_round' => $model->getStock()->inLastRound(),
            ]
        );
        if ($model->getStock()->inLastRound()) {
            return ComputeScores::class;
        }
        $this->game->stats->TABLE_TURNS_NUMBER->inc();
        return PlayerTurn::class;
    }
}
