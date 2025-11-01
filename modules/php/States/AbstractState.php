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
use Bga\Games\zooloretto\Model\Model;

abstract class AbstractState extends GameState
{
    function __construct(
        private Game $game,
        int $id,
        StateType $type,
        ?string $description = '',
        ?string $descriptionMyTurn = '',
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

    protected function createModel(): Model {
        return new Model($this->game);
    }

    protected function giveExtraTime(int $player_id, ?int $specificTime = null): void {
        $this->game->giveExtraTime($player_id, $specificTime);
    }

    protected function activeNextPlayer(): void {
        $this->game->activeNextPlayer();
    }
}
