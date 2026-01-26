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

use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\Games\zooloretto\Game;
use Bga\Games\zooloretto\Model\PossiblePlacement;
use Bga\Games\zooloretto\Model\Space;

class PlaceTruckTiles extends AbstractState
{
    function __construct(Game $game)
    {
        parent::__construct(
            game: $game,
            id: 5,
            type: StateType::ACTIVE_PLAYER,
            descriptionMyTurn: clienttranslate('${you} must place tiles from the truck'),
			description: clienttranslate('${actplayer} must place tiles from the truck'),
        );
    }

    /** @return array<string,mixed> */
    public function getArgs(int $active_player_id): array {
        // return truck_id, and map of positions to possible destinations
        $model = $this->createModel($active_player_id);
        $truck_id = $model->getActivePlayer()->truck_taken;
        if (!$truck_id) {
            throw new \BgaUserException("no truck selected");
        }
        $truck = $model->getTruck($truck_id);
        $pps = PossiblePlacement::possiblePlacementFor($active_player_id, $truck, $model->getEnclosuresForPlayer($active_player_id));
        return [
            "truck_id" => $truck_id,
            "possible_placements" => array_map(fn ($pp) => [
                'truck_pos' => $pp->truck_pos,
                'tile' => $pp->tile_type,
                'tile_id' => $truck->tileAt($pp->truck_pos)->id,
                'dests' => array_map(fn ($ppe) => $ppe->space->serialize(), $pp->next),
            ], $pps->placements),
        ];
    }

    #[PossibleAction]
    public function actUndo(int $active_player_id): mixed {
        $this->game->undoRestorePoint();
        return null;
    }

    #[PossibleAction]
    public function actConfirmPlacements(int $active_player_id): mixed {
        $model = $this->createModel($active_player_id);
        $truck_id = $model->getActivePlayer()->truck_taken;
        if (!$truck_id) {
            throw new \BgaUserException("no truck selected");
        }
        $truck = $model->getTruck($truck_id);
        if (!$truck->isEmpty()) {
            throw new \BgaUserException("truck {$truck_id} not empty");
        }
        return NextPlayer::class;
    }

    #[PossibleAction]
    public function actPlaceTile(int $active_player_id, int $truck_pos, int $enclosure_id, int $enclosure_pos): mixed {
        $model = $this->createModel($active_player_id);
        $delivery = $model->placeTruckTile($truck_pos, new Space($enclosure_id, $enclosure_pos));
        $this->notify->all('truckTilePlaced', clienttranslate('${player_name} placed ${tile} to ${enclosure_id}:${enclosure_pos}'), [
            'player_id' => $active_player_id,
            'delivery' => $delivery->serialize(),
            'tile' => $delivery->tile->type,
            'enclosure_id' => $enclosure_id,
            'enclosure_pos' => $enclosure_pos,
            'tile_description' => $delivery->tile->type->translated(),
            'i18n' => [
                'tile_description',
            ]
        ]);
        return null;
    }

	function zombie(int $player_id): mixed {
        $model = $this->createModel($player_id);
        $truck_id = $model->getActivePlayer()->truck_taken;
        if (!$truck_id) {
            throw new \BgaUserException("no truck selected");
        }
        $truck = $model->getTruck($truck_id);
        $pps = PossiblePlacement::possiblePlacementFor($player_id, $truck, $model->getEnclosuresForPlayer($player_id))->placements;
        if (count($pps) == 0) {
            return $this->actConfirmPlacements($player_id);
        }
        $space = $pps[0]->next[0]->space;
        return $this->actPlaceTile($player_id, $pps[0]->truck_pos, $space->enclosure_id, $space->pos);
    }
}
