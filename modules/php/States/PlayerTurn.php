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
      2 => array(
    		"name" => "playerTurn",
    		"description" => clienttranslate('${actplayer} must draw a tile to add to a wagon, take a wagon and pass or perform a money action.'),
    		"descriptionmyturn" => clienttranslate('${you} must draw a tile to add to a wagon, take a wagon and pass or perform a money action.'),
    		"type" => "activeplayer",
			"args" => "argplayerTurn",
    		"possibleactions" => array( "DrawTile", "TakeWagon", "BuyEnclosure", "Move", "Swap", "Buy", "Discard" ),
    		"transitions" => array( "PlaceTile" => 3, "ArrangeZoo" => 5, "NextPlayer" => 4, "Move" => 7, "Swap" => 8, "Buy" => 9, "Discard" => 10)
    ),
*/

class PlayerTurn extends GameState
{
    function __construct(private Game $game)
    {
        parent::__construct(
            game: $game,
            id: 2,
            type: StateType::ACTIVE_PLAYER,
            description: clienttranslate('${actplayer} must draw a tile to add to a wagon, take a wagon and pass or perform a money action.'),
            descriptionMyTurn: clienttranslate('${you} must draw a tile to add to a wagon, take a wagon and pass or perform a money action.'),
        );
    }

    public function getArgs(int $active_player_id): array
    {
		$active_player_id = $this->game->getActivePlayerId();
        return [
            'active_player_id'=> $this->game->getActivePlayerId(),
            'money' => $this->game->getUniqueValueFromDB("select money from player where player_id ='$active_player_id'" ),
            'unblockedzoo' => $this->game->getUniqueValueFromDB("select unblockedzoo from player where player_id ='$active_player_id'" ),
            'wagons' =>  $this->game->getObjectListFromDB( "SELECT id, size, val1, val2, val3 from wagons where status in ('AVAILABLE','TAKEN') order by id" ),
        ];
    }

	protected function dealAnimalsStatus(string $status)
	{
		$cardnum = $this->game->getUniqueValueFromDB( "SELECT count(*) from animals where status='AVAILABLE'" );
		if ($cardnum==0)
		{
			$sql = "update player set lastround = 'Y'";
			$this->game->DbQuery( $sql );
			$sql = "update animals set status = 'AVAILABLE' where status = 'LASTSET'";
			$this->game->DbQuery( $sql );
			$cardnum = $this->game->getUniqueValueFromDB( "SELECT count(*) from animals where status='AVAILABLE'" );

			$this->game->notifyAllPlayers( "LastRound", clienttranslate( 'This is the last round...'),
			array() );
		}

		if ($cardnum!=0)
		{
			$cards = $this->game->getObjectListFromDB( "SELECT id from animals where status='AVAILABLE'" );
			$i = 0;
			foreach( $cards as $index => $card)
			{
				$i = $i + 1;
				$sql = "update animals set idsel = $i where id = '" . $card['id']. "'";
				$this->game->DbQuery( $sql );
			}
			$result = bga_rand(1, intval ($cardnum) );

			$sql = "update animals set status = '$status' where idsel = '$result'";
			$this->game->DbQuery( $sql );

			$cardresult = $this->game->getUniqueValueFromDB( "SELECT id from animals where idsel='$result'" );

			$sql = "update animals set idsel = null";
			$this->game->DbQuery( $sql );
		}
		else
		{
			$cardresult = 0;
		}
		return $cardresult;
	}

