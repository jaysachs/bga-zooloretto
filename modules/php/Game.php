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

use Bga\Games\zooloretto\Model\Model;
use Bga\Games\zooloretto\States\PlayerTurn;

require_once(APP_GAMEMODULE_PATH . "module/table/table.game.php");

// require_once("Stats.php");

class Game extends \Bga\GameFramework\Table
{

	public function __construct()
	{
		parent::__construct();

		$this->notify->addDecorator(function (string $message, array $args): array {
			if (isset($args['player_id']) && !isset($args['player_name']) && str_contains($message, '${player_name}')) {
				$args['player_name'] = $this->getPlayerNameById($args['player_id']);
			}
			return $args;
		});
		self::initGameStateLabels([]);
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
		$result = array();

		$current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!

		// Get information about players
		// Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
		$sql = "SELECT player_id id, player_score score, player_no no, player_name name, skipped FROM player ";
		$result['players'] = self::getCollectionFromDb($sql);
		$result['current_player_no'] = self::getUniqueValueFromDB("SELECT player_no from player where player_id ='$current_player_id'");
		$result['wagons'] =  self::getObjectListFromDB("SELECT id, size, val1, val2, val3 from wagons where status in ('AVAILABLE','TAKEN') order by id");
		$result['wagonstiles1'] =  self::getObjectListFromDB("SELECT a.id, a.size, b.id idd, b.val from wagons a, animals b where a.val1=b.id and b.status='WAGON' and a.status in ('AVAILABLE','TAKEN') order by a.id");
		$result['wagonstiles2'] =  self::getObjectListFromDB("SELECT a.id, a.size, b.id idd, b.val from wagons a, animals b where a.val2=b.id and b.status='WAGON' and a.status in ('AVAILABLE','TAKEN') order by a.id");
		$result['wagonstiles3'] =  self::getObjectListFromDB("SELECT a.id, a.size, b.id idd, b.val from wagons a, animals b where a.val3=b.id and b.status='WAGON' and a.status in ('AVAILABLE','TAKEN') order by a.id");
		$result['wagonstaken'] =  self::getObjectListFromDB("SELECT id, size, val1, val2, val3 from wagons where status = 'TAKEN' order by id");


		$result['animalsthinking'] =  self::getObjectListFromDB("SELECT a.id,a.val,a.x,a.y,a.player_id,b.player_no from animals a, player b where a.player_id=b.player_id and a.status='THINKING'");

		$result['animalsthinkingwagon'] =  self::getObjectListFromDB("SELECT a.id,a.val,a.x,a.y,a.player_id from animals a where a.status='WAGON' and x = (select id from wagons where status='TAKEN')");

		$result['animalsplayed'] =  self::getObjectListFromDB("SELECT a.id,a.val,a.x,a.y,a.player_id,b.player_no from animals a, player b where a.player_id=b.player_id and a.status='PLAYED'");
		$result['animalsstall'] =  self::getObjectListFromDB("SELECT a.id,a.val,a.x,a.y,a.player_id,b.player_no from animals a, player b where a.player_id=b.player_id and a.status='STALL'");

		$result['money'] =  self::getObjectListFromDB("SELECT player_no, money, unblockedzoo from player");
		$result['drawntiles'] =  self::getObjectListFromDB("SELECT id, val from animals where status = 'DRAWN'");

		$result['unblockedzoo'] =  self::getObjectListFromDB("SELECT player_no, unblockedzoo from player");

		// FIXME: is the "|| 1" needed?
		$paramvalue = $this->tableOptions->get(100) || 1;
		$result['paramvalue'] = $paramvalue;
		$result['tilesleft'] =  self::getUniqueValueFromDB("SELECT count(*) from animals where status='AVAILABLE'");
		$result['tilesleft2'] =  self::getUniqueValueFromDB("SELECT count(*) from animals where status='LASTSET'");

		$result['lastround'] =  self::getUniqueValueFromDB("SELECT distinct lastround from player");

		// TODO: Gather all information about current game situation (visible by player $current_player_id).

		return $result;
	}

	/*
        getGameProgression:

        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).

        This method is called each time we are in a game state with the "updateGameProgression" property set to true
        (see states.inc.php)
    */
	function getGameProgression()
	{
		// TODO: compute and return the game progression
		$c1 = self::getUniqueValueFromDB("select count(*) from animals where status in ('AVAILABLE','LASTSET')");
		$c2 = self::getUniqueValueFromDB("select count(*) from animals");
		return floor((1 - $c1 / $c2) * 100);
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
		// Set the colors of the players with HTML color code
		// The default below is red/green/blue/orange/brown
		// The number of colors defined here must correspond to the maximum number of players allowed for the gams
		$gameinfos = self::getGameinfos();
		$default_colors = $gameinfos['player_colors'];

		// Create players
		// Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
		$sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
		$values = array();
		$count = 0;
		foreach ($players as $player_id => $player) {
			$color = array_shift($default_colors);
			$values[] = "('" . $player_id . "','$color','" . $player['player_canal'] . "','" . addslashes($player['player_name']) . "','" . addslashes($player['player_avatar']) . "')";
			$count = $count + 1;
		}
		$sql .= implode(',', $values);
		self::DbQuery($sql);
		// self::reattributeColorsBasedOnPreferences( $players, $gameinfos['player_colors'] );
		self::reloadPlayersBasicInfos();
		$model = new Model();
		$model->createNewGame($count);

		self::initStat('player', 'full1', 0);
		self::initStat('player', 'full2', 0);
		self::initStat('player', 'full3', 0);
		self::initStat('player', 'full4', 0);
		self::initStat('player', 'part1', 0);
		self::initStat('player', 'part2', 0);
		self::initStat('player', 'part3', 0);
		self::initStat('player', 'part4', 0);
		self::initStat('player', 'encstall1', 0);
		self::initStat('player', 'encstall2', 0);
		self::initStat('player', 'encstall3', 0);
		self::initStat('player', 'encstall4', 0);
		self::initStat('player', 'stalls', 0);
		self::initStat('player', 'leftinbarn', 0);
		self::initStat('player', 'totalpoints', 0);
		self::initStat('player', 'totalcoins', 0);
		self::initStat('player', 'coinsspent', 0);
		self::initStat('player', 'coinsreceived', 2);

		// Activate first player (which is in general a good idea :) )
		$this->activeNextPlayer();

		/************ End of the game initialization *****/

		return PlayerTurn::class;
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
}
