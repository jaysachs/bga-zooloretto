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

    3 => array(
    		"name" => "PlaceTile",
    		"description" => clienttranslate('${actplayer} must place a tile on a Wagon.'),
    		"descriptionmyturn" => clienttranslate('${you} must place a tile on a Wagon.'),
    		"type" => "activeplayer",
    		"possibleactions" => array( "PlaceTile" ),
    		"transitions" => array( "NextPlayer" => 4 )
    ),
*/

class PlaceTile extends GameState
{
    function __construct(private Game $game)
    {
        parent::__construct(
            game: $game,
            id: 3,
            type: StateType::ACTIVE_PLAYER,
    		description: clienttranslate('${actplayer} must place a tile on a Wagon.'),
    		descriptionMyTurn: clienttranslate('${you} must place a tile on a Wagon.'),
        );
    }

    public function getArgs(int $active_player_id): array
    {
        return [];
    }

    #[PossibleAction]
    public function actPlaceTile(string $x, string $y): mixed {
		$id = $this->game->getUniqueValueFromDB("select id from animals where status='DRAWN'" );
		$player_id = $this->game->getCurrentPlayerId();
		$player_no = $this->game->getUniqueValueFromDB("select player_no from player where player_id ='$player_id'" );
		$val = $this->game->getUniqueValueFromDB("select val from animals where id ='$id'" );

		$sql = "update animals set x = $x, y = $y, status = 'WAGON' where id = '$id'";
		$this->game->DbQuery( $sql );
		$sql = "update wagons set val$y = $id where id = '$x'";
		$this->game->DbQuery( $sql );


		$this->game->notifyAllPlayers( "PlaceTile", clienttranslate( '${player_name} placed the ${translatedval} tile on the ${pos} space of the ${wag} wagon.'),
		array(
			'player_id' => $player_id,
			'player_no' => $player_no,
			'id' => $id,
			'val' => $val,
			'x' => $x,
			'y' => $y,
			'translatedval' => $this->DecodeAnimal($val),
			'pos' => $this->DecodePos($y),
			'wag' => $this->DecodePos($x),
			'player_name' => $this->game->getCurrentPlayerName(),
			'i18n' => array( 'translatedval', 'pos', 'wag' )
		) );
        return NextPlayer::class;
    }

    function zombie(int $playerId): mixed {
        // FIXME
        return "";
    }
}
