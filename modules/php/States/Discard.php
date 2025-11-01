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

class Discard extends AbstractState
{
    function __construct(private Game $game)
    {
        parent::__construct(
            game: $game,
            id: 10,
            type: StateType::ACTIVE_PLAYER,
    		description: clienttranslate('${actplayer} must discard a tile from his Barn.'),
            descriptionMyTurn: clienttranslate('${you} must discard a tile from your Barn.'),
        );
    }

    public function getArgs(int $active_player_id): array
    {
        return [];
    }

    #[PossibleAction]
    public function actConfirmDiscard(int $active_player_id, int $tileid): mixed {
		$player_id = $active_player_id;
        $model = $this->createModel();

        $player = $model->getPlayer($player_id);
        $tile = $model->discardBarnTile($player, $tileid);
		$this->playerStats->inc( "coinsspent", $player->moneySpent(), $player_id);

		$val = $tile->type->value;

		$this->notify->all( "ConfirmDiscard", clienttranslate( '${player_name} discarded the ${translatedval} from his Barn.'),
		array(
			'player_id' => $player_id,
			'tileid' => $tileid,
			'val' => $val,
			'translatedval' => Decoder::Animal($val),
			'i18n' => array( 'translatedval' )
		) );

        return NextPlayer::class;
    }

    #[PossibleAction]
    public function actBack(): mixed {
        return PlayerTurn::class;
    }

    function zombie(int $playerId): mixed {
        // FIXME
        return "";
    }
}
