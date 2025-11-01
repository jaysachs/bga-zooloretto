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
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\zooloretto\Decoder;
use Bga\Games\zooloretto\Game;
use Bga\Games\zooloretto\Model\Tile;
use Bga\Games\zooloretto\Model\Wagon;
use Bga\Games\zooloretto\Model\WagonStatus;


class PlayerTurn extends AbstractState
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
        $model = $this->createModel();
		$player = $model->getActivePlayer();
		$wagondata = array_map(function (Wagon $wagon): array {
			return [
				"id" => $wagon->id,
				"size" => $wagon->capacity,
				"val1" => $wagon->valAt(1),
				"val2" => $wagon->capacity >= 2 ? $wagon->valAt(2) : "",
				"val3" => $wagon->capacity >= 3 ? $wagon->valAt(3) : "",
			];
		}, array_filter($model->getWagons(), function (Wagon $wagon): bool {
			return $wagon->status == WagonStatus::AVAILABLE
				|| $wagon->status == WagonStatus::TAKEN;
		}));
		return [
			'active_player_id' => $active_player_id,
			'money' => $player->money,
			'unblockedzoo' => $player->purchased_extensions,
			'wagons' =>  $wagondata,
		];
	}

	#[PossibleAction]
	public function actTakeWagon(int $active_player_id, int $wagon_id): mixed
	{
		$player_id = $active_player_id;

        $model = $this->createModel();
		$wagon = $model->takeWagon($wagon_id);
		$tiles = array_filter($wagon->tiles, function ($t) { return $t != null; });
		$wagontiles = array_map(function (Tile $tile): array {
			return [
				"id" => $tile->id,
				"wagontile" => "tile_0_" . implode("_", [$tile->id, $tile->type->value, $tile->x, $tile->y]),
			];
		}, $tiles);

		$messagestring = implode(
			', ',
			array_map(function (Tile $tile): string { return $tile->type->translated(); },
					  $wagon->tiles));

		$this->notify->all(
			"TakeWagon",
			clienttranslate('${player_name} took a wagon with ${wag}.'),
			[
				'player_id' => $player_id,
				'wagon_id' => $wagon_id,
				'wag' => $messagestring,
				'wagontiles' => $wagontiles,
				'i18n' => ['wag']
			]
		);

		return ArrangeZoo::class;
	}

	#[PossibleAction]
	public function actDrawTile(int $active_player_id): mixed
	{
        $model = $this->createModel();
		$deck = $model->drawTile();
		$tile = $deck->drawn;

		if ($model->waslastRoundTriggered()) {
			$this->notify->all("LastRound", clienttranslate('This is the last round...'), []);
		}

		$this->notify->all(
			"DrawTile",
			clienttranslate('${player_name} drew a ${translatedval} tile.'),
			[
				'player_id' => $active_player_id,
				'id' => $tile->id,
				'val' => $tile->type->value,
				'tilesleft' => count($deck->tiles),
				'tilesleft2' => count($deck->lastset),
				'translatedval' => $tile->type->translated(),
				'i18n' => ['translatedval']
			]
		);

		return PlaceTile::class;
	}

	#[PossibleAction]
	public function actBuyEnclosure(int $active_player_id): mixed
	{
        $model = $this->createModel();
		$player = $model->getActivePlayer();
		$model->buyEnclosure($player);
		$this->playerStats->inc("coinsspent", $player->moneySpent(), $player->id);

		$this->notify->all(
			"BuyEnclosure",
			clienttranslate('${player_name} bought an extra enclosure.'),
			[
				'player_id' => $active_player_id,
				'unblockedzoo' => $player->purchased_extensions,
			]
		);

		return NextPlayer::class;
	}

	#[PossibleAction]
	public function actMove(): mixed
	{
		return Move::class;
	}

	#[PossibleAction]
	public function actSwap(): mixed
	{
		return Swap::class;
	}

	#[PossibleAction]
	public function actBuy(): mixed
	{
		return Buy::class;
	}

	#[PossibleAction]
	public function actDiscard(): mixed
	{
		return Discard::class;
	}

	function zombie(int $playerId): mixed
	{
		// FIXME
		return "";
	}
}
