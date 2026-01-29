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

use Bga\GameFramework\Actions\Types\JsonParam;
use Bga\GameFramework\StateType;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\zooloretto\Game;
use Bga\Games\zooloretto\Model\AvailableTruck;
use Bga\Games\zooloretto\Model\Delivery;
use Bga\Games\zooloretto\Model\Enclosure;
use Bga\Games\zooloretto\Model\EnclosureSummary;
use Bga\Games\zooloretto\Model\Moneys;
use Bga\Games\zooloretto\Model\Offspring;
use Bga\Games\zooloretto\Model\PlacedTile;
use Bga\Games\zooloretto\Model\PossibleExchange;
use Bga\Games\zooloretto\Model\PossibleMove;
use Bga\Games\zooloretto\Model\Space;
use Bga\Games\zooloretto\Model\Truck;

class PlayerTurn extends AbstractState
{
	function __construct(Game $game)
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

    /** @return array<string,mixed> */
	public function getArgs(int $active_player_id): array
	{
        $model = $this->createModel($active_player_id);
		$available_trucks = array_map(
			fn (AvailableTruck $at) => $at->serialize(),
			$model->getAvailableTrucks());

		$pms = array_map(fn (PossibleMove $pm) => $pm->serialize(), $model->getPossibleMoves());

		$pb = array_map(fn (PossibleMove $b) => $b->serialize(), $model->getPurchaseableTiles());

		$pxs = [];
		foreach ($model->getPossibleExchanges() as $px) {
			$pxs[] = [
				'money_delta' => $px->moneyDelta->serialize(),
				'offspring' => array_map(
					fn ($o) => $o->serialize(), $px->offspring),
				'src' => array_map(
					fn ($p) => new Space($px->src_enclosure_id, $p)->serialize(), $px->src_positions),
				'dest' => array_map(
					fn ($p) => new Space($px->dest_enclosure_id, $p)->serialize(), $px->dest_positions),
			];
		}
		$pds = array_map(fn ($s) => $s->serialize(), $model->getDiscardables());

		return [
			'can_draw' => $model->canDraw(),
			'truck_taken' => $model->getActivePlayer()->truck_taken,
			'available_trucks' => $available_trucks,
			'can_move' => count($pms) > 0,
			'possible_purchases' => $pb,
			'possible_discards' => $pds,
			'possible_exchanges' => $pxs,
			'can_expand' => $model->canExpand(),
			'lastround' => $model->getStock()->inLastRound(),
		];
	}

	#[PossibleAction]
	public function actStartExchange(int $active_player_id): mixed {
		return ExchangeTiles::class;
	}

	#[PossibleAction]
	public function actStartMove(int $active_player_id): mixed {
		return MoveTile::class;
	}

	#[PossibleAction]
	public function actStartDiscard(int $active_player_id): mixed {
		return DiscardTile::class;
	}

	#[PossibleAction]
	public function actStartPurchase(int $active_player_id): mixed {
		return PurchaseTile::class;
	}

	#[PossibleAction]
	public function actTakeTruck(int $active_player_id, int $truck_id): mixed {
        $model = $this->createModel($active_player_id);
		$info = $model->startTruckDelivery($truck_id);
		$this->notify->all(
            'StartDelivery',
            clienttranslate('${player_name} started delivery from ${truck}'),
            [
                'player_id' => $active_player_id,
                'truck_id' => $truck_id,
				'coin_positions' => $info["coin_positions"],
				'moneys' => $model->currentMoneys(),
                'truck' => Truck::translated($truck_id),
                'i18n' => [
                    'truck',
                ]
            ]
        );
		return DeliverTruckTiles::class;
	}

	#[PossibleAction]
	public function actDrawTile(int $active_player_id): mixed
	{
        $model = $this->createModel($active_player_id);

		$stock = $model->drawTile();
		$tile = $stock->drawn;

		if ($stock->waslastRoundTriggered()) {
			$this->notify->all("LastRound", clienttranslate('This is the last round...'), []);
		}

		$this->notify->all(
			"DrawTile",
			clienttranslate('${player_name} drew a ${tile_type}.'),
			[
				'player_id' => $active_player_id,
				'tile' => $tile->serialize(),
				'drawn_from_endgame_pile' => $stock->inLastRound(),
				'primary_pile_size' => $this->stockCount($stock->primaryCount()),
				'endgame_pile_size' => $stock->endgameCount(),
				'tile_type' => $tile->type->value,
				'tile_description' => $tile->type->translated(),
				'i18n' => ['tile_description']
			]
		);
		$this->game->stats->PLAYER_TILESDRAWN->inc($active_player_id);
		return LoadDrawnTile::class;
	}

	#[PossibleAction]
	public function actExpandZoo(int $active_player_id): mixed
	{
        $model = $this->createModel($active_player_id);
		$player = $model->expandZoo();
		$this->notify->all(
			"ExpandZoo",
			clienttranslate('${player_name} expanded their zoo.'),
			[
				'player_id' => $active_player_id,
				'purchased_extensions' => $player->purchased_extensions,
	  		    'moneys' => $model->currentMoneys()->serialize(),
			]
		);
		$this->game->stats->PLAYER_EXPANSIONSPURCHASED->inc($active_player_id);
		return NextPlayer::class;
	}

	function zombie(int $player_id): mixed
	{
		$model = $this->createModel($player_id);
		if ($model->canDraw()) {
			return $this->actDrawTile($player_id);
		}

		foreach ($model->getTrucks() as $truck) {
			if (!$truck->isEmpty()) {
				return $this->actTakeTruck($player_id, $truck->id);
			}
		}

		// We should never get here. If there are no tiles to draw, or places to put the tiles
		// there should be at least one spot on one truck. That's why there are 15 tiles in
		// the end game pile -- 3 for each possible player.
		throw new \BgaVisibleSystemException("cannot draw, nor are there non-empty trucks available to take?!?");
	}
}