    #[PossibleAction]
    public function actTakeWagon(string $x): mixed {
		$id1 = $this->game->getUniqueValueFromDB("select val1 from wagons where id='$x'" );
		$id2 = $this->game->getUniqueValueFromDB("select val2 from wagons where id='$x'" );
		$id3 = $this->game->getUniqueValueFromDB("select val3 from wagons where id='$x'" );
		$player_id = $this->game->getCurrentPlayerId();
		$player_no = $this->game->getUniqueValueFromDB("select player_no from player where player_id ='$player_id'" );
		$val1 = $this->game->getUniqueValueFromDB("select val from animals where id ='$id1'" );
		$val2 = $this->game->getUniqueValueFromDB("select val from animals where id ='$id2'" );
		$val3 = $this->game->getUniqueValueFromDB("select val from animals where id ='$id3'" );
		$sql = "update player set skipped='Y' where player_id = '$player_id'";
		$this->game->DbQuery( $sql );

		$wagontiles = $this->game->getObjectListFromDB( "SELECT concat('tile_0_',id,'_',val,'_',x,'_',y) wagontile, id FROM `animals` WHERE status = 'WAGON' and x=$x");

		$sql = "update wagons set status = 'TAKEN' where id = '$x'";
		$this->game->DbQuery( $sql );

		$messagestring="";
		if ($val1!="")
		{
			$messagestring = $messagestring . Decoder::Animal($val1) . ", ";
		}
		if ($val2!="")
		{
			$messagestring = $messagestring . Decoder::Animal($val2) . ", ";
		}
		if ($val3!="")
		{
			$messagestring = $messagestring . Decoder::Animal($val3) . ", ";
		}
		$messagestring = substr($messagestring, 0, strlen($messagestring)-2);

		$this->game->notifyAllPlayers( "TakeWagon", clienttranslate( '${player_name} took a wagon with ${wag}.'),
		array(
			'player_id' => $player_id,
			'player_no' => $player_no,
			'id1' => $id1,
			'id2' => $id2,
			'id3' => $id3,
			'val1' => $val1,
			'val2' => $val2,
			'val3' => $val3,
			'x' => $x,
			'wag' => $messagestring,
			'wagontiles' => $wagontiles,
			'player_name' => $this->game->getCurrentPlayerName(),
			'i18n' => array( 'wag' )
		) );

		return ArrangeZoo::class;
    }

    #[PossibleAction]
    public function actDrawTile(): mixed {
        //		$this->game->checkAction( 'DrawTile' );
		$id = $this->dealAnimalsStatus("DRAWN");
		$player_id = $this->game->getCurrentPlayerId();
		$player_no = $this->game->getUniqueValueFromDB("select player_no from player where player_id ='$player_id'" );
		$val = $this->game->getUniqueValueFromDB("select val from animals where id ='$id'" );

		$tilesleft =  $this->game->getUniqueValueFromDB( "SELECT count(*) from animals where status='AVAILABLE'" );
		$tilesleft2 =  $this->game->getUniqueValueFromDB( "SELECT count(*) from animals where status='LASTSET'" );


		$this->game->notifyAllPlayers( "DrawTile", clienttranslate( '${player_name} drew a ${translatedval} tile.'),
		array(
			'player_id' => $player_id,
			'player_no' => $player_no,
			'id' => $id,
			'val' => $val,
			'tilesleft' => $tilesleft,
			'tilesleft2' => $tilesleft2,
			'translatedval' => Decoder::Animal($val),
			'player_name' => $this->game->getCurrentPlayerName(),
			'i18n' => array( 'translatedval' )
		) );

        return PlaceTile::class;
    }

    #[PossibleAction]
    public function actBuyEnclosure(): mixed {
		$player_id = $this->game->getCurrentPlayerId();
		$player_no = $this->game->getUniqueValueFromDB("select player_no from player where player_id ='$player_id'" );

		$sql = "update player set unblockedzoo = unblockedzoo + 1, money = money - 3 where player_id = '$player_id'";
		$this->game->DbQuery( $sql );

		$this->game->incStat( 3, "coinsspent", $player_id);

		$unblockedzoo = $this->game->getUniqueValueFromDB("select unblockedzoo from player where player_id ='$player_id'" );

		$this->game->notifyAllPlayers( "BuyEnclosure", clienttranslate( '${player_name} bought his ${pos} extra enclosure.'),
		array(
			'player_id' => $player_id,
			'player_no' => $player_no,
			'unblockedzoo' => $unblockedzoo,
			'pos' => Decoder::Pos($unblockedzoo),
			'player_name' => $this->game->getCurrentPlayerName(),
			'i18n' => array( 'pos' )
		) );

		return NextPlayer::class;
    }

    #[PossibleAction]
    public function actMove(): mixed {
        return Move::class;
    }

    #[PossibleAction]
    public function actSwap(): mixed {
        return Swap::class;
    }

    #[PossibleAction]
    public function actBuy(): mixed {
        return Buy::class;
    }

    #[PossibleAction]
    public function actDiscard(): mixed {
        return Discard::class;
    }

    function zombie(int $playerId): mixed {
        // FIXME
        return "";
    }
}
