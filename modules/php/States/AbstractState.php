<?php

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * zooloretto implementation : © Jay Sachs <vagabond@covariant.org>
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
use Bga\GameFramework\States\GameState;
use Bga\Games\zooloretto\Game;
use Bga\Games\zooloretto\Model\Enclosure;
use Bga\Games\zooloretto\Model\Model;
use Bga\Games\zooloretto\Model\Offspring;
use Bga\Games\zooloretto\Model\Serializable;
use Bga\Games\zooloretto\Utils\DefaultDb;

abstract class AbstractState extends GameState
{
    function __construct(
        protected Game $game,
        int $id,
        StateType $type,
        string $description = '',
        string $descriptionMyTurn = '',
        bool $updateGameProgression = false
    ) {
        parent::__construct(
            game: $game,
            id: $id,
            type: $type,
            name: null,
            description: $description,
            descriptionMyTurn: $descriptionMyTurn,
            updateGameProgression: $updateGameProgression);
    }

    protected function createModel(int $player_id, bool $readonly = false): Model {
        return new Model($player_id, new DefaultDb($readonly));
    }

    protected function giveExtraTime(int $player_id, ?int $specificTime = null): void {
        $this->game->giveExtraTime($player_id, $specificTime);
    }

    protected function activeNextPlayer(): void {
        $this->game->activeNextPlayer();
    }

    protected function stockCount(int $count): int {
        return $this->game->stockCount($count);
    }

    /** @param array<Serializable> $a */
    protected static function serializeArray(array $a): mixed {
        return array_map(fn (Serializable $x) => $x->serialize(), $a);
    }

	protected function notifyCompletionCoins(int $active_player_id, int $enclosure_id, ?int $bonus): void {
		if ($bonus !== null) {
			$this->game->stats->PLAYER_COMPLETIONBONUSCOINS->inc($active_player_id, $bonus);
			$this->notify->all(
                'CompletionCoins',
                clienttranslate('${player_name} completed ${enclosure} and received ${coins}'),
                [
                    'player_id' => $active_player_id,
                    'enclosure' => Enclosure::translated($enclosure_id),
                    'coins' => $bonus,
                    'i18n' => [
                        'enclosure'
                    ]
                ]
            );
		}
	}

	protected function notifyOffspring(int $active_player_id, ?Offspring $offspring): void {
		if ($offspring !== null) {
			$this->game->stats->PLAYER_OFFSPRINGPRODUCED->inc($active_player_id);
            if ($offspring->child->space->enclosure_id == 0) {
                $this->game->stats->PLAYER_OFFSPRINGPRODUCEDINBARN->inc($active_player_id);
            }
			$this->notify->all(
                'Offspring',
                clienttranslate('${player_name} produced an offspring ${tile_type}'),
                [
                    'player_id' => $active_player_id,
                    'offspring' => $offspring->serialize(),
                    'tile_type' => $offspring->child->tile->type->value,
                    'tile_description' => $offspring->child->tile->type->translated(),
                    'i18n' => [
                        'tile_description',
                    ]
                ]
            );
            /*
			$m = $offspring->child->money_delta;
			if ($m) {
				$this->notifyBonus($active_player_id, $offspring->child->space->enclosure_id, $m->players[$active_player_id]);
			}
                */
		}
	}
}
