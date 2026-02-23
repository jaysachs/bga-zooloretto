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

namespace Bga\Games\zoolorettoalpha\States;

use Bga\GameFramework\StateType;
use Bga\Games\zoolorettoalpha\Game;

class ComputeScores extends AbstractState
{
    function __construct(Game $game)
    {
        parent::__construct(
            game: $game,
            id: 90,
            type: StateType::GAME,
        );
    }

    public function onEnteringState(): mixed
    {
        $model = $this->createModel(0);
        $scoreDetailsByPlayerId = $model->computeScores();
        foreach ($scoreDetailsByPlayerId as $pid => $scores) {
            $this->game->stats->PLAYER_TOTALPOINTS->set($pid, $scores['total']);
            $this->game->stats->PLAYER_STALLPOINTS->set($pid, $scores['stall_points']);
            $this->game->stats->PLAYER_BARNPENALTY->set($pid, $scores['barn_stall_points'] + $scores['barn_animal_points']);
            $this->game->stats->PLAYER_POINTSFORFULLENCLOSURES->set($pid, $scores['full_enclosure_points']);
            $this->game->stats->PLAYER_POINTSFORNEARFULLENCLOSURES->set($pid, $scores['near_full_enclosure_points']);
            $this->game->stats->PLAYER_POINTSFORINCOMPLETEENCLOSURES->set($pid, $scores['other_enclosure_points']);
            $this->game->stats->PLAYER_FULLENCLOSURES->set($pid, $scores['full_enclosures']);
            $this->game->stats->PLAYER_NEARFULLENCLOSURES->set($pid, $scores['near_full_enclosures']);
            $this->game->stats->PLAYER_INCOMPLETEENCLOSURES->set($pid, $scores['other_enclosures']);

            $this->game->bga->playerScore->set($pid, $scores['total']);
            $this->game->bga->playerScoreAux->set($pid, $scores['money']);
        }
        // FIXME: include winner(s) in message?
        $this->notify->all('ShowFinalScores', clienttranslate('Final scores'), [
            'endScores' => $scoreDetailsByPlayerId,
        ]);
        return 99;
    }

}
