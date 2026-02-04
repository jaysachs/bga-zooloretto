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

use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\Games\zoolorettoalpha\Game;
use Bga\Games\zoolorettoalpha\Model\Cost;
use Bga\Games\zoolorettoalpha\Model\EnclosureSummary;
use Bga\Games\zoolorettoalpha\Model\Moneys;
use Bga\Games\zoolorettoalpha\Model\Space;

class DiscardTile extends AbstractState
{
    function __construct(Game $game)
    {
        parent::__construct(
            game: $game,
            id: 30,
            type: StateType::ACTIVE_PLAYER,
            descriptionMyTurn: clienttranslate('${you} must choose a tile to discard'),
            description: clienttranslate('${actplayer} must choose a tile to discard'),
        );
    }

    #[PossibleAction]
    public function actUndo(int $active_player_id): mixed {
        $this->game->undoRestorePoint();
        return PlayerTurn::class;
    }

    /** @return array<string,mixed> */
	public function getArgs(int $active_player_id): array
	{
        $model = $this->createModel($active_player_id);
		return [
			'possible_discards' => [
				'money_delta' => Moneys::costPlayerDelta($active_player_id, Cost::DISCARD)->serialize(),
				'spaces' => array_map(fn ($s) => $s->serialize(), $model->getDiscardables())
			],
        ];
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

	function zombie(int $player_id): mixed {
        $model = $this->createModel($player_id);
        $ds = $model->getDiscardables();
        if (count($ds) == 0) {
            return PlayerTurn::class;
        }
        return $this->actDiscardTile($player_id, $ds[0]->pos);
    }

}
