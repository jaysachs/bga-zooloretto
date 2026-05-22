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
use Bga\GameFramework\Table;
use Bga\Games\zooloretto\Model\Enclosure;
use Bga\Games\zooloretto\Model\EnclosureSummary;
use Bga\Games\zooloretto\Model\Model;
use Bga\Games\zooloretto\Model\PersistentStore;
use Bga\Games\zooloretto\Model\Player;
use Bga\Games\zooloretto\Model\Space;
use Bga\Games\zooloretto\Model\Stock;
use Bga\Games\zooloretto\Model\Tile;
use Bga\Games\zooloretto\Model\TileType;
use Bga\Games\zooloretto\Model\Truck;
use Bga\Games\zooloretto\States\ComputeScores;
use Bga\Games\zooloretto\States\PlayerTurn;
use Bga\Games\zooloretto\Utils\Arrays;
use Bga\Games\zooloretto\Utils\DefaultDb;
use Bga\Games\zooloretto\Utils\Log;
use Bga\Games\zooloretto\Utils\Logger;

class GameLogger implements Logger {
	function __construct(private Table $table) { }

	public function debug(string $msg): void {
		$this->table->debug($msg);
	}

	public function dump(string $prefix, mixed $obj): void {
		$this->table->dump($prefix, $obj);
	}

	public function error(string $msg): void {
		$this->table->error($msg);
	}

	public function trace(string $msg): void {
		$this->table->trace($msg);
	}

	public function warn(string $msg): void {
		$this->table->warn($msg);
	}
}

class Game extends Table
{

