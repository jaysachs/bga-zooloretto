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
use Bga\Games\zooloretto\Model\Enclosure;
use Bga\Games\zooloretto\Model\EnclosureSummary;
use Bga\Games\zooloretto\Model\PossiblePlacement;
use Bga\Games\zooloretto\Model\Space;

class DeliverTruckTiles extends AbstractState
{
    function __construct(Game $game)
    {
        parent::__construct(
            game: $game,
            id: 5,
            type: StateType::ACTIVE_PLAYER,
            descriptionMyTurn: clienttranslate('${you} must deliver tiles from the truck'),
			description: clienttranslate('${actplayer} must deliver tiles from the truck'),
        );
    }

    /** @return array<string,mixed> */
    public function getArgs(int $active_player_id): array {
        // return truck_id, and map of positions to possible destinations
        $model = $this->createModel($active_player_id);
        $truck_id = $model->getDeliveringTruckId();
        if (!$truck_id) {
            throw new \BgaUserException("no truck delivering");
        }
        $truck = $model->getTruck($truck_id);
        $pps = PossiblePlacement::possiblePlacementFor($active_player_id, $truck, $model->getEnclosuresForPlayer($active_player_id));
        return [
            "truck_id" => $truck_id,
            "possible_placements" => array_map(fn ($pp) => [
                'truck_pos' => $pp->truck_pos,
                'tile' => $pp->tile_type,
                'tile_id' => $truck->tileAt($pp->truck_pos)->id,
                'dests' => array_map(fn ($ppe) => $ppe->serializeNoNext(), $pp->next),
            ], $pps->placements),
        ];
    }

    #[PossibleAction]
    public function actUndo(int $active_player_id): mixed {
        $this->game->undoRestorePoint();
        return PlayerTurn::class;
    }

    #[PossibleAction]
    public function actConfirmDelivery(int $active_player_id): mixed {
        $model = $this->createModel($active_player_id);
        $truck = $model->setDeliveryCompleted();
        $this->notify->all('DeliveryCompleted','',[
            'truck_id' => $truck->id,
            'player_id' => $truck->taken_by,
			'moneys' => $model->currentMoneys()->serialize(),
			'enclosure_summaries' => array_map(
					fn ($e) => EnclosureSummary::forEnclosure($active_player_id, $e)->serialize(),
					$model->getEnclosuresForPlayer($active_player_id)
			),
        ]);
        return NextPlayer::class;
    }

    #[PossibleAction]
    public function actDeliverTile(int $active_player_id, int $truck_pos, int $enclosure_id, int $enclosure_pos, bool $confirm_if_done): mixed {
        $model = $this->createModel($active_player_id);
        $delivery = $model->placeTruckTile($truck_pos, new Space($enclosure_id, $enclosure_pos));
        $this->notify->all('DeliverTruckTile', clienttranslate('${player_name} delivered ${tile_type} to ${enclosure_description}'), [
            'player_id' => $active_player_id,
            'delivery' => $delivery->serialize(),
            'tile_type' => $delivery->tile->type,
            'enclosure_description' => Enclosure::translated($enclosure_id),
            'i18n' => [
                'enclosure_description'
            ]
        ]);
        if ($model->getTruck($delivery->truck_id)->isEmpty() && $confirm_if_done) {
            return $this->actConfirmDelivery($active_player_id);
        }
        return DeliverTruckTiles::class;
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
            return $this->actConfirmDelivery($player_id);
        }
        $space = $pps[0]->next[0]->space;
        return $this->actDeliverTile($player_id, $pps[0]->truck_pos, $space->enclosure_id, $space->pos, true);
    }
}
