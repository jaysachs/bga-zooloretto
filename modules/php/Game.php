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

namespace Bga\Games\babylonia;

// use Bga\Games\babylonia\States\StartTurn;

require_once(APP_GAMEMODULE_PATH . "module/table/table.game.php");

// require_once("Stats.php");

class Game extends \Bga\GameFramework\Table
{

    public function __construct()
    {
        parent::__construct();

        $this->notify->addDecorator(function(string $message, array $args): array {
            if (isset($args['player_id']) && !isset($args['player_name']) && str_contains($message, '${player_name}')) {
                $args['player_name'] = $this->getPlayerNameById($args['player_id']);
            }
            return $args;
        });
    }


    /*
        getAllDatas:

        Gather all informations about current game situation (visible by the current player).

        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = array();

        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!

        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score, player_no no, player_name name, skipped FROM player ";
        $result['players'] = self::getCollectionFromDb( $sql );
  		$result['current_player_no'] = self::getUniqueValueFromDB( "SELECT player_no from player where player_id ='$current_player_id'" );
		$result['wagons'] =  self::getObjectListFromDB( "SELECT id, size, val1, val2, val3 from wagons where status in ('AVAILABLE','TAKEN') order by id" );
		$result['wagonstiles1'] =  self::getObjectListFromDB( "SELECT a.id, a.size, b.id idd, b.val from wagons a, animals b where a.val1=b.id and b.status='WAGON' and a.status in ('AVAILABLE','TAKEN') order by a.id" );
		$result['wagonstiles2'] =  self::getObjectListFromDB( "SELECT a.id, a.size, b.id idd, b.val from wagons a, animals b where a.val2=b.id and b.status='WAGON' and a.status in ('AVAILABLE','TAKEN') order by a.id" );
		$result['wagonstiles3'] =  self::getObjectListFromDB( "SELECT a.id, a.size, b.id idd, b.val from wagons a, animals b where a.val3=b.id and b.status='WAGON' and a.status in ('AVAILABLE','TAKEN') order by a.id" );
		$result['wagonstaken'] =  self::getObjectListFromDB( "SELECT id, size, val1, val2, val3 from wagons where status = 'TAKEN' order by id" );


		$result['animalsthinking'] =  self::getObjectListFromDB( "SELECT a.id,a.val,a.x,a.y,a.player_id,b.player_no from animals a, player b where a.player_id=b.player_id and a.status='THINKING'" );

		$result['animalsthinkingwagon'] =  self::getObjectListFromDB( "SELECT a.id,a.val,a.x,a.y,a.player_id from animals a where a.status='WAGON' and x = (select id from wagons where status='TAKEN')" );

		$result['animalsplayed'] =  self::getObjectListFromDB( "SELECT a.id,a.val,a.x,a.y,a.player_id,b.player_no from animals a, player b where a.player_id=b.player_id and a.status='PLAYED'" );
		$result['animalsstall'] =  self::getObjectListFromDB( "SELECT a.id,a.val,a.x,a.y,a.player_id,b.player_no from animals a, player b where a.player_id=b.player_id and a.status='STALL'" );

		$result['money'] =  self::getObjectListFromDB( "SELECT player_no, money, unblockedzoo from player" );
		$result['drawntiles'] =  self::getObjectListFromDB( "SELECT id, val from animals where status = 'DRAWN'" );

		$result['unblockedzoo'] =  self::getObjectListFromDB( "SELECT player_no, unblockedzoo from player" );

		$param100 = self::getUniqueValueFromDB( "select count(*) from global where global_id=100" );
		if ($param100=="1")
		{
			$paramvalue=$this->gamestate->table_globals[100];
		}
		else
		{
			$paramvalue = "1";
		}
		$result['paramvalue'] = $paramvalue;
		$result['tilesleft'] =  self::getUniqueValueFromDB( "SELECT count(*) from animals where status='AVAILABLE'" );
		$result['tilesleft2'] =  self::getUniqueValueFromDB( "SELECT count(*) from animals where status='LASTSET'" );

		$result['lastround'] =  self::getUniqueValueFromDB( "SELECT distinct lastround from player" );

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
        return floor((1-$c1/$c2)*100);
    }

    /*
        upgradeTableDb:

        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.

    */

    function upgradeTableDb( $from_version )
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


