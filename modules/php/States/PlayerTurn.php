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
use Bga\Games\zooloretto\Model\Model;


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
		$model = new Model();
		$player = $model->getPlayer($active_player_id);
        return [
            'active_player_id'=> $active_player_id,
            'money' => $player->money,
            'unblockedzoo' => $player->available_enclosures,
            'wagons' =>  $this->game->getObjectListFromDB( "SELECT id, size, val1, val2, val3 from wagons where status in ('AVAILABLE','TAKEN') order by id" ),
        ];
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

		$this->notify->all( "TakeWagon", clienttranslate( '${player_name} took a wagon with ${wag}.'),
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
    public function actDrawTile(int $active_player_id): mixed {
		$model = new Model();
		$deck = $model->getDeck();
		$tile = $deck->drawTile();
		$model->updateDeck();

		if ($model->waslastRoundTriggered()) {
			$this->notify->all( "LastRound", clienttranslate( 'This is the last round...'), []);
		}

		$this->notify->all( "DrawTile", clienttranslate( '${player_name} drew a ${translatedval} tile.'),
		array(
			'player_id' => $active_player_id,
			'player_no' => $model->getPlayer($active_player_id)->no,
			'id' => $tile->id,
			'val' => $tile->type->value,
			'tilesleft' => count($deck->tiles),
			'tilesleft2' => count($deck->lastset),
			'translatedval' => Decoder::Animal($tile->type->value),
			'i18n' => array( 'translatedval' )
		) );

        return PlaceTile::class;
    }

    #[PossibleAction]
    public function actBuyEnclosure(int $active_player_id): mixed {
		$model = new Model();
		$player = $model->getPlayer($active_player_id);
		$player->buyEnclosure();
		$model->updatePlayer($active_player_id);
		$this->playerStats->inc( "coinsspent", $player->moneySpent(), $active_player_id);

		$this->notify->all( "BuyEnclosure", clienttranslate( '${player_name} bought his ${pos} extra enclosure.'),
		array(
			'player_id' => $active_player_id,
			'player_no' => $player->no,
			'unblockedzoo' => $player->available_enclosures,
			'pos' => Decoder::Pos($player->available_enclosures),
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
