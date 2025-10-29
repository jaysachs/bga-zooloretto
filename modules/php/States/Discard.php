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
use Bga\Games\zooloretto\Decoder;
use Bga\Games\zooloretto\Game;


/*

    10 => array(
    		"name" => "Discard",
    		"description" => clienttranslate('${actplayer} must discard a tile from his Barn.'),
    		"descriptionmyturn" => clienttranslate('${you} must discard a tile from your Barn.'),
    		"type" => "activeplayer",
    		"possibleactions" => array( "Discard", "Back" ),
    		"transitions" => array( "Back" => 2, "NextPlayer" => 4 )
    ),
*/

class Discard extends GameState
{
    function __construct(private Game $game)
    {
        parent::__construct(
            game: $game,
            id: 10,
            type: StateType::ACTIVE_PLAYER,
    		description: clienttranslate('${actplayer} must discard a tile from his Barn.'),
            descriptionMyTurn: clienttranslate('${you} must discard a tile from your Barn.'),
        );
    }

    public function getArgs(int $active_player_id): array
    {
        return [];
    }

    #[PossibleAction]
    public function actConfirmDiscard(
        string $titleid
    ): mixed {
		$player_id = $this->game->getCurrentPlayerId();
		$player_no = $this->game->getUniqueValueFromDB("select player_no from player where player_id ='$player_id'" );
		$val = $this->game->getUniqueValueFromDB("select val from animals where id ='$tileid'" );

		$sql = "update animals set x = 0, y = 0, player_id = 0, status='DISCARD' where id = '$tileid'";
		$this->game->DbQuery( $sql );
		$sql = "update player set money = money - 2 where player_id = '$player_id'";
		$this->game->DbQuery( $sql );

		$this->game->incStat( 2, "coinsspent", $player_id);


		$this->game->notifyAllPlayers( "ConfirmDiscard", clienttranslate( '${player_name} discarded the ${translatedval} from his Barn.'),
		array(
			'player_id' => $player_id,
			'player_no' => $player_no,
			'tileid' => $tileid,
			'val' => $val,
			'translatedval' => Decoder::Animal($val),
			'player_name' => $this->game->getCurrentPlayerName(),
			'i18n' => array( 'translatedval' )
		) );

        return NextPlayer::class;
    }

    #[PossibleAction]
    public function actBack(): mixed {
        return PlayerTurn::class;
    }

    function zombie(int $playerId): mixed {
        // FIXME
        return "";
    }
}
