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
use Bga\Games\zooloretto\Model\Tile;
use Bga\Games\zooloretto\Model\Wagon;
use Bga\Games\zooloretto\Model\WagonStatus;


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
		$wagondata = array_map(function (Wagon $wagon): array {
			return [
				"id" => $wagon->id,
				"size" => $wagon->capacity,
				"val1" => $wagon->valAt(0),
				"val2" => $wagon->valAt(1),
				"val3" => $wagon->ValAt(2),
			];
		}, array_filter($model->getWagons(), function (Wagon $wagon): bool {
			return $wagon->status == WagonStatus::AVAILABLE
				|| $wagon->status == WagonStatus::TAKEN;
		}));
		return [
            'active_player_id'=> $active_player_id,
            'money' => $player->money,
            'unblockedzoo' => $player->purchased_extensions,
            'wagons' =>  $wagondata,
        ];
    }

   #[PossibleAction]
    public function actTakeWagon(int $x): mixed {
		$player_id = intval($this->game->getActivePlayerId());

		$model = new Model();
		$player = $model->getPlayer($player_id);
		$wagon = $model->takeWagon($player, $x);
		$player_no = $player->no;
		$wagontiles = array_map(function (Tile $tile): array {
			return [
				"id" => $tile->id,
				"wagontile" => implode("_", [$tile->id, $tile->type->value, $tile->x, $tile->y]),
			];
		}, $wagon->tiles);
		$messagestring = implode(', ', array_filter(
			[$wagon->valAt(0), $wagon->valAt(1), $wagon->valAt(2)],
			function (string $s): bool {
				return $s > "";
			}
		));

		return ArrangeZoo::class;
    }

    #[PossibleAction]
    public function actDrawTile(int $active_player_id): mixed {
		$model = new Model();
		$deck = $model->drawTile();
		$tile = $deck->drawn;

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
		$model->buyEnclosure($player);
		$this->playerStats->inc( "coinsspent", $player->moneySpent(), $active_player_id);

		$this->notify->all( "BuyEnclosure", clienttranslate( '${player_name} bought his ${pos} extra enclosure.'),
		array(
			'player_id' => $active_player_id,
			'player_no' => $player->no,
			'unblockedzoo' => $player->purchased_extensions,
			'pos' => Decoder::Pos($player->purchased_extensions),
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