	function DealAnimalsStatus($status)
	{
		$cardnum = self::getUniqueValueFromDB( "SELECT count(*) from animals where status='AVAILABLE'" );
		if ($cardnum==0)
		{
			$sql = "update player set lastround = 'Y'";
			self::DbQuery( $sql );
			$sql = "update animals set status = 'AVAILABLE' where status = 'LASTSET'";
			self::DbQuery( $sql );
			$cardnum = self::getUniqueValueFromDB( "SELECT count(*) from animals where status='AVAILABLE'" );

			self::notifyAllPlayers( "LastRound", clienttranslate( 'This is the last round...'),
			array() );
		}

		if ($cardnum!=0)
		{
			$cards = self::getObjectListFromDB( "SELECT id from animals where status='AVAILABLE'" );
			$i = 0;
			foreach( $cards as $index => $card)
			{
				$i = $i + 1;
				$sql = "update animals set idsel = $i where id = '" . $card['id']. "'";
				self::DbQuery( $sql );
			}
			$result = bga_rand(1, intval ($cardnum) );

			$sql = "update animals set status = '$status' where idsel = '$result'";
			self::DbQuery( $sql );

			$cardresult = self::getUniqueValueFromDB( "SELECT id from animals where idsel='$result'" );

			$sql = "update animals set idsel = null";
			self::DbQuery( $sql );
		}
		else
		{
			$cardresult = 0;
		}
		return $cardresult;
	}

    protected function setupNewGame( $players, $options = array() )
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
        foreach( $players as $player_id => $player )
        {
            $color = array_shift( $default_colors );
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."')";
			$count = $count + 1;
        }
        $sql .= implode( ',', $values );
        self::DbQuery( $sql );
        // self::reattributeColorsBasedOnPreferences( $players, $gameinfos['player_colors'] );
        self::reloadPlayersBasicInfos();


