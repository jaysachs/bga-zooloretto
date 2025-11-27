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

use Bga\Games\zooloretto\Model\DefaultDb;
use Bga\Games\zooloretto\Model\Enclosure;
use Bga\Games\zooloretto\Model\Model;
use Bga\Games\zooloretto\Model\Player;
use Bga\Games\zooloretto\Model\Tile;
use Bga\Games\zooloretto\Model\TileType;
use Bga\Games\zooloretto\Model\Truck;
use Bga\Games\zooloretto\States\PlayerTurn;

require_once(APP_GAMEMODULE_PATH . "module/table/table.game.php");

// require_once("Stats.php");

class Game extends \Bga\GameFramework\Table
{

	public function __construct()
	{
		parent::__construct();

		$this->notify->addDecorator(function (string $message, array $args): array {
			if (isset($args['player_id'])) {
				/** @var string[] | null */
				$i18n = null;
				if (isset($args['i18n'])) {
					$i18n = $args['i18n'];
				}
				if (!isset($args['player_name']) && str_contains($message, '${player_name}')) {
					$args['player_name'] = $this->getPlayerNameById($args['player_id']);
					if ($i18n != null) {
						$i18n[] = 'player_name';
					}
				}
				if (!isset($args['player_no'])) {
					$args['player_no'] = $this->getPlayerNoById($args['player_id']);
					if ($i18n != null) {
						$i18n[] = 'player_no';
					}
				}
			}
			return $args;
		});

		$this->initGameStateLabels([]);
	}

	/*
        getAllDatas:

        Gather all informations about current game situation (visible by the current player).

        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
	protected function getAllDatas(): array
	{
		$model = new Model($this, intval($this->getActivePlayerId()));
		$stock = $model->getStock();
		$datas = [
            'trucks' => array_map(function (Truck $truck): array {
				$tiles = $truck->getAllTiles();
				return [
					'truck_id' => $truck->id,
					'taken_by_player_id' => $truck->taken_by,
					'contents' => array_map(
						function(Tile $tile, int $pos): array {
							return ['pos' => $pos, 'tile_type' => $tile == null ? null : $tile->type->value ];
						},
						$tiles,
						array_keys($tiles)),
				];
			}, $model->getTrucks()),
            'player_enclosures' => array_merge(array_map(
				fn (Player $p) => [
					'player_id' => $p->id,
					'enclosures' => array_merge(array_map(
						function (Enclosure $e) : array {
							$contents = [];
							foreach ($e->nonEmptyContents() as $pos => $tile) {
								$contents[] = [
									'pos' => $pos,
									'tile_type' => $tile->type->value,
								];
							}
							return [
								'enclosure_id' => $e->id,
								'spaces' => $contents,
							];
						},
						$model->getEnclosuresForPlayer($p->id)
					)),
				],
				$model->getPlayers())),
            'primary_stocksize' => $stock->primaryCount(),
            'endgame_stocksize' => $stock->endgameCount(),
            'drawntile' => ($stock->drawn == null) ? null : $stock->drawn->type->value,
            'lastround' => $stock->inLastRound(),
			'tile_translations' => array_map(fn ($t) => [
				'type' => $t->value,
				'name' => $t->translated(),
			], TileType::cases()),
		];
		foreach ($model->getPlayers() as $player) {
			$datas['players'][$player->id]['player_no'] = $player->no;
			$datas['players'][$player->id]['player_id'] = $player->id;
			$datas['players'][$player->id]['money'] = $player->money;
			$datas['players'][$player->id]['purchased_extensions'] = $player->purchased_extensions;
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
		// TODO: compute and return the game progression
        return 0;
	}

	/*
        upgradeTableDb:

        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.

    */

	function upgradeTableDb($from_version)
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

	protected function setupNewGame($players, $options = array())
	{
        $gameinfos = $this->getGameinfos();
        $default_colors = $gameinfos['player_colors'];
        Utils::shuffle($default_colors);
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

		$this->doCreateGame(array_keys($players));
		$this->playerStats->init([
			'full1',
			'full2',
			'full3',
			'full4',
			'part1',
			'part2',
			'part3',
			'part4',
			'encstall1',
			'encstall2',
			'encstall3',
			'encstall4',
			'stalls',
			'leftinbarn',
			'totalpoints',
			'totalcoins',
			'coinsspent',
			'coinsreceived'
		], 0);

		$this->playerStats->incAll('coinsreceived', 2);

		$this->activeNextPlayer();
		return PlayerTurn::class;
	}

