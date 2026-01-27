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
			'possible_moves' => $pms,
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

	/** @param list<array<string,int>> $delivery_requests */
	#[PossibleAction]
	public function actTakeTruckAndPlaceTiles(int $active_player_id, int $truck_id, #[JsonParam] array $delivery_requests): mixed {
        $model = $this->createModel($active_player_id);

		$drs = array_map(
			fn ($dr) => [
				'truck_pos' => intval($dr['truck_pos']),
				'space' => new Space(intval($dr['enclosure_id']), intval($dr['enclosure_pos'])),
			],
			$delivery_requests
		);

		// FIXME: need to rework what we get back here. At a minimum, each delivery should say
		//   whether it completed an enclosure and what that bonus was.
		/** @var list<Delivery> */
		$deliveries = $model->takeTruckAndPlaceTiles($truck_id, $drs);
		// FIXME: give more details about placements in log

		$this->notify->all(
            'SelectTruck',
            clienttranslate('${player_name} selected ${truck}'),
            [
                'player_id' => $active_player_id,
                'truck_id' => $truck_id,
                'truck' => Truck::translated($truck_id),
                'i18n' => [
                    'truck',
                ]
            ]
        );

		// Send individual notifs for each tile
		foreach ($deliveries as $del) {
			if ($del->dest === null) {
				// coins
				$this->notify->all(
                    'PlaceTruckTile',
                    clienttranslate('${player_name} gained a ${tile_type}'),
                    [
                        'player_id' => $active_player_id,
                        'truck_id' => $truck_id,
                        'delivery' => $del->serialize(),
                        'tile_type' => $del->tile->type->value,
                        'tile_description' => $del->tile->type->translated(),
                        'i18n' => [
                            'tile_description',
					]
                    ]
                );
				$this->game->stats->PLAYER_COINTILESACQUIRED->inc($active_player_id);
			}
			else {
				$this->notify->all(
                    'PlaceTruckTile',
                    clienttranslate('${player_name} placed ${tile_type} into ${enclosure}'),
                    [
                        'player_id' => $active_player_id,
                        'truck_id' => $truck_id,
                        'tile_type' => $del->tile->type->value,
                        'tile_description' => $del->tile->type->translated(),
                        'delivery' => $del->serialize(),
                        'enclosure' => Enclosure::translated($del->dest->space->enclosure_id),
                        'enclosure_id' => $del->dest->space->enclosure_id,
                        'i18n' => [
                            'tile_description',
                            'enclosure',
                        ]
                    ]
                );
			    $this->game->stats->PLAYER_TILESTAKENFROMTRUCKS->inc($active_player_id);
				if ($del->dest->space->enclosure_id == 0) {
    				$this->game->stats->PLAYER_TILESTAKEFROMTRUCKSINTOBARN->inc($active_player_id);
				}
				$this->notifyOffspring($active_player_id, $del->dest->offspring);
				// FIXME: Destination (or Delivery?) should have completedEnclosure
				//    also should have specific moneyDelta for that
				// if ($del->dest->completedEnclosure) {
				// 	$this->notify->all('PlaceTruckTileCompleted', clienttranslate('${player_name} completed enclosure ${enclosure_id} and gained ${coins} bonus coins'), [
				// 		'player_id' => $active_player_id,
				// 		'truck_id' => $truck_id,
				// 		'enclosure_id' => $del->dest->space->enclosure_id,
				// 		'coins' => $del->dest->moneyDelta->players[$active_player_id],
				// 	]);
				// }
			}
		}
		// FIXME: probably want a specific moneyDelta for the completion bonus

		$this->notify->all("TakeTruck", "", [
			'player_id' => $active_player_id,
			'truck_id' => $truck_id,
			'moneys' => $model->currentMoneys()->serialize(),
			'enclosure_summaries' => array_map(
					fn ($e) => EnclosureSummary::forEnclosure($active_player_id, $e)->serialize(),
					$model->getEnclosuresForPlayer($active_player_id)
			),
		]);

		return NextPlayer::class;
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

	#[PossibleAction]
	public function actDiscardTile(int $active_player_id, int $barn_pos): mixed
	{
        $model = $this->createModel($active_player_id);
		$tile = $model->discardBarnTile($barn_pos);
		$this->notify->all(
            'DiscardTile',
            clienttranslate('${player_name} discarded tile ${tile_type}'),
            [
                'player_id' => $active_player_id,
                'tile' => $tile->serialize(),
                'tile_type' => $tile->type->value,
                'tile_description' => $tile->type->translated(),
                'moneys' => $model->currentMoneys()->serialize(),
                'space' => new Space(0, $barn_pos),
                'enclosure_summaries' => [
                    EnclosureSummary::forEnclosure($active_player_id, $model->getEnclosuresForPlayer($active_player_id)[0])->serialize(),
                ],
                'i18n' => [
                    'tile_description',
                ]
            ]
        );
		$this->game->stats->PLAYER_DISCARDEDTILES->inc($active_player_id);
		return NextPlayer::class;
	}

	function zombie(int $player_id): mixed
	{
		$model = $this->createModel($player_id);
		if ($model->canDraw()) {
			return $this->actDrawTile($player_id);
		}

		$truck = $model->getAvailableTrucks()[0];
		$pl = $truck->placement->placements[0];
		$placedTiles = [];
		while (true) {
			$placedTiles[] = [
				'truck_pos' => $pl->truck_pos,
				'enclosure_id' => $pl->next[0]->space->enclosure_id,
				'enclosure_pos' => $pl->next[0]->space->pos,
			];
			if (count($pl->next) == 0) {
				break;
			}
			if ($pl->next[0]->next == null) {
				break;
			}
			$pl = $pl->next[0]->next->placements[0];
		};
		return $this->actTakeTruckAndPlaceTiles($player_id, $truck->truck_id, $placedTiles);
	}
}
