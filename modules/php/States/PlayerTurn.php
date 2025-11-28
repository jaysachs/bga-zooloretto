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
use Bga\Games\zooloretto\Model\Destination;
use Bga\Games\zooloretto\Model\Placement;
use Bga\Games\zooloretto\Model\PositionSet;
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

	private function serializeSpace(Space $s): mixed {
		return [
			'enclosure_id' => $s->enclosure_id,
			'pos' => $s->pos,
		];
	}

	private function serializeDestination(Destination $s): mixed {
		$result = [
			'enclosure_id' => $s->space->enclosure_id,
			'pos' => $s->space->pos,
		];
		if ($s->childSpace !== null) {
			$result['child_enclosure_id'] = $s->childSpace->enclosure_id;
			$result['child_pos'] = $s->childSpace->pos;
			$result['child_tile'] = $s->tile->type->value;
		}
		return $result;
	}

	public function getArgs(int $active_player_id): array
	{
        $model = $this->createModel();
		$tpp = $model->getTrucksWithPossiblePlacements();
		$trucks_available = array_map(fn ($id, $pp) => [
			'truck_id' => $id,
			'playable' => $pp->serialize(),
		], array_keys($tpp), array_values($tpp));

		$pm = array_map(fn (PossibleMove $pm) => [
			'src' => $this->serializeSpace($pm->src),
			'dests' => array_map(fn ($d) => $this->serializeDestination($d), $pm->dests),
		], $model->getPossibleMoves());

		$pb = array_map(fn (PossibleBuy $b) => [
			'player_id' => $b->player_id,
			'barn_pos' => $b->move->src->pos,
			'dests' => array_map(fn ($d) => $this->serializeDestination($d), $b->move->dests),
		], $model->getPurchaseableTiles());

		$px = array_map(fn (PossibleExchange $px) => [
			'src' => [
				'enclosure_id' => $px->src->enclosure_id,
				'positions' => $px->src->positions,
			],
			'dest' => [
				'enclosure_id' => $px->dest->enclosure_id,
				'positions' => $px->dest->positions,
			],
			'children' => array_map(fn (Space $s) => $this->serializeSpace($s), $px->children),
		], $model->getPossibleExchanges());

		return [
			'can_draw' => $model->canDraw(),
			'available_trucks' => $trucks_available,
			'possible_moves' => $pm,
			'possible_purchases' => $pb,
			'possible_discards' => $model->getDiscardbleBarnPos(),
			'possible_exchanges' => $px,
			'can_expand' => $model->canExpand(),
			// 'money' => $player->money,
		];
	}

	#[PossibleAction]
	public function actTakeTruckAndPlaceTiles(int $active_player_id, int $truck_id, #[JsonParam] array $placed_tiles): mixed {
		$model = $this->createModel();
		$truck = $model->getTruck($truck_id);

		// FIXME: this should be returned as a structure from the model method. Or as a kind of Placement.
		$coins = $truck->coinPositions();

		$placements = $model->placeTilesInZooAndTakeTruck($truck_id,
			array_map(fn ($pt) => new Placement(
				$truck_id,
				intval($pt['truck_pos']),
				intval($pt['enclosure_id']),
				intval($pt['enclosure_pos'])),
			$placed_tiles));
		$p = [];
		foreach ($coins as $coin) {
			$p[$coin] = [
				'truck_pos' => $coin,
				'placement' => 'coin',
			];
		}
		foreach ($placements as $pl) {
			$p[$pl->truck_pos] = [
				'truck_pos' => $pl->truck_pos,
				'placement' => [
					'enclosure_id' => $pl-> enclosure_id,
					'enclosure_pos' => $pl->enclosure_pos,
				],
			];
		}
		$p = array_values($p);
		// FIXME: give more details about placements in log
		$this->notify->all('TakeTruckAndPlaceTiles', '${player_name} placed tiles from truck ${truck_id}', [
		  'player_id' => $active_player_id,
		  'truck_id' => $truck_id,
		  'placements' => $p,
		  'money' => $model->getPlayers()[$active_player_id]->money,
		]);
		return NextPlayer::class;
	}

	#[PossibleAction]
	public function actDrawTile(int $active_player_id): mixed
	{
        $model = $this->createModel();

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
	public function actExpandZoo(int $active_player_id): mixed
	{
        $model = $this->createModel();
		$player = $model->expandZoo($active_player_id);
		$this->notify->all(
			"ExpandZoo",
			clienttranslate('${player_name} expanded their zoo.'),
			[
				'player_id' => $active_player_id,
				'purchased_extensions' => $player->purchased_extensions,
				'money' => $player->money,
			]
		);
		return NextPlayer::class;
	}

	#[PossibleAction]
	public function actMoveTile(int $active_player_id, int $src_id, int $src_pos, int $dest_id, int $dest_pos): mixed
	{
		$model = $this->createModel();
		$src = new Space($src_id, $src_pos);
		$dest = new Space($dest_id, $dest_pos);
		$model->moveTile($src, $dest);
		$this->notify->all(
			"MoveTile",
			clienttranslate('${player_name} moved a tile'),
			[
				'player_id' => $active_player_id,
				'src' => $this->serializeSpace($src),
				'dest' => $this->serializeSpace($dest),
				'money' => $model->getPlayers()[$active_player_id]->money,
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
		$model = $this->createModel();
		$animal_types = $model->exchange(new PositionSet($src_enclosure_id, $src_positions), new PositionSet($dest_enclosure_id, $dest_positions));
		$player = $model->getPlayers()[$active_player_id];
		$this->notify->all('ExchangeEnclosureAnimals',
		    '${player_name} exchanged ${src_animal_type} and ${dest_animal_type} between enclosures ${src_enclosure_id} and ${dest_enclosure_id}', [
			'player_id' => $active_player_id,
			'src_enclosure_id' => $src_enclosure_id,
			'src_positions' => $src_positions,
			'dest_enclosure_id' => $dest_enclosure_id,
			'dest_positions' => $dest_positions,
			'src_animal_type' => $animal_types[0]->value,
			'dest_animal_type' => $animal_types[1]->value,
			'money' => $player->money,
		]);
		return NextPlayer::class;
	}

	#[PossibleAction]
	public function actPurchaseTile(int $active_player_id, int $from_player_id, int $barn_pos, int $enclosure_id, int $enclosure_pos): mixed
	{
		$model = $this->createModel();
		$tile = $model->purchaseTile($from_player_id, $barn_pos, new Space($enclosure_id, $enclosure_pos));
		$player = $model->getPlayers()[$active_player_id];
		$this->notify->all('PurchaseTile', '${player_name} purchased tile ${tile}', [
			'player_id' => $active_player_id,
			'barn_pos' => $barn_pos,
			'enclosure_id' => $enclosure_id,
			'enclosure_pos' => $enclosure_pos,
			'tile' => $tile->type->value,
			'money' => $player->money,
			'from_player_id' => $from_player_id,
			'from_player_money' => $model->getPlayers()[$from_player_id]->money,
		]);
		return NextPlayer::class;
	}

	#[PossibleAction]
	public function actDiscardTile(int $active_player_id, int $barn_pos): mixed
	{
		$model = $this->createModel();
		$tile = $model->discardBarnTile($barn_pos);
		$this->notify->all('DiscardTile', '${player_name} discarded tile ${tile}',[
			'player_id' => $active_player_id,
			'tile' => $tile->type->value,
			'money' => $model->getPlayers()[$active_player_id]->money,
			'barn_pos' => $barn_pos,
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
