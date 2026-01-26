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
use Bga\Games\zooloretto\Model\PossibleMove;
use Bga\Games\zooloretto\Model\Space;

class MoveTile extends AbstractState
{
    function __construct(Game $game)
    {
        parent::__construct(
            game: $game,
            id: 7,
            type: StateType::ACTIVE_PLAYER,
            descriptionMyTurn: clienttranslate('${you} must choose a tile to move and its destination'),
            description: clienttranslate('${player_name} must choose a tile to move and its destination'),
        );
    }

    /** @return array<string,mixed> */
    public function getArgs(int $active_player_id): array {
        $model = $this->createModel($active_player_id);
		$pms = array_map(fn (PossibleMove $pm) => $pm->serialize(), $model->getPossibleMoves());
		return [
			'possible_moves' => $pms,
		];
    }

    #[PossibleAction]
    public function actUndo(int $active_player_id): mixed {
        $this->game->undoRestorePoint();
        return null;
    }

	public function actMoveTile(int $active_player_id, int $src_id, int $src_pos, int $dest_id, int $dest_pos): mixed
	{
        $model = $this->createModel($active_player_id);
		$dest = new Space($dest_id, $dest_pos);
		$result = $model->moveTile(new Space($src_id, $src_pos), $dest);
		$placed_tile = $result['placed_tile'];
		$tile = $placed_tile->tile;
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
				'enclosure_summaries' => [
					EnclosureSummary::forEnclosure($active_player_id, $model->getEnclosuresForPlayer($active_player_id)[$src_id])->serialize(),
					EnclosureSummary::forEnclosure($active_player_id, $model->getEnclosuresForPlayer($active_player_id)[$dest_id])->serialize(),
				],
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

    function zombie(int $player_id): mixed {
        $model = $this->createModel($player_id);
        $pm = $model->getPossibleMoves()[0];
        $dest = $pm->dests[0];
        return $this->actMoveTile($player_id, $pm->src->enclosure_id, $pm->src->pos,
                                  $dest->space->enclosure_id, $dest->space->pos);
    }

}