	/** @param $player_ids int[] */
	private function doCreateGame(array $player_ids) {
		Model::createNewGame($player_ids);
	}

	/*
    function optionEnabled(TableOption $option): bool
    {
        return $this->tableOptions->get($option->value) > 0;
    }

    public function debug_zc(string $zctype, int $points, bool $used, int $row, int $col): void {
        $active_player_id = intval($this->getActivePlayerId());
        $this->notify->all(
            "zigguratCardSelection",
            clienttranslate('${player_name} chose ziggurat card ${zcard}'),
            [
                "player_id" => $active_player_id,
                "player_name" => $this->getPlayerNameById($active_player_id),
                "zcard" => $zctype,
                "cardused" => $used,
                "points" => $points,
                "hex" => new RowCol($row, $col),
            ]
        );
    }
    */

	public function debug_setState(int $state): void {
		$this->gamestate->jumpToState($state);
	}

	public function debug_setMoney(int $player_id, int $amount): void {
		new DefaultDb()->execute("UPDATE player SET money = $amount WHERE player_id=$player_id");
	}

	public function debug_placeTile(int $truck_id, int $pos): void {
		$model = new Model($this, 0);
		$tile = $model->placeDrawnTileOnTruck($truck_id, $pos);
		$this->notify->all(
			"PlaceTile",
			clienttranslate( '${player_name} placed the ${translatedval} tile on space ${pos} of truck ${truck_id}.'),
			[
				'player_id' => $this->getActivePlayerId(),
				'tile_id' => $tile->id,
				'val' => $tile->type->value,
                'truck_id' => $truck_id,
                'pos' => $pos,
				'translatedval' => $tile->type->translated(),
				'i18n' => [ 'translatedval' ],
			]
		);
	}

	public function debug_fillTrucks(): void {
		$player_id = intval($this->getActivePlayerId());
		$model = new Model($this, $player_id);
		$trucks = $model->getTrucks();
		while (array_sum(array_map(fn (Truck $t) => $t->freeSpaces(), $trucks)) > 0) {
			$drawn = $model->drawTile()->drawn;
			$this->notify->all(
			"DrawTile",
			// FIXME: render the tile image in the log (in addition? instead?)
			'debug drew a ${translatedval} tile.',
			[
				// 'player_id' => 0,
				'tile_type' => $drawn->type->value,
				'drawn_from_endgame_pile' => false, // FIXME
				// 'primary_left' => $amt($stock->primaryCount()),
				// 'endgame_left' => $amt($stock->endgameCount()),
				'translatedval' => $drawn->type->translated(),
				'i18n' => ['translatedval']
			]);
			foreach ($trucks as $truck) {
				$p = $truck->firstFreePosition();
				if ($p > 0) {
					$model->placeDrawnTileOnTruck($truck->id, $p);
					$this->notify->all(
						"PlaceDrawnTileInTruck",
						'debug placed the drawn ${translatedval} tile on space ${truck_pos} of truck ${truck_id}.',
						[
							// 'player_id' => 0,
							'tile_id' => $drawn->id,
							'val' => $drawn->type->value,
							'truck_id' => $truck->id,
							'truck_pos' => $p,
							'translatedval' => $drawn->type->translated(),
							// 'primary_stock_size' => $stock->primaryCount(),
							// 'endgame_stock_size' => $stock->endgameCount(),
							'i18n' => [ 'translatedval' ],
						]

					);
					break;
				}
			}
		}
	}

	public function debug_resetGame(): void {
		// FIXME: do more tables including resetting money.
		$db = new DefaultDb();
		$player_ids = $db->getSingleFieldList("SELECT player_id FROM player ORDER BY player_id");
		$db->execute('DELETE FROM tiles');
		$db->execute('DELETE FROM trucks');
		$db->execute('DELETE FROM enclosures');
		$db->execute('DELETE FROM enclosure_contents');
		$db->execute('DELETE FROM primary_stock');
		$db->execute('DELETE FROM endgame_stock');
		$this->doCreateGame($player_ids);
		$this->gamestate->jumpToState(2);
		$this->notify->all('debugReset', '', []);
	}
}
