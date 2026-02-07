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
use Bga\Games\zoolorettoalpha\Model\Enclosure;
use Bga\Games\zoolorettoalpha\Model\EnclosureSummary;
use Bga\Games\zoolorettoalpha\Model\PossiblePurchase;
use Bga\Games\zoolorettoalpha\Model\Space;

class PurchaseTile extends AbstractState
{
    function __construct(Game $game)
    {
        parent::__construct(
            game: $game,
            id: 29,
            type: StateType::ACTIVE_PLAYER,
            descriptionMyTurn: clienttranslate('${you} must select a tile to purchase and a destination'),
            description: clienttranslate('${actplayer} must select a tile to purchase and a destination'),
        );
    }

    /** @return array<string,mixed> */
	public function getArgs(int $active_player_id): array
	{
        $model = $this->createModel($active_player_id);
		$pb = array_map(fn (PossiblePurchase $b) => $b->serialize(), $model->getPurchaseableTiles());

		return [
			'possible_purchases' => $pb,
		];
	}

    #[PossibleAction]
    public function actUndo(int $active_player_id): mixed {
        $this->game->undoRestorePoint();
        return null;
    }

    #[PossibleAction]
	public function actPurchaseTile(int $active_player_id, int $from_player_id, int $barn_pos, int $enclosure_id, int $enclosure_pos): mixed
	{
        $model = $this->createModel($active_player_id);
		$dest = new Space($enclosure_id, $enclosure_pos);
		$result = $model->purchaseTile($from_player_id, $barn_pos, $dest);
		$purchased = $result['tiles'][0];
		$this->notify->all(
            'PurchaseTile',
            clienttranslate('${player_name} purchased ${tile_type} from ${player_name2} into ${enclosure}'),
            [
                'player_id' => $active_player_id,
                'player_id2' => $from_player_id,
                'tile_type' => $purchased->tile->type->value,
                'placed_tiles' => array_map(fn ($pt) => $pt->serialize(), $result['tiles']),
                'moneys' => $model->currentMoneys()->serialize(),
                'enclosure' => Enclosure::translated($enclosure_id),
                'tile_description' => $purchased->tile->type->translated(),
                'enclosure_summaries' => [
                    EnclosureSummary::forEnclosure($active_player_id, $model->getEnclosuresForPlayer($active_player_id)[$enclosure_id])->serialize(),
                    EnclosureSummary::forEnclosure($from_player_id, $model->getEnclosuresForPlayer($from_player_id)[0])->serialize(),
                ],
                'i18n' => [
                    'tile_description',
                    'enclosure',
                    'seller_player_name',
                ]
            ]
        );
		$this->game->stats->PLAYER_TILESPURCHASED->inc($active_player_id);
		$this->game->stats->PLAYER_TILESSOLD->inc($from_player_id);
		$this->notifyOffspring($active_player_id, $result['offspring']);
		$this->notifyBonus($active_player_id, $enclosure_id, $result['enclosure_bonus']);

		return NextPlayer::class;
	}

	function zombie(int $player_id): mixed {
        $model = $this->createModel($player_id);
		$pb = $model->getPurchaseableTiles()[0];
        $dest = $pb->dests[0]->space;
        return $this->actPurchaseTile($player_id,
                                      $pb->src_player_id, $pb->src->pos,
                                      $dest->enclosure_id, $dest->pos);
    }

}