		for( $x=1; $x<=7 ; $x++ )
        {
			$sql = "insert into animals (id, idsel, idorder, player_id, val, status) values ('" . $x . "','','','','C','AVAILABLE')";
			self::DbQuery( $sql );
		}
		for( $x=8; $x<=9 ; $x++ )
        {
			$sql = "insert into animals (id, idsel, idorder, player_id, val, status) values ('" . $x . "','','','','CM','AVAILABLE')";
			self::DbQuery( $sql );
		}
		for( $x=10; $x<=11 ; $x++ )
        {
			$sql = "insert into animals (id, idsel, idorder, player_id, val, status) values ('" . $x . "','','','','CF','AVAILABLE')";
			self::DbQuery( $sql );
		}
		for( $x=12; $x<=18 ; $x++ )
        {
			$sql = "insert into animals (id, idsel, idorder, player_id, val, status) values ('" . $x . "','','','','E','AVAILABLE')";
			self::DbQuery( $sql );
		}
		for( $x=19; $x<=20 ; $x++ )
        {
			$sql = "insert into animals (id, idsel, idorder, player_id, val, status) values ('" . $x . "','','','','EM','AVAILABLE')";
			self::DbQuery( $sql );
		}
		for( $x=21; $x<=22 ; $x++ )
        {
			$sql = "insert into animals (id, idsel, idorder, player_id, val, status) values ('" . $x . "','','','','EF','AVAILABLE')";
			self::DbQuery( $sql );
		}
		for( $x=23; $x<=29 ; $x++ )
        {
			$sql = "insert into animals (id, idsel, idorder, player_id, val, status) values ('" . $x . "','','','','F','AVAILABLE')";
			self::DbQuery( $sql );
		}
		for( $x=39; $x<=31 ; $x++ )
        {
			$sql = "insert into animals (id, idsel, idorder, player_id, val, status) values ('" . $x . "','','','','FM','AVAILABLE')";
			self::DbQuery( $sql );
		}
		for( $x=32; $x<=33 ; $x++ )
        {
			$sql = "insert into animals (id, idsel, idorder, player_id, val, status) values ('" . $x . "','','','','FF','AVAILABLE')";
			self::DbQuery( $sql );
		}
		for( $x=34; $x<=40 ; $x++ )
        {
			$sql = "insert into animals (id, idsel, idorder, player_id, val, status) values ('" . $x . "','','','','K','AVAILABLE')";
			self::DbQuery( $sql );
		}
		for( $x=41; $x<=42 ; $x++ )
        {
			$sql = "insert into animals (id, idsel, idorder, player_id, val, status) values ('" . $x . "','','','','KM','AVAILABLE')";
			self::DbQuery( $sql );
		}
		for( $x=43; $x<=44 ; $x++ )
        {
			$sql = "insert into animals (id, idsel, idorder, player_id, val, status) values ('" . $x . "','','','','KF','AVAILABLE')";
			self::DbQuery( $sql );
		}
		for( $x=45; $x<=51 ; $x++ )
        {
			$sql = "insert into animals (id, idsel, idorder, player_id, val, status) values ('" . $x . "','','','','L','AVAILABLE')";
			self::DbQuery( $sql );
		}
		for( $x=52; $x<=53 ; $x++ )
        {
			$sql = "insert into animals (id, idsel, idorder, player_id, val, status) values ('" . $x . "','','','','LM','AVAILABLE')";
			self::DbQuery( $sql );
		}
		for( $x=54; $x<=55 ; $x++ )
        {
			$sql = "insert into animals (id, idsel, idorder, player_id, val, status) values ('" . $x . "','','','','LF','AVAILABLE')";
			self::DbQuery( $sql );
		}
		if ($count>=3)
		{
			for( $x=56; $x<=62 ; $x++ )
			{
				$sql = "insert into animals (id, idsel, idorder, player_id, val, status) values ('" . $x . "','','','','M','AVAILABLE')";
				self::DbQuery( $sql );
			}
			for( $x=63; $x<=64 ; $x++ )
			{
				$sql = "insert into animals (id, idsel, idorder, player_id, val, status) values ('" . $x . "','','','','MM','AVAILABLE')";
				self::DbQuery( $sql );
			}
			for( $x=65; $x<=66 ; $x++ )
			{
				$sql = "insert into animals (id, idsel, idorder, player_id, val, status) values ('" . $x . "','','','','MF','AVAILABLE')";
				self::DbQuery( $sql );
			}
		}
		if ($count>=4)
		{
			for( $x=67; $x<=73 ; $x++ )
			{
				$sql = "insert into animals (id, idsel, idorder, player_id, val, status) values ('" . $x . "','','','','P','AVAILABLE')";
				self::DbQuery( $sql );
			}
			for( $x=74; $x<=75 ; $x++ )
			{
				$sql = "insert into animals (id, idsel, idorder, player_id, val, status) values ('" . $x . "','','','','PM','AVAILABLE')";
				self::DbQuery( $sql );
			}
			for( $x=76; $x<=77 ; $x++ )
			{
				$sql = "insert into animals (id, idsel, idorder, player_id, val, status) values ('" . $x . "','','','','PF','AVAILABLE')";
				self::DbQuery( $sql );
			}
		}
		if ($count>=5)
		{
			for( $x=78; $x<=84 ; $x++ )
			{
				$sql = "insert into animals (id, idsel, idorder, player_id, val, status) values ('" . $x . "','','','','Z','AVAILABLE')";
				self::DbQuery( $sql );
			}
			for( $x=85; $x<=86 ; $x++ )
			{
				$sql = "insert into animals (id, idsel, idorder, player_id, val, status) values ('" . $x . "','','','','ZM','AVAILABLE')";
				self::DbQuery( $sql );
			}
			for( $x=87; $x<=88 ; $x++ )
			{
				$sql = "insert into animals (id, idsel, idorder, player_id, val, status) values ('" . $x . "','','','','ZF','AVAILABLE')";
				self::DbQuery( $sql );
			}
		}
		for( $x=89; $x<=100 ; $x++ )
        {
			$sql = "insert into animals (id, idsel, idorder, player_id, val, status) values ('" . $x . "','','','','Coin','AVAILABLE')";
			self::DbQuery( $sql );
		}
		for( $x=101; $x<=103 ; $x++ )
        {
			$sql = "insert into animals (id, idsel, idorder, player_id, val, status) values ('" . $x . "','','','','StallA','AVAILABLE')";
			self::DbQuery( $sql );
		}
		for( $x=104; $x<=106 ; $x++ )
        {
			$sql = "insert into animals (id, idsel, idorder, player_id, val, status) values ('" . $x . "','','','','StallB','AVAILABLE')";
			self::DbQuery( $sql );
		}
		for( $x=107; $x<=109 ; $x++ )
        {
			$sql = "insert into animals (id, idsel, idorder, player_id, val, status) values ('" . $x . "','','','','StallC','AVAILABLE')";
			self::DbQuery( $sql );
		}
		for( $x=110; $x<=112 ; $x++ )
        {
			$sql = "insert into animals (id, idsel, idorder, player_id, val, status) values ('" . $x . "','','','','StallD','AVAILABLE')";
			self::DbQuery( $sql );
		}

		for( $x=1; $x<=15 ; $x++ )
        {
			$this->DealAnimalsStatus("LASTSET");
		}

		if ($count!=2)
		{
			for( $x=1; $x<=$count ; $x++ )
			{
				$sql = "insert into wagons (id, size, val1, val2, val3, status) values ('" . $x . "',3,'','','','AVAILABLE')";
				self::DbQuery( $sql );
			}
		}
		else
		{
			$sql = "insert into wagons (id, size, val1, val2, val3, status) values ('1',3,'','','','AVAILABLE')";
			self::DbQuery( $sql );
			$sql = "insert into wagons (id, size, val1, val2, val3, status) values ('2',2,'','','','AVAILABLE')";
			self::DbQuery( $sql );
			$sql = "insert into wagons (id, size, val1, val2, val3, status) values ('3',1,'','','','AVAILABLE')";
			self::DbQuery( $sql );
		}

