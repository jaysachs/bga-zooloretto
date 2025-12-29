<?php

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * Zooloretto implementation : © Jay Sachs <vagabond@covariant.org>
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

namespace Bga\Games\zooloretto;

use Bga\GameFramework\Actions\Debug;
use Bga\Games\zooloretto\Model\DefaultDb;
use Bga\Games\zooloretto\Model\Model;
use Bga\Games\zooloretto\Model\PersistentStore;
use Bga\Games\zooloretto\Model\Space;
use Bga\Games\zooloretto\Model\Stock;
use Bga\Games\zooloretto\Model\Tile;
use Bga\Games\zooloretto\Model\TileType;
use Bga\Games\zooloretto\Model\Truck;
use Bga\Games\zooloretto\States\PlayerTurn;

require_once(APP_GAMEMODULE_PATH . "module/table/table.game.php");

// require_once("Stats.php");

class Game extends \Bga\GameFramework\Table
{

	public Stats $stats;
	public function __construct()
	{
		parent::__construct();

		$logDecorator = new LogDecorator(\Closure::fromCallable($this->getPlayerNameById(...)));
		$this->notify->addDecorator($logDecorator->playerNames(...));
 		$this->stats = Stats::createForGame($this);

		$this->initGameStateLabels([]);
	}

	/*
        getAllDatas:

        Gather all informations about current game situation (visible by the current player).

        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
	/** @return array<string,mixed> */
	protected function getAllDatas(): array
	{
		$model = new Model(intval($this->getActivePlayerId()));
		$stock = $model->getStock();
		$encs = [];
		foreach ($model->getAllPlayers() as $player) {
			$contents = [];
			foreach ($model->getEnclosuresForPlayer($player->id) as $e) {
				foreach ($e->nonEmptyContents() as $pos => $tile) {
					$contents[] = [
						'space' => new Space($e->id, $pos)->serialize(),
						'tile' => $tile->serialize(),
					];
				}
			}
			$encs[$player->id] = $contents;
		}
		$datas = [
            'trucks' => array_map(function (Truck $truck): array {
				$tiles = $truck->getAllTiles();
				return [
					'truck_id' => $truck->id,
					'taken_by_player_id' => $truck->taken_by,
					'contents' => array_map(
						function(Tile $tile, int $pos): array {
							return ['pos' => $pos, 'tile' => $tile == null ? null : $tile->serialize() ];
						},
						$tiles,
						array_keys($tiles)),
				];
			}, $model->getTrucks()),
            'enclosures' => $encs,
            'primary_pile_size' => $this->stockCount($stock->primaryCount()),
            'endgame_pile_size' => $stock->endgameCount(),
            'drawntile' => ($stock->drawn == null) ? null : $stock->drawn->serialize(),
            'lastround' => $stock->inLastRound(),
			'bank_money' => $model->bankMoney(),
			'tile_translations' => array_map(fn ($t) => [
				'type' => $t->value,
				'name' => $t->translated(),
			], TileType::cases()),
		];
		foreach ($model->getAllPlayers() as $player) {
			$datas['players'][$player->id]['player_id'] = $player->id;
			$datas['players'][$player->id]['money'] = $player->money;
			$datas['players'][$player->id]['purchased_extensions'] = $player->purchased_extensions;
		}
		$isEndScore = intval($this->gamestate->state_id()) >= 99;
  		$datas['endScores'] = $isEndScore ? new Model(0)->computeScores() : null;

        return $datas;
	}

	/*
        getGameProgression:

        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).

        This method is called each time we are in a game state with the "updateGameProgression" property set to true
        (see states.inc.php)
    */
	function getGameProgression(): int
	{
		$model = new Model(0);
		$stock = $model->getStock();
        $numPlayers = count($model->getAllPlayers());
		return intval(100 * $stock->percentComplete($numPlayers));
	}

	/*
        upgradeTableDb:

        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.

    */

	function upgradeTableDb($from_version): void
	{
		// $from_version is the current version of this game database, in numerical form.
		// For example, if the game was running with a release of your game named "140430-1345",
		// $from_version is equal to 1404301345

		// Example:
		//        if( $from_version <= 1404301345 )
		//        {
		//            // ! important ! Use DBPREFIX_<table_name> for all tables
		//
		//            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
		//            self::applyDbUpgradeToAllDB( $sql );
		//        }
		//        if( $from_version <= 1405061421 )
		//        {
		//            // ! important ! Use DBPREFIX_<table_name> for all tables
		//
		//            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
		//            self::applyDbUpgradeToAllDB( $sql );
		//        }
		//        // Please add your future database scheme changes here
		//
		//


	}

	/** @param array<mixed> $options */
	protected function setupNewGame($players, $options = array()): mixed
	{
        $gameinfos = $this->getGameinfos();
		/** @var list<string> */
        $default_colors = $gameinfos['player_colors'];
        Utils::shuffle($default_colors);
		$query_values = [];
        foreach ($players as $player_id => $player) {
            // Now you can access both $player_id and $player array
            $query_values[] = vsprintf("('%s', '%s', '%s', '%s', '%s')", [
                $player_id,
                array_shift($default_colors),
                $player["player_canal"],
                addslashes($player["player_name"]),
                addslashes($player["player_avatar"]),
            ]);
        }

        // Create players based on generic information.
        static::DbQuery(
            sprintf(
                "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES %s",
                implode(",", $query_values)
            )
        );

		// self::reattributeColorsBasedOnPreferences( $players, $gameinfos['player_colors'] );
		self::reloadPlayersBasicInfos();

		$this->stats->initAll();
		Model::createNewGame(array_keys($players));

		$this->activeNextPlayer();
        $this->stats->TABLE_TURNS_NUMBER->inc();
        /** @phpstan-ignore return.void */
		return PlayerTurn::class;
	}

	public function stockCount(int $count): int {
		$show_remaining = $this->tableOptions->get(100) === 2;
		if ($count <= 15 || $show_remaining) { return $count; }
		return 1000;
	}

	/*
    function optionEnabled(TableOption $option): bool
    {
        return $this->tableOptions->get($option->value) > 0;
    }
    */

	#[Debug(reload: true)]
	public function debug_setMoney(int $player_id, int $amount): void {
		new DefaultDb()->execute("UPDATE player SET money = $amount WHERE player_id=$player_id");
	}

	#[Debug(reload: true)]
	public function debug_fillTrucks(): void {
		$player_id = intval($this->getActivePlayerId());
		$model = new Model($player_id);
		$trucks = $model->getTrucks();
		while (array_sum(array_map(fn (Truck $t) => $t->freeSpaces(), $trucks)) > 0) {
			$drawn = $model->drawTile()->drawn;
			foreach ($trucks as $truck) {
				$p = $truck->firstFreePosition();
				if ($p > 0) {
					$model->placeDrawnTileOnTruck($truck->id, $p);
					break;
				}
			}
		}
	}

	#[Debug(reload: true)]
	public function debug_drawN(int $n): void {
		$model = new Model(0);
		$stock = $model->getStock();
		while ($n-- > 0) {
			$stock = $model->drawTile();
			$drawn = $stock->drawn;
			$stock->removeDrawnTile();
		}
		new PersistentStore()->updateStock($stock);
	}
}
