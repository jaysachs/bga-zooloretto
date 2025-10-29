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
use Bga\Games\zooloretto\Game;


/*
    6 => array(
    		"name" => "NextTurn",
    		"description" => clienttranslate('Changing player...'),
    		"type" => "game",
			"action" => "stNextTurn",
			"updateGameProgression" => true,
    		"transitions" => array( "NextPlayer" => 2, "GameEnd" => 99)
    ),
*/

class NextTurn extends GameState
{
    function __construct(private Game $game)
    {
        parent::__construct(
            game: $game,
            id: 6,
            type: StateType::GAME,
            description: clienttranslate('Changing player...'),
            updateGameProgression: true,
        );
    }

    public function onEnteringState(): mixed
    {
		$sql = "update animals set status = 'DISCARDED' where status = 'WAGON'";
		$this->game->DbQuery( $sql );
		$sql = "update wagons set status = 'AVAILABLE', val1='', val2='', val3=''";
		$this->game->DbQuery( $sql );
		$sql = "update player set skipped='N'";
		$this->game->DbQuery( $sql );

		$wagons = $this->game->getObjectListFromDB( "SELECT id, val1, val2, val3, status, size from wagons" );

		$lastround = $this->game->getUniqueValueFromDB("select distinct lastround from player" );

		if ($lastround=="N")
		{
			$this->game->notifyAllPlayers( "EndTurn", clienttranslate( 'Turn is over... starting another turn.'),
			array(
				'wagons' => $wagons,
			) );

            return NextPlayer::class;
		}
		else
		{
			$this->CalculateScore();
            // TODO: better end game indication?
            return 99;
		}
    }
}
