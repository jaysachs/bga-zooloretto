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
use Bga\Games\zooloretto\Model\Enclosure;

class PlaceTruckTiles extends AbstractState
{
    function __construct(private Game $game)
    {
        parent::__construct(
            game: $game,
            id: 25,
            type: StateType::ACTIVE_PLAYER,
            description: clienttranslate('${actplayer} must place tiles from the truck into their Zoo.'),
            descriptionMyTurn: clienttranslate('${you} must place tiles from the truck into your Zoo.'),
        );
    }

    public function getArgs(int $active_player_id): array
    {
		$model = $this->createModel();
		$taken = $model->getActivePlayer()->truck_taken;
		if ($taken == 0) {
			return [];
		}
		$truck = $model->getTruck($taken);
		$enclosures = $model->getEnclosuresForPlayer($active_player_id);
		$data = [];
		foreach ($truck->getAllTiles() as $truck_pos => $tile) {
			if (!$tile->isEmpty()) {
				$ed = [];
				foreach ($enclosures as $enclosure) {
					// We don't need to show more than one "available" position,
					//   since we force placement into the "earliest" open space
					// 0 means "not placeable"
					$ap = $enclosure->availablePos($tile->type);
					if ($ap > 0) {
						$ed[] = [
							'enclosure_id' => $enclosure->id,
							'enclosure_pos' => $ap,
						];
					}
				}
				if ($tile->type->canGoInBarn() || count($ed) > 0) {
					$data[] = [
						'truck_pos' => $truck_pos,
						'barn' => $tile->type->canGoInBarn(),
						'enclosures' => $ed,
					];
				}
			}
		}
		return [
			'truck_id' => $truck->id,
			'spaces' => $data,
		];
    }

    #[PossibleAction]
    public function actPlaceTileInZoo(int $active_player_id, int $truck_id, int $truck_pos, int $enclosure_id) : mixed {
		$model = $this->createModel();
		$enclosure_pos = $model->placeTileInZoo($truck_id, $truck_pos, $enclosure_id);
		// FIXME: this should add children, and include them in the notify
		$this->notify->all('PlaceTileInZoo', '${player_name} moved tile from truck ${truck_id} to enclosure ${enclosure_id}',
			array_merge($this->getArgs($active_player_id),
		[
			'player_id' => $active_player_id,
			// 'truck_id' => $truck_id,
			'truck_pos' => $truck_pos,
			'enclosure_id' => $enclosure_id,
			'enclosure_pos' => $enclosure_pos,
		]));
		return null;
	}

    #[PossibleAction]
    public function actConfirmTilePlacement(int $active_player_id) : mixed {
		$this->notify->all('ConfirmTilePlacement', '${player_name} confirmed their move', [
			'player_id' => $active_player_id
		]);
		return NextPlayer::class;
	}

    #[PossibleAction]
    public function actUndoTilePlacement(int $active_player_id) : mixed {
		$this->game->undoRestorePoint();
		$this->notify->all('Undo', '${player_name} undid their move', [
			'player_id' => $active_player_id
		]);
		return PlayerTurn::class;
	}

    function zombie(int $playerId): mixed {
        // FIXME
        return "";
    }
}
