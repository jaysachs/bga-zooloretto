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
use Bga\Games\zooloretto\Model\Offspring;
use Bga\Games\zooloretto\Model\PossibleBuy;
use Bga\Games\zooloretto\Model\PossibleExchange;
use Bga\Games\zooloretto\Model\PossibleMove;
use Bga\Games\zooloretto\Model\Space;

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
	}

	public function getArgs(int $active_player_id): array
	{
        $model = $this->createModel($active_player_id);
		$available_trucks = array_map(
			fn (AvailableTruck $at) => $at->serialize(),
			$model->getAvailableTrucks());

		$pms = $model->getPossibleMoves();
		$pm = array_map(fn (PossibleMove $pm) => [
			'src' => $pm->src->serialize(),
			'money_delta' => $pms->moneyDelta->serialize(),
			'dests' => array_map(fn ($d) => $d->serialize(), $pm->dests),
		], $pms->moves);

		$pb = array_map(fn (PossibleBuy $b) => [
			'from_player_id' => $b->from_player_id,
			'src' => $b->move->src->serialize(),
			'money_delta' => $b->moneyDelta->serialize(),
			'dests' => array_map(fn ($d) => $d->serialize($d), $b->move->dests),
		], $model->getPurchaseableTiles());

		$pxs = [];
		$pex = $model->getPossibleExchanges();
		if ($pex) {
			foreach ($pex->exchanges as $px) {
				$pxs[] = [
					'src' => array_map(
						fn ($p) => new Space($px->src_enclosure_id, $p)->serialize(), $px->src_positions),
					'dest' => array_map(
						fn ($p) => new Space($px->dest_enclosure_id, $p)->serialize(), $px->dest_positions),
				];
			}
		}
		/*
		$px = array_map(fn (PossibleExchange $px) => [
			'src' => [
				'enclosure_id' => $px->src_enclosure_id,
				'positions' => $px->src_positions,
			],
			'dest' => [
				'enclosure_id' => $px->dest_enclosure_id,
				'positions' => $px->dest_positions,
			],
			'money_delta' => $pex->moneyDelta->serialize(),
			'offspring' => array_map(fn (Offspring $s) => $s->serialize(), $px->offspring),
		], ($pex ? $pex->exchanges : []));
*/
		$pd = array_map(fn ($s) => $s->serialize(), $model->getDiscardables());

		return [
			'can_draw' => $model->canDraw(),
			'available_trucks' => $available_trucks,
			'possible_moves' => $pm,
			'possible_purchases' => $pb,
			'possible_discards' => $pd,
			'possible_exchanges' => $pxs,
			'can_expand' => $model->canExpand(),
			'lastround' => $model->getStock()->inLastRound(),
		];
	}

	#[PossibleAction]
	public function actTakeTruckAndPlaceTiles(int $active_player_id, int $truck_id, #[JsonParam] array $placed_tiles): mixed {
        $model = $this->createModel($active_player_id);
		$truck = $model->getTruck($truck_id);

		// FIXME: this should be returned as a structure from the model method. Or as a kind of Placement.
		$coins = $truck->coinPositions();

		$deliveries = $model->placeTilesInZooAndTakeTruck($truck_id,
			array_map(fn ($pt) => new Delivery(
				$truck_id,
				intval($pt['truck_pos']),
				new Space(intval($pt['enclosure_id']),
						  intval($pt['enclosure_pos']))),
			$placed_tiles));
		$p = [];
		foreach ($coins as $coin) {
			$p[$coin] = [
				'truck_pos' => $coin,
				'placement' => 'coin',
			];
		}
		foreach ($deliveries as $del) {
			$p[$del->truck_pos] = [
				'truck_pos' => $del->truck_pos,
				'placement' => [
					'space' => $del->space->serialize(),
					'offspring' => $del->offspring ? $del->offspring->serialize() : null,
				],
			];
		}
		$p = array_values($p);
		// FIXME: give more details about placements in log
		$this->notify->all('TakeTruckAndPlaceTiles', '${player_name} placed tiles from truck ${truck_id}', [
		  'player_id' => $active_player_id,
		  'truck_id' => $truck_id,
		  'deliveries' => $p,
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

		// FIXME: pull from options
		$show_counts = false;
		// FIXME: make a method?
		$amt = function (int $count) use (&$show_counts) : int {
			if ($count <= 5 || $show_counts) { return $count; }
			else return 200;
		};

		$drawn_from_endgame_pile = $stock->inLastRound();

		$this->notify->all(
			"DrawTile",
			// FIXME: render the tile image in the log (in addition? instead?)
			clienttranslate('${player_name} drew a ${translatedval} tile.'),
			[
				'player_id' => $active_player_id,
				'tile_type' => $tile->type->value,
				'drawn_from_endgame_pile' => $drawn_from_endgame_pile,
				'primary_pile_size' => $amt($stock->primaryCount()),
				'endgame_pile_size' => $amt($stock->endgameCount()),
				'translatedval' => $tile->type->translated(),
				'i18n' => ['translatedval']
			]
		);
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
		return NextPlayer::class;
	}

	#[PossibleAction]
	public function actMoveTile(int $active_player_id, int $src_id, int $src_pos, int $dest_id, int $dest_pos): mixed
	{
        $model = $this->createModel($active_player_id);
		$src = new Space($src_id, $src_pos);
		$dest = new Space($dest_id, $dest_pos);
		$model->moveTile($src, $dest);
		$this->notify->all(
			"MoveTile",
			clienttranslate('${player_name} moved a tile'),
			[
				'player_id' => $active_player_id,
				'src' => $src->serialize(),
				'dest' => $dest->serialize(),
        		'moneys' => $model->currentMoneys()->serialize(),
			]
		);
		return NextPlayer::class;
	}

	#[PossibleAction]
	public function actExchangeEnclosureAnimals(
		int $active_player_id,
		int $src_enclosure_id,
		#[JsonParam] array $src_positions,
		int $dest_enclosure_id,
		#[JsonParam] array $dest_positions): mixed
	{
        $model = $this->createModel($active_player_id);
		$animal_types = $model->exchange(new PossibleExchange($src_enclosure_id, $src_positions, $dest_enclosure_id, $dest_positions, []));

		$to_spaces = fn($id, $ap) => array_map(fn ($p) => new Space($id, $p)->serialize(), $ap);

		$this->notify->all('ExchangeEnclosureAnimals',
		    '${player_name} exchanged ${src_animal_type} and ${dest_animal_type} between enclosures ${src_enclosure_id} and ${dest_enclosure_id}', [
			'player_id' => $active_player_id,
			'src_enclosure_id' => $src_enclosure_id,
			'src_spaces' => $to_spaces($src_enclosure_id, $src_positions),
			'dest_enclosure_id' => $dest_enclosure_id,
			'dest_spaces' => $to_spaces($dest_enclosure_id, $dest_positions),
			'src_animal_type' => $animal_types[0]->value,
			'dest_animal_type' => $animal_types[1]->value,
        	'moneys' => $model->currentMoneys()->serialize(),
		]);
		return NextPlayer::class;
	}

	#[PossibleAction]
	public function actPurchaseTile(int $active_player_id, int $from_player_id, int $barn_pos, int $enclosure_id, int $enclosure_pos): mixed
	{
        $model = $this->createModel($active_player_id);
		$tile = $model->purchaseTile($from_player_id, $barn_pos, new Space($enclosure_id, $enclosure_pos));
		$player = $model->getActivePlayer();
		$this->notify->all('PurchaseTile', '${player_name} purchased tile ${tile}', [
			'player_id' => $active_player_id,
			'from_player_id' => $from_player_id,
			'src' => new Space(0, $barn_pos),
			'dest'  => new Space($enclosure_id, $enclosure_pos),
			'tile' => $tile->type->value,
    		'moneys' => $model->currentMoneys()->serialize(),
		]);
		return NextPlayer::class;
	}

	#[PossibleAction]
	public function actDiscardTile(int $active_player_id, int $barn_pos): mixed
	{
        $model = $this->createModel($active_player_id);
		$tile = $model->discardBarnTile($barn_pos);
		$this->notify->all('DiscardTile', '${player_name} discarded tile ${tile}',[
			'player_id' => $active_player_id,
			'tile' => $tile->type->value,
        	'moneys' => $model->currentMoneys()->serialize(),
			'space' => new Space(0, $barn_pos),
		]
		);
		return NextPlayer::class;
	}

	function zombie(int $playerId): mixed
	{
		// FIXME
		return "";
	}
}