		$sql = "update player set money = 2";
		self::DbQuery( $sql );
		$sql = "update player set unblockedzoo = 0";
		self::DbQuery( $sql );
		$sql = "update player set skipped = 'N'";
		self::DbQuery( $sql );
		$sql = "update player set lastround = 'N'";
		self::DbQuery( $sql );


        self::initStat( 'player', 'full1', 0 );
        self::initStat( 'player', 'full2', 0 );
        self::initStat( 'player', 'full3', 0 );
        self::initStat( 'player', 'full4', 0 );
        self::initStat( 'player', 'part1', 0 );
        self::initStat( 'player', 'part2', 0 );
        self::initStat( 'player', 'part3', 0 );
        self::initStat( 'player', 'part4', 0 );
        self::initStat( 'player', 'encstall1', 0 );
        self::initStat( 'player', 'encstall2', 0 );
        self::initStat( 'player', 'encstall3', 0 );
        self::initStat( 'player', 'encstall4', 0 );
        self::initStat( 'player', 'stalls', 0 );
        self::initStat( 'player', 'leftinbarn', 0 );
        self::initStat( 'player', 'totalpoints', 0 );
        self::initStat( 'player', 'totalcoins', 0 );
        self::initStat( 'player', 'coinsspent', 0 );
        self::initStat( 'player', 'coinsreceived', 2 );

        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();

        /************ End of the game initialization *****/
    }


	function DecodePos($val)
	{
		if ($val=="1") return clienttranslate('first');
		else if ($val=="2") return clienttranslate('second');
		else if ($val=="3") return clienttranslate('third');
		else if ($val=="4") return clienttranslate('fourth');
		else if ($val=="5") return clienttranslate('fifth');
		else if ($val=="6") return clienttranslate('sixth');
		else return "";
	}
	function DecodeAnimal($val)
	{
		if ($val=="C") return clienttranslate('Camel');
		else if ($val=="CF") return clienttranslate('Female Camel');
		else if ($val=="CM") return clienttranslate('Male Camel');
		else if ($val=="CK") return clienttranslate('Pup Camel');
		else if ($val=="E") return clienttranslate('Elephant');
		else if ($val=="EF") return clienttranslate('Female Elephant');
		else if ($val=="EM") return clienttranslate('Male Elephant');
		else if ($val=="EK") return clienttranslate('Pup Elephant');
		else if ($val=="F") return clienttranslate('Flamingo');
		else if ($val=="FF") return clienttranslate('Female Flamingo');
		else if ($val=="FM") return clienttranslate('Male Flamingo');
		else if ($val=="FK") return clienttranslate('Pup Flamingo');
		else if ($val=="K") return clienttranslate('Kangaroo');
		else if ($val=="KF") return clienttranslate('Female Kangaroo');
		else if ($val=="KM") return clienttranslate('Male Kangaroo');
		else if ($val=="KK") return clienttranslate('Pup Kangaroo');
		else if ($val=="L") return clienttranslate('Leopard');
		else if ($val=="LF") return clienttranslate('Female Leopard');
		else if ($val=="LM") return clienttranslate('Male Leopard');
		else if ($val=="LK") return clienttranslate('Pup Leopard');
		else if ($val=="M") return clienttranslate('Monkey');
		else if ($val=="MF") return clienttranslate('Female Monkey');
		else if ($val=="MM") return clienttranslate('Male Monkey');
		else if ($val=="MK") return clienttranslate('Pup Monkey');
		else if ($val=="P") return clienttranslate('Panda');
		else if ($val=="PF") return clienttranslate('Female Panda');
		else if ($val=="PM") return clienttranslate('Male Panda');
		else if ($val=="PK") return clienttranslate('Pup Panda');
		else if ($val=="Z") return clienttranslate('Zebra');
		else if ($val=="ZF") return clienttranslate('Female Zebra');
		else if ($val=="ZM") return clienttranslate('Male Zebra');
		else if ($val=="ZK") return clienttranslate('Pup Zebra');
		else if ($val=="StallA") return clienttranslate('Kiosk Stall');
		else if ($val=="StallB") return clienttranslate('Barrow Stall');
		else if ($val=="StallC") return clienttranslate('Snacks Stall');
		else if ($val=="StallD") return clienttranslate('Popcorn Stall');
		else if ($val=="Coin") return clienttranslate('Coin');
		else return "";
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
