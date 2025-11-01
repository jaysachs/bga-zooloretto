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

class PlaceTile extends AbstractState
{
    function __construct(private Game $game)
    {
        parent::__construct(
            game: $game,
            id: 3,
            type: StateType::ACTIVE_PLAYER,
    		description: clienttranslate('${actplayer} must place a tile on a Wagon.'),
    		descriptionMyTurn: clienttranslate('${you} must place a tile on a Wagon.'),
        );
    }

    public function getArgs(int $active_player_id): array
    {
        return [];
    }

    #[PossibleAction]
    public function actPlaceTile(int $active_player_id, int $x, int $y): mixed {

		$model = $this->createModel();
		// $x is wagon number
		// $y is positioin on wagon
		/** @var Tile */
		$tile = $model->placeDrawnTileOnWagon($x, $y);

		$this->notify->all(
			"PlaceTile",
			clienttranslate( '${player_name} placed the ${translatedval} tile on the ${pos} space of the ${wag} wagon.'),
			[
				'player_id' => $active_player_id,
				'id' => $tile->id,
				'val' => $tile->type->value,
				'x' => $x,
				'y' => $y,
				'translatedval' => $tile->type->translated(),
				'pos' => Decoder::Pos($y),
				'wag' => Decoder::Pos($x),
				'i18n' => array( 'translatedval', 'pos', 'wag' )
			]
		);
        return NextPlayer::class;
    }

    function zombie(int $playerId): mixed {
        // FIXME
        return "";
    }
}
