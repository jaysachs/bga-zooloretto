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
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;


/*
    4 => array(
    		"name" => "NextPlayer",
    		"description" => clienttranslate('Changing player...'),
    		"type" => "game",
			"action" => "stNextPlayer",
			"updateGameProgression" => true,
    		"transitions" => array( "NextPlayer" => 2, "NextTurn" => 6, "GameEnd" => 99)
    ),
*/

class NextPlayer extends GameState
{
    function __construct(private Game $game)
    {
        parent::__construct(
            game: $game,
            id: 4,
            type: StateType::GAME,
            description: clienttranslate('Changing player...'),
            updateGameProgression: true,
        );
    }

    public function onEnteringState(): mixed
    {
		$count = $this->game->getUniqueValueFromDB("select count(*) from player where skipped='N'" );

		if (intval($count)>0)
		{
			$player_id = $this->game->getActivePlayerId();
			$this->game->giveExtraTime( $player_id );
			$found = false;
			while (!$found)
			{
				$this->activeNextPlayer();
				$player_id = $this->game->getActivePlayerId();
				$count = $this->game->getUniqueValueFromDB("select count(*) from player where skipped='N' and player_id='$player_id'" );
				if (intval($count)>0)
				{
					$found = true;
				}
			}
            return PlayerTurn::class;
		}
		else
		{
            return NextTurn::class;
		}
    }
}
