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

namespace Bga\Games\zoolorettoalpha\States;

use Bga\GameFramework\Actions\Types\JsonParam;
use Bga\GameFramework\StateType;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\SystemException;
use Bga\Games\zoolorettoalpha\Game;
use Bga\Games\zoolorettoalpha\Model\Cost;
use Bga\Games\zoolorettoalpha\Model\Delivery;
use Bga\Games\zoolorettoalpha\Model\Enclosure;
use Bga\Games\zoolorettoalpha\Model\EnclosureSummary;
use Bga\Games\zoolorettoalpha\Model\Moneys;
use Bga\Games\zoolorettoalpha\Model\Space;
use Bga\Games\zoolorettoalpha\Model\Truck;

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

    /** @return array<string,mixed> */
	public function getArgs(int $active_player_id): array
	{
        $model = $this->createModel($active_player_id, true);
		$pe = $model->getPossibleExchanges();
		return [
			'can_draw' => $model->canDraw(),
			'truck_taken' => $model->getActivePlayer()->truck_taken,
			'available_trucks' => $model->getAvailableTruckIds(),
			'possible_moves' => [
				'moves' => self::serializeArray($model->getPossibleMoves()),
				'money_delta' => Moneys::costPlayerDelta($active_player_id, Cost::MOVE)->serialize(),
			],
			'possible_purchases' => self::serializeArray($model->getPurchaseableTiles()),
			'possible_discards' => [
				'money_delta' => Moneys::costPlayerDelta($active_player_id, Cost::DISCARD)->serialize(),
				'spaces' => self::serializeArray($model->getDiscardables())
			],
			'possible_exchanges' => $pe == null ? null : [
				'money_delta' => Moneys::costPlayerDelta($active_player_id, Cost::EXCHANGE)->serialize(),
				'exchanges' => $pe->serialize(),
			],
			'extension_available' => $model->extensionAvailable(),
			'lastround' => $model->getStock()->inLastRound(),
		];
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
	public function actMoveTile(int $active_player_id, int $src_id, int $src_pos, int $dest_id, int $dest_pos): mixed
	{
        $model = $this->createModel($active_player_id);
		$dest = new Space($dest_id, $dest_pos);
		$placed_tile = $model->moveTile(new Space($src_id, $src_pos), $dest);
		$tile = $placed_tile->tile;
		$this->notifyOffspring($active_player_id, $placed_tile->offspring);
		$this->notify->all(
			"MoveTile",
			// FIXME: need to handle barn ...
			clienttranslate('${player_name} moved a ${tile_type} tile from ${src_enclosure} to ${dest_enclosure}'),
			[
				'player_id' => $active_player_id,
				'tile' => $tile->serialize(),
				'dest' => $dest->serialize(),
        		'moneys' => $model->currentMoneys()->serialize(),
				'src_enclosure_id' => $src_id,
				'src_enclosure' => Enclosure::translated($src_id),
				'dest_enclosure_id' => $dest_id,
				'dest_enclosure' => Enclosure::translated($dest_id),
				'tile_type' => $tile->type->value,
				'tile_description' => $tile->type->translated(),
				'enclosure_summaries' => [
					EnclosureSummary::forEnclosure($active_player_id, $model->getEnclosuresForPlayer($active_player_id)[$src_id])->serialize(),
					EnclosureSummary::forEnclosure($active_player_id, $model->getEnclosuresForPlayer($active_player_id)[$dest_id])->serialize(),
				],
				'i18n' => [
					'tile_description',
					'src_enclosure',
					'dest_enclosure',
				]
			]
		);
		if ($tile->type->isAnimal()) {
			$this->game->stats->PLAYER_MOVEDANIMALS->inc($active_player_id);
		} else {
			$this->game->stats->PLAYER_MOVEDSTALLS->inc($active_player_id);
			if ($src_id == 0) {
				$this->game->stats->PLAYER_MOVEDSTALLSFROMBARN->inc($active_player_id);
			}
		}
		$this->notifyCompletionCoins($active_player_id, $dest_id, $placed_tile->completionCoins());
		return NextPlayer::class;
	}

	/**
	 * @param list<int> $dest_positions
	 */
	#[PossibleAction]
	public function actExchangeEnclosureAnimals(
		int $active_player_id,
		int $src_enclosure_id,
		int $dest_enclosure_id,
		#[JsonParam] ?array $dest_positions): mixed
	{
        $model = $this->createModel($active_player_id);
		$completedExchange = $model->exchange($src_enclosure_id, $dest_enclosure_id, $dest_positions);
		$this->notifyOffspring($active_player_id, $completedExchange->offspring());

		$this->notify->all(
            'ExchangeEnclosureAnimals',
			// FIXME: need to handle barn
            clienttranslate('${player_name} exchanged ${tile_type} between ${src_enclosure} and ${dest_enclosure}'),
            [
                'player_id' => $active_player_id,
                'placed_tiles' => self::serializeArray($completedExchange->placedTiles),
                'src_enclosure_id' => $completedExchange->src_enclosure_id,
                'src_enclosure' => Enclosure::translated($completedExchange->src_enclosure_id),
                'moneys' => $model->currentMoneys()->serialize(),
                'dest_enclosure_id' => $completedExchange->dest_enclosure_id,
                'dest_enclosure' => Enclosure::translated($completedExchange->dest_enclosure_id),
                'tile_type' => $completedExchange->src_tile_type,
                'tile_description' => $completedExchange->src_tile_type->translated(),
                'enclosure_summaries' => [
                    EnclosureSummary::forEnclosure($active_player_id, $model->getEnclosuresForPlayer($active_player_id)[$src_enclosure_id])->serialize(),
                    EnclosureSummary::forEnclosure($active_player_id, $model->getEnclosuresForPlayer($active_player_id)[$dest_enclosure_id])->serialize(),
                ],
                'i18n' => [
                    'tile_description',
                    'src_enclosure',
                    'dest_enclosure',
                ]
            ]
        );

		$this->game->stats->PLAYER_EXCHANGEACTIONS->inc($active_player_id);
		if ($src_enclosure_id == 0 || $dest_enclosure_id == 0) {
			$this->game->stats->PLAYER_EXCHANGEACTIONSWITHBARN->inc($active_player_id);
		}

		return NextPlayer::class;
	}

	#[PossibleAction]
	public function actPurchaseTile(int $active_player_id, int $from_player_id, int $barn_pos, int $enclosure_id, int $enclosure_pos): mixed
	{
        $model = $this->createModel($active_player_id);
		$dest = new Space($enclosure_id, $enclosure_pos);
		$purchased = $model->purchaseTile($from_player_id, $barn_pos, $dest);
		$this->notifyOffspring($active_player_id, $purchased->offspring);
		$this->notify->all(
            'PurchaseTile',
            clienttranslate('${player_name} purchased ${tile_type} from ${player_name2} into ${enclosure}'),
            [
                'player_id' => $active_player_id,
                'player_id2' => $from_player_id,
                'tile_type' => $purchased->tile->type->value,
                'placed_tile' => $purchased->serialize(),
                'moneys' => $model->currentMoneys()->serialize(),
                'enclosure' => Enclosure::translated($enclosure_id),
                'tile_description' => $purchased->tile->type->translated(),
                'enclosure_summaries' => [
                    EnclosureSummary::forEnclosure($active_player_id, $model->getEnclosuresForPlayer($active_player_id)[$enclosure_id])->serialize(),
                    EnclosureSummary::forEnclosure($from_player_id, $model->getEnclosuresForPlayer($from_player_id)[0])->serialize(),
                ],
                'i18n' => [
                    'tile_description',
                    'enclosure',
                    'seller_player_name',
                ]
            ]
        );
		$this->game->stats->PLAYER_TILESPURCHASED->inc($active_player_id);
		$this->game->stats->PLAYER_TILESSOLD->inc($from_player_id);
		$this->notifyCompletionCoins($active_player_id, $enclosure_id, $purchased->completionCoins());

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

	/** @param list<array{truck_pos:int,enclosure_id:int,enclosure_pos:int}> $placements */
	#[PossibleAction]
	public function actTakeTruckAndPlaceTiles(int $active_player_id, int $truck_id, #[JsonParam()] array $placements): mixed
	{
        $model = $this->createModel($active_player_id);
		$res = $model->takeTruckAndDeliverTiles($truck_id, $placements);
		$deliveries = $res['deliveries'];
		$cointiles = $res['coins'];
		$this->notify->all(
			'TakeTruck',
			clienttranslate('${player_name} took ${truck}'), [
				'player_id' => $active_player_id,
				'truck' => Truck::translated($truck_id),
				'i18n' => [
					'truck',
				]
			]
		);

		if (count($cointiles) > 0) {
			$coins = count($cointiles);
			$this->notify->all(
				'DeliverCoins',
				clienttranslate('${player_name} received ${coins} from ${truck}'), [
					'player_id' => $active_player_id,
					'coins' => $coins,
					'coin_tiles' => $this->serializeArray($cointiles),
					'truck_id' => $truck_id,
					'truck' => Truck::translated($truck_id),
					'i18n' => [
						'truck',
					]
				]
			);
		}
		// Notify for each tile, also send offspring and enclosure completion notifications.
		foreach ($deliveries as $delivery) {
			$pt = $delivery->placed_tile;
			$this->notify->all(
				'DeliverTruckTile',
				clienttranslate('${player_name} delivered ${tile_type} to ${enclosure_description} from ${truck}'), [
					'player_id' => $active_player_id,
					'delivery' => $delivery->serialize(),
					'tile_type' => $pt->tile->type,
					'truck_id' => $truck_id,
					'truck' => Truck::translated($truck_id),
					'enclosure_description' => Enclosure::translated($pt->space->enclosure_id),
					'i18n' => [
						'enclosure_description',
						'truck',
					]
				]
			);

			if ($pt->offspring) {
				$child = $pt->offspring->child;
				$tile = $child->tile;
				$space = $child->space;
				$this->notify->all(
					'Offspring',
					clienttranslate('${player_name} received an offspring ${tile_type} in ${enclosure_description}'),[
						'player_id' => $active_player_id,
						'offspring' => $pt->offspring->serialize(),
						'tile_type' => $tile->type->value,
						'tile_description' => $tile->type->translated(),
						'enclosure_description' => Enclosure::translated($space->enclosure_id),
						'i18n' => ['tile_description', 'enclosure_description']
					]
				);
			}
			$this->notifyCompletionCoins($active_player_id, $pt->space->enclosure_id, $pt->completionCoins());
		}

        $this->notify->all('DeliveryCompleted','',[
            'truck_id' => $truck_id,
            'player_id' => $active_player_id,
			'moneys' => $model->currentMoneys()->serialize(),
			'enclosure_summaries' => array_map(
					fn ($e) => EnclosureSummary::forEnclosure($active_player_id, $e)->serialize(),
					$model->getEnclosuresForPlayer($active_player_id)
			),
        ]);
		return NextPlayer::class;
	}

	/** @param list<array{truck_pos:int,enclosure_id:int,enclosure_pos:int}> $placements */
	#[PossibleAction]
    public function actDeliverPendingTiles(int $active_player_id, int $truck_id, #[JsonParam()] array $placements): mixed {
        $model = $this->createModel($active_player_id, true);
		$deliveries = $model->deliverPendingTruckTiles($truck_id, $placements);
        $pds = [];
        foreach ($model->getPossibleDeliveries($truck_id) as $pos => $dests) {
            $pds[] = [
                'truck_pos' => $pos,
                'dests' => self::serializeArray($dests),
            ];
        }
		$this->notify->player($active_player_id, 'DeliverPendingTruckTiles', '', [
            'deliveries' => self::serializeArray($deliveries),
            "truck_id" => $truck_id,
            "possible_deliveries" => $pds,
		]);
		return null;
    }

	function zombie(int $player_id): mixed
	{
		$model = $this->createModel($player_id, true);
		if ($model->canDraw()) {
			return $this->actDrawTile($player_id);
		}

		foreach ($model->getTrucks() as $truck) {
			if ($truck->canBeTaken()) {
				$placements = [];
				$coins = 0;
				foreach ($truck->coinPositions() as $pos) {
					$truck->removeTileAt($pos);
					$coins++;
				}
				while (!$truck->isEmpty()) {
					$deliveries = $model->deliverPendingTruckTiles($truck->id, $placements);
					$placements = array_map(function (Delivery $d): array {
						return [
							'truck_pos' => $d->truck_pos,
							'enclosure_id' => $d->placed_tile->space->enclosure_id,
							'enclosure_pos' => $d->placed_tile->space->pos,
						];
					}, $deliveries);
				}
				return $this->actTakeTruckAndPlaceTiles($player_id, $truck->id, $placements);
			}
		}
		// We should never get here. If there are no tiles to draw, or places to put the tiles
		// there should be at least one spot on one truck. That's why there are 15 tiles in
		// the end game pile -- 3 for each possible player.
		throw new SystemException("cannot draw, but there are no non-empty trucks available to take?!?");
	}
}
