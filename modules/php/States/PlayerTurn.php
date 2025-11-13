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
use Override;

class PlayerTurn extends AbstractState
{
	function __construct(private Game $game)
	{
		parent::__construct(
			game: $game,
			id: 2,
			type: StateType::ACTIVE_PLAYER,
			description: clienttranslate('${actplayer} must take an action.'),
			descriptionMyTurn: clienttranslate('${you} must take an action.'),
		);
	}

	public function onEnteringState(int $active_player_id): void {
		$this->game->undoSavepoint();
	}

	public function getArgs(int $active_player_id): array
	{
        $model = $this->createModel();
		$player = $model->getActivePlayer();

		$trucks_available = [];
		if (!$player->truck_taken) {
			foreach ($model->getTrucks() as $truck) {
				if ($truck->canBeTaken()) {
					$trucks_available[] = [
						'truck_id' => $truck->id,
						'playable' => $model->getPossiblePlacements($active_player_id, $truck->id)->serialize(),
					];
				}
			}
		}

		/*
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
		*/
		return [
			'can_draw' => $model->getStock()->drawn == null && $model->spacesOnTrucks() > 0,
			'can_purchase' => $player->canPurchaseExtension(),
			'can_move' => false,
			'can_buy' => false,
			'can_swap' => false,
			'can_discard' => false,
			'available_trucks' => $trucks_available,
			// 'money' => $player->money,
			'unblockedzoo' => $player->purchased_extensions,
			// 'wagons' =>  $wagondata,
		];
	}

	#[PossibleAction]
	public function actTakeTruck(int $active_player_id, int $truck_id): mixed
	{
		$player_id = $active_player_id;

        $model = $this->createModel();
		$truck = $model->playerTakeTruck($player_id, $truck_id);

		// $messagestring = implode(
		// 	', ',
		// 	array_map(function (Tile $tile): string { return $tile->type->translated(); },
		// 			  $truck->getAllTiles()));

		$this->notify->all("TakeTruck", '${player_name} took truck ${truck_id}.',
		[
			'player_id' => $active_player_id,
			'truck_id' => $truck_id,
			'tiles' => [],
			// 'contents' => $messagestring,
			// 'i18n' => ['contents'],
		]);

		return PlaceTruckTiles::class;
	}

	#[PossibleAction]
	public function actPlaceTruckTiles(int $active_player_id): mixed {
		$this->notify->all('PlaceTruckTiles', '${player_name} place tiles from truck', [
			'player_id' => $active_player_id,
		]);
		return NextPlayer::class;
	}

	#[PossibleAction]
	public function actDrawTile(int $active_player_id): mixed
	{
        $model = $this->createModel();

		$stock = $model->drawTile();
		$tile = $stock->drawn;
		$this->game->undoSavepoint();

		if ($stock->waslastRoundTriggered()) {
			$this->notify->all("LastRound", clienttranslate('This is the last round...'), []);
		}

		// FIXME: pull from options
		$show_counts = false;
		// FIXME: make a method?
		$amt = function (int $count) use (&$show_counts) : int {
			if ($count <= 5 || $show_counts) { return $count; }
			else return 200;
		};

		$drawn_from_endgame_pile = $stock->lastDrawFromEndgamePile();

		$this->notify->all(
			"DrawTile",
			// FIXME: render the tile image in the log (in addition? instead?)
			clienttranslate('${player_name} drew a ${translatedval} tile.'),
			[
				'player_id' => $active_player_id,
				'tile_type' => $tile->type->value,
				'drawn_from_endgame_pile' => $drawn_from_endgame_pile,
				'primary_left' => $amt($stock->primaryCount()),
				'endgame_left' => $amt($stock->endgameCount()),
				'translatedval' => $tile->type->translated(),
				'i18n' => ['translatedval']
			]
		);
		return PlaceDrawnTile::class;
	}

	#[PossibleAction]
	public function actPurchaseEnclosure(int $active_player_id): mixed
	{
        $model = $this->createModel();
		$player = $model->getActivePlayer();
		/*
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
*/
		return NextPlayer::class;
	}

	#[PossibleAction]
	public function actMoveTile(): mixed
	{
		return Move::class;
	}

	#[PossibleAction]
	public function actSwapEnclosureContents(): mixed
	{
		return Swap::class;
	}

	#[PossibleAction]
	public function actBuyTile(): mixed
	{
		return Buy::class;
	}

	#[PossibleAction]
	public function actDiscardTile(): mixed
	{
		return Discard::class;
	}

	function zombie(int $playerId): mixed
	{
		// FIXME
		return "";
	}
}
