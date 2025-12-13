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
use Bga\Games\zooloretto\Game;
use Bga\Games\zooloretto\Model\Model;
use Bga\Games\zooloretto\Model\Truck;
use BgaSystemException;

class PlaceDrawnTile extends AbstractState
{
    function __construct(Game $game)
    {
        parent::__construct(
            game: $game,
            id: 23,
            type: StateType::ACTIVE_PLAYER,
    		description: clienttranslate('${actplayer} must place a tile on a truck.'),
    		descriptionMyTurn: clienttranslate('${you} must place a tile on a truck.'),
        );
    }

    /** @return array<string,mixed> */
    public function getArgs(int $active_player_id): array
    {
        $model = new Model($active_player_id);
        $available = [];
        foreach ($model->getTrucks() as $truck) {
            $pos = $truck->firstFreePosition();
            if ($pos > 0) {
                $available[] = [ 'truck_id' => $truck->id, 'truck_pos' => $pos ];
            }
        }
        $stock = $model->getStock();
        return [
            'available_spaces' => $available,
            'drawn_from_endgame_pile' => $stock->inLastRound(),
            'tile' => $stock->drawn->serialize(),
        ];
    }

    #[PossibleAction]
    public function actPlaceDrawnTileInTruck(int $active_player_id, int $truck_id, int $truck_pos): mixed {

        $model = $this->createModel($active_player_id);
		$tile = $model->placeDrawnTileOnTruck($truck_id, $truck_pos);
        $stock = $model->getStock();
		$this->notify->all(
			"PlaceDrawnTileInTruck",
			clienttranslate( '${player_name} placed ${tile_type} onto truck ${truck_id}.'),
			[
				'player_id' => $active_player_id,
                'truck_id' => $truck_id,
                'tile' => $tile->serialize(),
                'truck_pos' => $truck_pos,
                'tile_type' => $tile->type->value,
				'tile_description' => $tile->type->translated(),
                'primary_pile_size' => $stock->primaryCount(),
                'endgame_pile_size' => $stock->endgameCount(),
                'drawn_from_endgame_pile' => $stock->inLastRound(),
				'i18n' => [ 'tile_description' ],
			]
		);
        return NextPlayer::class;
    }

    function zombie(int $player_id): mixed {
        $model = new Model($player_id);
        foreach ($model->getTrucks() as $truck) {
            $pos = $truck->firstFreePosition();
            if ($pos > 0) {
                return $this->actPlaceDrawnTileInTruck($player_id, $truck->id, $pos);
            }
        }
        throw new BgaSystemException("In PlaceDrawnTile state, but no trucks have an open spot?!");
        // could instead keep making progress ... ?
        // return NextPlayer::class;
    }
}
