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
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\Games\zooloretto\Game;
use Bga\Games\zooloretto\Model\Enclosure;
use Bga\Games\zooloretto\Model\EnclosureSummary;
use Bga\Games\zooloretto\Model\Moneys;
use Bga\Games\zooloretto\Model\PossibleExchange;
use Bga\Games\zooloretto\Model\Space;

class ExchangeTiles extends AbstractState
{
    function __construct(Game $game)
    {
        parent::__construct(
            game: $game,
            id: 8,
            type: StateType::ACTIVE_PLAYER,
            description: clienttranslate('legacy state exchange 8'),
        );
    }

    /** @return array<string,mixed> */
	public function getArgs(int $active_player_id): array
	{
        $model = $this->createModel($active_player_id);
		$pxs = [];
		foreach ($model->getPossibleExchanges() as $px) {
			$pxs[] = [
				'money_delta' => $px->moneyDelta->serialize(),
				'offspring' => array_map(
					fn ($o) => $o->serialize(), $px->offspring),
				'src' => array_map(
					fn ($p) => new Space($px->src_enclosure_id, $p)->serialize(), $px->src_positions),
				'dest' => array_map(
					fn ($p) => new Space($px->dest_enclosure_id, $p)->serialize(), $px->dest_positions),
			];
		}
		return [
			'possible_exchanges' => $pxs,
		];
	}


    #[PossibleAction]
    public function actUndo(int $active_player_id): mixed {
        $this->game->undoRestorePoint();
        return PlayerTurn::class;
    }

    /**
	 * @param list<int> $src_positions
	 * @param list<int> $dest_positions
	 */
	#[PossibleAction]
	public function actExchangeEnclosureAnimals(
		int $active_player_id,
		int $src_enclosure_id,
		#[JsonParam()] array $src_positions,
		int $dest_enclosure_id,
		#[JsonParam] array $dest_positions): mixed
	{
        $model = $this->createModel($active_player_id);
		$completedExchange = $model->exchange(new PossibleExchange($src_enclosure_id, $src_positions, $dest_enclosure_id, $dest_positions, [], new Moneys(0)));

		$this->notify->all(
            'ExchangeEnclosureAnimals',
			// FIXME: need to handle barn
            clienttranslate('${player_name} exchanged ${src_tile_type} and ${dest_tile_type} between ${src_enclosure} and ${dest_enclosure}'),
            [
                'player_id' => $active_player_id,
                'placed_tiles' => array_map(fn($pt) => $pt->serialize(), $completedExchange->placedTiles),
                'src_enclosure_id' => $completedExchange->src_enclosure_id,
                'src_enclosure' => Enclosure::translated($completedExchange->src_enclosure_id),
                'moneys' => $model->currentMoneys()->serialize(),
                'dest_enclosure_id' => $completedExchange->dest_enclosure_id,
                'dest_enclosure' => Enclosure::translated($completedExchange->dest_enclosure_id),
                'src_tile_type' => $completedExchange->src_tile_type,
                'dest_tile_type' => $completedExchange->dest_tile_type,
                'src_tile_description' => $completedExchange->src_tile_type->translated(),
                'dest_tile_description' => $completedExchange->dest_tile_type->translated(),
                'enclosure_summaries' => [
                    EnclosureSummary::forEnclosure($active_player_id, $model->getEnclosuresForPlayer($active_player_id)[$src_enclosure_id])->serialize(),
                    EnclosureSummary::forEnclosure($active_player_id, $model->getEnclosuresForPlayer($active_player_id)[$dest_enclosure_id])->serialize(),
                ],
                'i18n' => [
                    'src_tile_description',
                    'dest_tile_description',
                    'src_enclosure',
                    'dest_enclosure',
                ]
            ]
        );

		$this->game->stats->PLAYER_EXCHANGEACTIONS->inc($active_player_id);
		if ($src_enclosure_id == 0 || $dest_enclosure_id == 0) {
			$this->game->stats->PLAYER_EXCHANGEACTIONSWITHBARN->inc($active_player_id);
		}
		$this->notifyOffspring($active_player_id, $completedExchange->offspring);

		return NextPlayer::class;
	}

	function zombie(int $player_id): mixed {
        $model = $this->createModel($player_id);
        $px = $model->getPossibleExchanges()[0];
        return $this->actExchangeEnclosureAnimals($player_id,
           $px->src_enclosure_id, $px->src_positions,
           $px->dest_enclosure_id, $px->dest_positions);
   }
}
