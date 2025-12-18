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
use Bga\Games\zooloretto\Model\Moneys;
use Bga\Games\zooloretto\Model\Offspring;
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
			'available_trucks' => $available_trucks,
			'possible_moves' => $pms,
			'possible_purchases' => $pb,
			'possible_discards' => $pds,
			'possible_exchanges' => $pxs,
			'can_expand' => $model->canExpand(),
			'lastround' => $model->getStock()->inLastRound(),
		];
	}

	/** @param list<array<string,int>> $placed_tiles */
	#[PossibleAction]
	public function actTakeTruckAndPlaceTiles(int $active_player_id, int $truck_id, #[JsonParam] array $placed_tiles): mixed {
        $model = $this->createModel($active_player_id);

		$pts = [];
		foreach ($placed_tiles as $pt) {
			$pts[$pt['truck_pos']] =
				new Space(intval($pt['enclosure_id']),
						  intval($pt['enclosure_pos']));
		}

		// FIXME: need to rework what we get back here. At a minimum, each delivery should say
		//   whether it completed an enclosure and what that bonus was.
		/** @var list<Delivery> */
		$deliveries = $model->takeTruckAndPlaceTiles($truck_id, $pts);
		// FIXME: give more details about placements in log

		$this->notify->all('SelectTruck', '${player_name} selected ${truck}', [
			'player_id' => $active_player_id,
			'truck_id' => $truck_id,
			'truck' => Truck::translated($truck_id),
			'i18n' => [
				'truck',
			]
		]);

		// Send individual notifs for each tile
		foreach ($deliveries as $del) {
			if ($del->dest === null) {
				// coins
				$this->notify->all('PlaceTruckTile', '${player_name} gained a ${tile_type}',[
				    'player_id' => $active_player_id,
					'truck_id' => $truck_id,
					'delivery' => $del->serialize(),
					'tile_type' => $del->tile->type->value,
					'tile_description' => $del->tile->type->translated(),
					'i18n' => [
						'tile_description',
					]
				]);
				$this->game->stats->PLAYER_COINTILESACQUIRED->inc($active_player_id);
			}
			else {
				$this->notify->all('PlaceTruckTile', '${player_name} placed ${tile_type} into ${enclosure}',[
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
				]);
			    $this->game->stats->PLAYER_TILESTAKENFROMTRUCKS->inc($active_player_id);
				if ($del->dest->space->enclosure_id == 0) {
    				$this->game->stats->PLAYER_TILESTAKEFROMTRUCKSINTOBARN->inc($active_player_id);
				}

				// FIXME: Destination (or Delivery?) should have completedEnclosure
				//    also should have specific moneyDelta for that
				// if ($del->dest->completedEnclosure) {
				// 	$this->notify->all('PlaceTruckTileCompleted', '${player_name} completed enclosure ${enclosure_id} and gained ${coins} bonus coins', [
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
		return PlaceDrawnTile::class;
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
		$result = $model->moveTile(new Space($src_id, $src_pos), $dest);
		$tile = $result['tile'];
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
		$this->notifyOffspring($active_player_id, $result['offspring']);
		$this->notifyBonus($active_player_id, $dest_id, $result['enclosureBonus']);
		return NextPlayer::class;
	}

	/**
	 * @param list<int> $src_positions
	 * @param list<int> $dest_positions
	 */
	#[PossibleAction]
	public function actExchangeEnclosureAnimals(
		int $active_player_id,
		int $src_enclosure_id,
		#[JsonParam] array $src_positions,
		int $dest_enclosure_id,
		#[JsonParam] array $dest_positions): mixed
	{
        $model = $this->createModel($active_player_id);
		$completedExchange = $model->exchange(new PossibleExchange($src_enclosure_id, $src_positions, $dest_enclosure_id, $dest_positions, [], new Moneys(0)));

		$this->notify->all('ExchangeEnclosureAnimals',
			// FIXME: need to handle barn
		    '${player_name} exchanged ${src_tile_type} and ${dest_tile_type} between ${src_enclosure} and ${dest_enclosure}', [
			'player_id' => $active_player_id,
			'placed_tiles' => array_map(fn($pt) => $pt->serialize(), $completedExchange->placedTiles),
			'src_enclosure_id' => $completedExchange->src_enclosure_id,
			'src_enclosure' => Enclosure::translated($completedExchange->src_enclosure_id),
        	'moneys' => $model->currentMoneys()->serialize(),
			'dest_enclosure_id' => $completedExchange->dest_enclosure_id,
			'dest_enclosure' => Enclosure::translated($completedExchange->dest_enclosure_id),
			'src_tile_type' => $completedExchange->src_tile_type,
			'dest_tile_type' => $completedExchange->dest_tile_type,
			'src_tile_description' => $completedExchange->src_tile_type->translated(),
			'dest_tile_description' => $completedExchange->dest_tile_type->translated(),
			'i18n' => [
				'src_tile_description',
				'dest_tile_description',
				'src_enclosure',
				'dest_enclosure',
			]
		]);

		$this->game->stats->PLAYER_EXCHANGEACTIONS->inc($active_player_id);
		if ($src_enclosure_id == 0 || $dest_enclosure_id == 0) {
			$this->game->stats->PLAYER_EXCHANGEACTIONSWITHBARN->inc($active_player_id);
		}
		$this->notifyOffspring($active_player_id, $completedExchange->offspring);

		return NextPlayer::class;
	}

	#[PossibleAction]
	public function actPurchaseTile(int $active_player_id, int $from_player_id, int $barn_pos, int $enclosure_id, int $enclosure_pos): mixed
	{
        $model = $this->createModel($active_player_id);
		$dest = new Space($enclosure_id, $enclosure_pos);
		$result = $model->purchaseTile($from_player_id, $barn_pos, $dest);
		$purchased = $result['tiles'][0];
		$this->notify->all('PurchaseTile', '${player_name} purchased ${tile_type} from ${player_name2} into ${enclosure}', [
			'player_id' => $active_player_id,
			'player_id2' => $from_player_id,
			'tile_type' => $purchased->tile->type->value,
			'placed_tiles' => array_map(fn ($pt) => $pt->serialize(), $result['tiles']),
    		'moneys' => $model->currentMoneys()->serialize(),
			'enclosure' => Enclosure::translated($enclosure_id),
			'tile_description' => $purchased->tile->type->translated(),
			'i18n' => [
				'tile_description',
				'enclosure',
				'seller_player_name',
			]
		]);
		$this->game->stats->PLAYER_TILESPURCHASED->inc($active_player_id);
		$this->game->stats->PLAYER_TILESSOLD->inc($from_player_id);
		$this->notifyOffspring($active_player_id, $result['offspring']);
		$this->notifyBonus($active_player_id, $enclosure_id, $result['enclosure_bonus']);

		return NextPlayer::class;
	}

	private function notifyBonus(int $active_player_id, int $enclosure_id, ?int $bonus): void {
		if ($bonus !== null) {
			$this->game->stats->PLAYER_COMPLETIONBONUSCOINS->inc($active_player_id, $bonus);
			$this->notify->all('EnclosureBonus', '${player_name} completed ${enclosure} and received ${bonus} coins', [
				'player_id' => $active_player_id,
				'enclosure' => Enclosure::translated($enclosure_id),
				'bonus' => $bonus,
				'i18n' => [
					'enclosure'
				]
			]);
		}
	}

	private function notifyOffspring(int $active_player_id, ?Offspring $offspring): void {
		if ($offspring !== null) {
			$this->game->stats->PLAYER_OFFSPRINGPRODUCED->inc($active_player_id);
			$this->notify->all('PurchaseTileOffspring', '${player_name} produced an offspring ${tile_type}',[
				'player_id' => $active_player_id,
				'offspring' => $offspring->serialize(),
				'tile_type' => $offspring->child->tile->type->value,
				'tile_description' => $offspring->child->tile->type->translated(),
				'i18n' => [
					'tile_description',
				]
			]);
		}
	}

	#[PossibleAction]
	public function actDiscardTile(int $active_player_id, int $barn_pos): mixed
	{
        $model = $this->createModel($active_player_id);
		$tile = $model->discardBarnTile($barn_pos);
		$this->notify->all('DiscardTile', '${player_name} discarded tile ${tile_type}',[
			'player_id' => $active_player_id,
			'tile' => $tile->serialize(),
			'tile_type' => $tile->type->value,
			'tile_description' => $tile->type->translated(),
        	'moneys' => $model->currentMoneys()->serialize(),
			'space' => new Space(0, $barn_pos),
			'i18n' => [
				'tile_description',
			]
		]);
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