	public Stats $stats;
	public function __construct()
	{
		parent::__construct();

		Log::setImpl(new GameLogger($this));
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
	// #[Override]
	/** @return array<string,mixed> */
	protected function getAllDatas(?int $player_id): array
	{
		$model = new Model(intval($player_id));
		$stock = $model->getStock();

		$encshapes = [];
		{
			$toShape = function (Enclosure $e, ?int $extnum = null): array {
				$result = [
					'id' => $e->id,
					'animal_capacity' => $e->animal_capacity,
					'stall_capacity' => $e->stall_capacity,
				];
				if ($extnum) {
					$result['extension'] = $extnum;
				}
				return $result;
			};
			$playerCount = count($model->getAllPlayers());
			$somePlayer = new Player(0, 0, $playerCount, 0, 0);
			foreach(Enclosure::forPlayer($somePlayer) as $e) {
				$encshapes[] = $toShape($e);
			}
			$encshapes[] = $toShape(Enclosure::extension(1), 1);
			if ($playerCount == 2) {
				$encshapes[] = $toShape(Enclosure::extension(2), 2);
			}
		}

		$encs = [];
		$esumms = [];
		{
			foreach ($model->getAllPlayers() as $player) {
				$contents = [];
				foreach ($model->getEnclosuresForPlayer($player->id) as $e) {
					$esumms[] = EnclosureSummary::forEnclosure($player->id, $e);
					foreach ($e->nonEmptyContents() as $pos => $tile) {
						$contents[] = [
							'space' => new Space($e->id, $pos)->serialize(),
							'tile' => $tile->serialize(),
						];
					}
				}
				$encs[$player->id] = $contents;
			}
		}
		$translated = [];
		foreach (TileType::cases() as $tiletype) {
			$translated[$tiletype->value] = $tiletype->translated();
		}
		$datas = [
			'players' => [],
			'enclosure_shapes' => $encshapes,
            'trucks' => array_map(fn ($t) => $t->serialize(), array_values($model->getTrucks())),
            'enclosures' => $encs,
            'primary_pile_size' => $this->stockCount($stock->primaryCount()),
            'endgame_pile_size' => $stock->endgameCount(),
            'drawntile' => ($stock->drawn == null) ? null : $stock->drawn->serialize(),
            'lastround' => $stock->inLastRound(),
			'enclosure_summaries' => array_map(fn ($s) => $s->serialize(), $esumms),
			'tile_translations' => $translated,
		];
		foreach ($model->getAllPlayers() as $player) {
			$datas['players'][$player->id]['player_id'] = $player->id;
			$datas['players'][$player->id]['money'] = $player->money;
			$datas['players'][$player->id]['purchased_extensions'] = $player->purchased_extensions;
			$datas['players'][$player->id]['extension_available'] = $player->extensionAvailable();
		}
		if (intval($this->gamestate->getCurrentMainStateId()) >= 99) {
			$datas['endScores'] = $model->computeScores();
		}

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
		$trucksTaken = count(array_filter($model->getTrucks(), fn (Truck $t) => $t->taken_by > 0));
        $numPlayers = count($model->getAllPlayers());
		$totalTiles = Tile::totalTilesForPlayerCount($numPlayers);
		$tilesToTriggerEnd = $totalTiles - Stock::LASTSET_SIZE + 1;
		$tilesTaken = $totalTiles - ($stock->primaryCount() + $stock->endgameCount());
		$basePct = intval(min(100 - $numPlayers, 100.0 * $tilesTaken / $tilesToTriggerEnd));
		return $basePct + ($stock->inLastRound() ? $trucksTaken : 0);
	}

	/*
        upgradeTableDb:

        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.

    */

	function upgradeTableDb(mixed $from_version): void
	{
		$from_version = intval($from_version);
		new UpgradeDb(new DefaultDb(), $this->gamestate, $this)->upgrade($from_version);
	}

	/** @param array<mixed> $options */
	protected function setupNewGame($players, $options = array()): mixed
	{
        $gameinfos = $this->getGameinfos();
		/** @var list<string> */
        $default_colors = $gameinfos['player_colors'];
        Arrays::shuffle($default_colors);
		$query_values = [];
        foreach ($players as $player_id => $player) {
            // Now you can access both $player_id and $player array
            $query_values[] = vsprintf("('%s', '%s', '%s')", [
                $player_id,
                array_shift($default_colors),
                addslashes($player["player_name"]),
            ]);
        }

        // Create players based on generic information.
        static::DbQuery(
            sprintf(
                "INSERT INTO player (player_id, player_color, player_name) VALUES %s",
                implode(",", $query_values)
            )
        );

		// self::reattributeColorsBasedOnPreferences( $players, $gameinfos['player_colors'] );
		self::reloadPlayersBasicInfos();

		$this->stats->initAll();
		Model::createNewGame(array_keys($players));

		$this->activeNextPlayer();
        $this->stats->TABLE_TURNS_NUMBER->inc();
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

	#[Debug]
	public function debug_fillTrucks(int $player_id): void {
		$trucksFilled = function(int $count) use (&$player_id) {
			$model = new Model($player_id);
			foreach ($model->getTrucks() as $truck) {
				if (!$truck->taken_by && $truck->freeSpaces() > 0) {
					return false;
				}
			}
			return true;
		};
		$this->debug->playUntil($trucksFilled);
	}

	#[Debug(reload: true)]
	public function debug_putTile(int $player_id, string $tile_type, string $location, int $loc_id, int $loc_pos, int $tile_id = 0): void {
		$db = new DefaultDb();
		if ($tile_id == 0) {
			$rows = $db->getSingleFieldList("SELECT 1+MAX(id) FROM tiles WHERE id < 10000 AND id >= 2000");
			$tile_id = intval($rows[0] ?? 2000);
			$db->execute("INSERT INTO tiles (id, type, player_id, location, loc_id, loc_pos) VALUES ({$tile_id}, '{$tile_type}', {$player_id}, '{$location}', {$loc_id}, {$loc_pos} )");
		} else {
			$db->execute("UPDATE tiles SET (player_id, location, loc_id, loc_pos) = ({$player_id}, '{$location}', {$loc_id}, {$loc_pos} ) WHERE id = {$tile_id}");
		}
	}

	#[Debug(reload: false)]
	public function debug_endGame(int $active_player_id): void {
		/** @phpstan-ignore argument.type */
		$this->gamestate->jumpToState(ComputeScores::class);
	}

	#[Debug(reload: true)]
	public function debug_drawN(int $active_player_id, int $n): void {
		$model = new Model($active_player_id);
		$stock = $model->getStock();
		$ps = new PersistentStore();
		while ($n-- > 0) {
			$stock = $model->drawTile();
			$drawn = $stock->drawn;
			$stock->removeDrawnTile();
			$ps->deleteTile($drawn);
		}
	}

	#[Debug(reload: true)]
	public function debug_upgrade(int $from_version): void {
		$this->upgradeTableDb($from_version);
	}
}
