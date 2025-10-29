<?php
 /**
  *------
  * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
  * Zooloretto implementation : © <Your name here> <Your email address here>
  * 
  * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
  * See http://en.boardgamearena.com/#!doc/Studio for more information.
  * -----
  * 
  * zooloretto.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */


require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );


class Zooloretto extends Table
{
	function __construct( )
	{
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();
        
        self::initGameStateLabels( array( 
            //    "my_first_global_variable" => 10,
            //    "my_second_global_variable" => 11,
            //      ...
            //    "my_first_game_variant" => 100,
            //    "my_second_game_variant" => 101,
            //      ...
        ) );        
	}
	
    protected function getGameName( )
    {
		// Used for translations and stuff. Please do not modify.
        return "zooloretto";
    }
	
	function getPlayerNoForLayout($current_player_id) {
		return self::getUniqueValueFromDB("select player_no from player where player_id = '$current_player_id'");
	}	

    /*
        setupNewGame:
        
        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
	
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

	function gTakeWagon($x)
	{
		self::checkAction( 'TakeWagon' ); 
		$id1 = self::getUniqueValueFromDB("select val1 from wagons where id='$x'" );
		$id2 = self::getUniqueValueFromDB("select val2 from wagons where id='$x'" );
		$id3 = self::getUniqueValueFromDB("select val3 from wagons where id='$x'" );
		$player_id = self::getCurrentPlayerId();
		$player_no = self::getUniqueValueFromDB("select player_no from player where player_id ='$player_id'" );
		$val1 = self::getUniqueValueFromDB("select val from animals where id ='$id1'" );
		$val2 = self::getUniqueValueFromDB("select val from animals where id ='$id2'" );
		$val3 = self::getUniqueValueFromDB("select val from animals where id ='$id3'" );
		$sql = "update player set skipped='Y' where player_id = '$player_id'";
		self::DbQuery( $sql );

		$wagontiles = self::getObjectListFromDB( "SELECT concat('tile_0_',id,'_',val,'_',x,'_',y) wagontile, id FROM `animals` WHERE status = 'WAGON' and x=$x");


		$sql = "update wagons set status = 'TAKEN' where id = '$x'";
		self::DbQuery( $sql );
		
		$messagestring="";
		if ($val1!="")
		{
			$messagestring = $messagestring . $this->DecodeAnimal($val1) . ", ";
		}
		if ($val2!="")
		{
			$messagestring = $messagestring . $this->DecodeAnimal($val2) . ", ";
		}
		if ($val3!="")
		{
			$messagestring = $messagestring . $this->DecodeAnimal($val3) . ", ";
		}
		$messagestring = substr($messagestring, 0, strlen($messagestring)-2);
		
		self::notifyAllPlayers( "TakeWagon", clienttranslate( '${player_name} took a wagon with ${wag}.'), 
		array(
			'player_id' => $player_id,
			'player_no' => $player_no,
			'id1' => $id1,
			'id2' => $id2,
			'id3' => $id3,
			'val1' => $val1,
			'val2' => $val2,
			'val3' => $val3,
			'x' => $x,
			'wag' => $messagestring,
			'wagontiles' => $wagontiles,
			'player_name' => self::getCurrentPlayerName(),
			'i18n' => array( 'wag' )
		) );

		$this->gamestate->nextState( 'ArrangeZoo' );
		
	}
	

	function argplayerTurn()
	{
		$result = array();
		$result['active_player_id'] = self::getActivePlayerId();
		$active_player_id = self::getActivePlayerId();
		$result['money'] = self::getUniqueValueFromDB("select money from player where player_id ='$active_player_id'" );
		$result['unblockedzoo'] = self::getUniqueValueFromDB("select unblockedzoo from player where player_id ='$active_player_id'" );
		$result['wagons'] =  self::getObjectListFromDB( "SELECT id, size, val1, val2, val3 from wagons where status in ('AVAILABLE','TAKEN') order by id" );

        return $result;
	}
	
	function gSwap()
	{
		self::checkAction( 'Swap' ); 
		$this->gamestate->nextState( 'Swap' );
	}
	function gMove()
	{
		self::checkAction( 'Move' ); 
		$this->gamestate->nextState( 'Move' );
	}	
	
	function gBack()
	{
		self::checkAction( 'Back' ); 
		$this->gamestate->nextState( 'Back' );
	}	
	
	function gDiscard()
	{
		self::checkAction( 'Discard' ); 
		$this->gamestate->nextState( 'Discard' );
	}	
	function gBuy()
	{
		self::checkAction( 'Buy' ); 
		$this->gamestate->nextState( 'Buy' );
	}	
	
	
	function gBuyEnclosure()
	{
		self::checkAction( 'BuyEnclosure' ); 
		$player_id = self::getCurrentPlayerId();
		$player_no = self::getUniqueValueFromDB("select player_no from player where player_id ='$player_id'" );

		$sql = "update player set unblockedzoo = unblockedzoo + 1, money = money - 3 where player_id = '$player_id'";
		self::DbQuery( $sql );

		self::incStat( 3, "coinsspent", $player_id);

		$unblockedzoo = self::getUniqueValueFromDB("select unblockedzoo from player where player_id ='$player_id'" );
		
		self::notifyAllPlayers( "BuyEnclosure", clienttranslate( '${player_name} bought his ${pos} extra enclosure.'), 
		array(
			'player_id' => $player_id,
			'player_no' => $player_no,
			'unblockedzoo' => $unblockedzoo,
			'pos' => $this->DecodePos($unblockedzoo),
			'player_name' => self::getCurrentPlayerName(),
			'i18n' => array( 'pos' )
		) );

		$this->gamestate->nextState( 'NextPlayer' );
	}
	
	function gSwapTiles($enc1, $enc2, $anid)
	{
		self::checkAction( 'Swap' ); 
		$player_id = self::getCurrentPlayerId();
		$player_no = self::getUniqueValueFromDB("select player_no from player where player_id ='$player_id'" );
		$tiles1 = "";
		$tiles2 = "";
		if ($enc1!="0")
		{
			$tiles1 = self::getObjectListFromDB( "SELECT id,val,x,y from animals where player_id ='$player_id' and status = 'PLAYED' and x = $enc1" );
			$count = 0;
			foreach( $tiles1 as $index => $tile1)
			{
				$count = $count + 1;
				$sql = "update animals set x = $enc2, y = $count, status='PLAYED2' where id = '".$tile1['id']."'";
				self::DbQuery( $sql );
			}
		}
		else
		{
			$tiles1 = self::getObjectListFromDB( "SELECT id,val,x,y from animals where player_id ='$player_id' and status = 'STALL' and left(val,1) = '$anid'" );
			$count = 0;
			foreach( $tiles1 as $index => $tile1)
			{
				$count = $count + 1;
				$sql = "update animals set x = $enc2, y = $count, status='PLAYED2' where id = '".$tile1['id']."'";
				self::DbQuery( $sql );
			}
		}
		if ($enc1!="0")
		{
			$tiles2 = self::getObjectListFromDB( "SELECT id,val,x,y from animals where player_id ='$player_id' and status = 'PLAYED' and x = $enc2" );
			$count = 0;
			foreach( $tiles2 as $index => $tile2)
			{
				$count = $count + 1;
				$sql = "update animals set x = $enc1, y = $count, status='PLAYED2' where id = '".$tile2['id']."'";
				self::DbQuery( $sql );
			}
		}
		else
		{
			$tiles2 = self::getObjectListFromDB( "SELECT id,val,x,y from animals where player_id ='$player_id' and status = 'PLAYED' and x = $enc2" );
			$count = 0;
			foreach( $tiles2 as $index => $tile2)
			{
				$count = $count + 1;
				$sql = "update animals set x = 0, y = 0, status='STALL' where id = '".$tile2['id']."'";
				self::DbQuery( $sql );
			}
		}
		
		$sql = "update player set money = money - 1 where player_id = '$player_id'";
		self::DbQuery( $sql );

		self::incStat( 1, "coinsspent", $player_id);

		$sql = "update animals set status='PLAYED' where status='PLAYED2'";
		self::DbQuery( $sql );
		
		if ($enc1!="0")
		{
			self::notifyAllPlayers( "SwapTiles", clienttranslate( '${player_name} swapped the tiles of his ${pos1} and ${pos2} enclosures.'), 
			array(
				'player_id' => $player_id,
				'player_no' => $player_no,
				'enc1' => $enc1,
				'enc2' => $enc2,
				'tiles1' => $tiles1,
				'tiles2' => $tiles2,
				'pos1' => $this->DecodePos($enc1),
				'pos2' => $this->DecodePos($enc2),
				'anid' => $anid,
				'player_name' => self::getCurrentPlayerName(),
				'i18n' => array( 'pos1','pos2' )
			) );
		}
		else
		{
			self::notifyAllPlayers( "SwapTiles", clienttranslate( '${player_name} swapped the tiles of his Barn and ${pos2} enclosures.'), 
			array(
				'player_id' => $player_id,
				'player_no' => $player_no,
				'enc1' => $enc1,
				'enc2' => $enc2,
				'tiles1' => $tiles1,
				'tiles2' => $tiles2,
				'pos1' => $this->DecodePos($enc1),
				'pos2' => $this->DecodePos($enc2),
				'anid' => $anid,
				'player_name' => self::getCurrentPlayerName(),
				'i18n' => array( 'pos1','pos2' )
			) );
		}


		if ($enc1=="0")
		{
			////////////////////////////
			// KIDS
			////////////////////////////
			
			$enclosures = self::getObjectListFromDB( "SELECT distinct x as x from animals where x = $enc2" );
			foreach( $enclosures as $index => $enclosure)
			{
				$males = intval(self::getUniqueValueFromDB("select count(*) from animals where id < 300 and val like '_M' and player_id='$player_id' and x = '" . $enclosure['x']. "'" ));
				$females = intval(self::getUniqueValueFromDB("select count(*) from animals where id < 300 and val like '_F' and player_id='$player_id' and x = '" . $enclosure['x']. "'" ));
				$kids = intval(self::getUniqueValueFromDB("select count(*) from animals where val like '_K' and player_id='$player_id' and x = '" . $enclosure['x']. "'" ));
				$parents = min($males,$females);
				$maxid = intval(self::getUniqueValueFromDB("select MAX(id) from animals where id < 300" ));
				$spacesoccupied = intval(self::getUniqueValueFromDB("select count(*) from animals where player_id='$player_id' and x = '" . $enclosure['x']. "'" ));
				$animal = self::getUniqueValueFromDB("select distinct LEFT(val , 1) from animals where player_id='$player_id' and x = '" . $enclosure['x']. "'");
				$spacesleft = 0;
				if ($enclosure['x']=="1") $spacesleft = 5 - $spacesoccupied;
				else if ($enclosure['x']=="2") $spacesleft = 4 - $spacesoccupied;
				else if ($enclosure['x']=="3") $spacesleft = 6 - $spacesoccupied;
				else if ($enclosure['x']=="4") $spacesleft = 5 - $spacesoccupied;
				else if ($enclosure['x']=="5") $spacesleft = 5 - $spacesoccupied;
				$totalkids = $parents; //-$kids;

				$sql = "update animals set id = id + 300 where id < 300 and val like '_M' and player_id='$player_id' and x = '" . $enclosure['x']. "' limit $totalkids";
				self::DbQuery( $sql );
				$sql = "update animals set id = id + 300 where id < 300 and val like '_F' and player_id='$player_id' and x = '" . $enclosure['x']. "' limit $totalkids";
				self::DbQuery( $sql );

				$newparents = self::getObjectListFromDB( "SELECT concat('tile_',b.player_no,'_',a.id-300,'_',a.val,'_',a.x,'_',a.y) oldparenttile, concat('tile_',b.player_no,'_',a.id,'_',a.val,'_',a.x,'_',a.y) parenttile, a.id, a.val,a.x,a.y, b.player_no FROM `animals` a, player b WHERE a.player_id = b.player_id and a.id > 300");

				for( $x=1; $x<= $totalkids; $x++ )
				{
					if ($spacesleft>0)
					{
						$maxid = $maxid + 1;
						$spacesleft = $spacesleft - 1;
						$spacesoccupied = $spacesoccupied + 1;

						$found = false;
						$spaceid = 0;
						while (!$found)
						{
							$spaceid = $spaceid + 1;
							$check = intval(self::getUniqueValueFromDB("select count(*) from animals where player_id='$player_id' and x = '" . $enclosure['x']. "' and y='$spaceid'" ));
							if ($check==0)
							{
								$found = true;
							}
						}

						$sql = "insert into animals (id, idsel, idorder, player_id, val, status,x,y) values ('" . $maxid . "',null,0,'$player_id','".$animal."K','THIKINGKID',".$enclosure['x'].", $spaceid)";
						self::DbQuery( $sql );
						$kids = self::getObjectListFromDB( "SELECT concat('tile_',$player_no,'_',id,'_',val,'_',x,'_',y) kidtile, id, val,x,y FROM `animals` WHERE status = 'THIKINGKID'");
						$kidsstall = "";

						self::notifyAllPlayers( "Babies", clienttranslate( '${player_name} received a newborn ${translatedval} that goes into his enclosure.'), 
						array(
							'player_id' => $player_id,
							'player_no' => $player_no,
							'kids' => $kids,
							'kidsstall' => $kidsstall,
							'player_name' => self::getCurrentPlayerName(),
							'translatedval' => $this->DecodeAnimal($animal."K"),
							'newparents'=>$newparents,
							'i18n' => array( 'translatedval' )
						) );

						$sql = "update animals set status = 'PLAYED' where status = 'THIKINGKID'";
						self::DbQuery( $sql );
						$sql = "update animals set status = 'STALL' where status = 'THIKINGKIDSTALL'";
						self::DbQuery( $sql );
					}
					else
					{
						$maxid = $maxid + 1;
						$sql = "insert into animals (id, idsel, idorder, player_id, val, status,x,y) values ('" . $maxid . "',null,0,'$player_id','".$animal."K','THIKINGKIDSTALL',0,0)";
						self::DbQuery( $sql );
						$kids = "";
						$kidsstall = self::getObjectListFromDB( "SELECT concat('tile_',".$player_no.",'_',id,'_',val,'_',x,'_',y) as kidtile, id, val,x,y FROM `animals` WHERE status = 'THIKINGKIDSTALL'");

						self::notifyAllPlayers( "Babies", clienttranslate( '${player_name} received a newborn ${translatedval} that goes into his Barn.'), 
						array(
							'player_id' => $player_id,
							'player_no' => $player_no,
							'kids' => $kids,
							'kidsstall' => $kidsstall,
							'player_name' => self::getCurrentPlayerName(),
							'translatedval' => $this->DecodeAnimal($animal."K"),
							'newparents'=>$newparents,
							'i18n' => array( 'translatedval' )
						) );

						$sql = "update animals set status = 'PLAYED' where status = 'THIKINGKID'";
						self::DbQuery( $sql );
						$sql = "update animals set status = 'STALL' where status = 'THIKINGKIDSTALL'";
						self::DbQuery( $sql );
					}
				}
			}		
		}
		$this->gamestate->nextState( 'NextPlayer' );
	}
	
	function gConfirmDiscard($tileid)
	{
		self::checkAction( 'Discard' ); 
		$player_id = self::getCurrentPlayerId();
		$player_no = self::getUniqueValueFromDB("select player_no from player where player_id ='$player_id'" );
		$val = self::getUniqueValueFromDB("select val from animals where id ='$tileid'" );

		$sql = "update animals set x = 0, y = 0, player_id = 0, status='DISCARD' where id = '$tileid'";
		self::DbQuery( $sql );
		$sql = "update player set money = money - 2 where player_id = '$player_id'";
		self::DbQuery( $sql );

		self::incStat( 2, "coinsspent", $player_id);
		
		
		self::notifyAllPlayers( "ConfirmDiscard", clienttranslate( '${player_name} discarded the ${translatedval} from his Barn.'), 
		array(
			'player_id' => $player_id,
			'player_no' => $player_no,
			'tileid' => $tileid,
			'val' => $val,
			'translatedval' => $this->DecodeAnimal($val),
			'player_name' => self::getCurrentPlayerName(),
			'i18n' => array( 'translatedval' )
		) );

		$this->gamestate->nextState( 'NextPlayer' );
	}

	function gPlaceTile($x, $y)
	{
		self::checkAction( 'PlaceTile' ); 
		$id = self::getUniqueValueFromDB("select id from animals where status='DRAWN'" );
		$player_id = self::getCurrentPlayerId();
		$player_no = self::getUniqueValueFromDB("select player_no from player where player_id ='$player_id'" );
		$val = self::getUniqueValueFromDB("select val from animals where id ='$id'" );

		$sql = "update animals set x = $x, y = $y, status = 'WAGON' where id = '$id'";
		self::DbQuery( $sql );
		$sql = "update wagons set val$y = $id where id = '$x'";
		self::DbQuery( $sql );
		
		
		self::notifyAllPlayers( "PlaceTile", clienttranslate( '${player_name} placed the ${translatedval} tile on the ${pos} space of the ${wag} wagon.'), 
		array(
			'player_id' => $player_id,
			'player_no' => $player_no,
			'id' => $id,
			'val' => $val,
			'x' => $x,
			'y' => $y,
			'translatedval' => $this->DecodeAnimal($val),
			'pos' => $this->DecodePos($y),
			'wag' => $this->DecodePos($x),
			'player_name' => self::getCurrentPlayerName(),
			'i18n' => array( 'translatedval', 'pos', 'wag' )
		) );

		$this->gamestate->nextState( 'NextPlayer' );
	}
	
	function gGoBack($x)
	{
		self::checkAction( 'GoBack' ); 
		$this->gReset();

		$player_id = self::getCurrentPlayerId();
		$player_no = self::getUniqueValueFromDB("select player_no from player where player_id ='$player_id'" );

		$sql = "update player set skipped='N' where player_id = '$player_id'";
		self::DbQuery( $sql );

		$sql = "update wagons set status = 'AVAILABLE' where id = '$x'";
		self::DbQuery( $sql );

		$wagontiles = self::getObjectListFromDB( "SELECT concat('tile_0_',id,'_',val,'_',x,'_',y) wagontile, id FROM `animals` WHERE status = 'WAGON' and x=$x");

		self::notifyAllPlayers( "GoBackWagon", clienttranslate( '${player_name} decided to lay down the wagon taken.'), 
		array(
			'player_id' => $player_id,
			'player_no' => $player_no,
			'x' => $x,
			'wagontiles' => $wagontiles,
			'player_name' => self::getCurrentPlayerName(),
			'i18n' => array( 'wag' )
		) );

		$this->gamestate->nextState( 'playerTurn' );
	}
	
	function gReset()
	{
		self::checkAction( 'Reset' ); 
		$player_id = self::getCurrentPlayerId();
		$player_no = self::getUniqueValueFromDB("select player_no from player where player_id ='$player_id'" );
		$wagonid = self::getUniqueValueFromDB("select id from wagons where status = 'TAKEN'" );
		$wagonsize = self::getUniqueValueFromDB("select size from wagons where status = 'TAKEN'" );
		$val1 = self::getUniqueValueFromDB("select val1 from wagons where status = 'TAKEN'" );
		$val2 = self::getUniqueValueFromDB("select val2 from wagons where status = 'TAKEN'" );
		$val3 = self::getUniqueValueFromDB("select val3 from wagons where status = 'TAKEN'" );
		$thinkings = self::getObjectListFromDB( "SELECT concat('tile_',$player_no,'_',id,'_',val,'_',x,'_',y) as thinking,id,val,x,y from animals where status = 'THINKING'" );
		$found = false;
		$pos1="";
		$pos2="";
		$pos3="";
		foreach( $thinkings as $index => $thinking)
		{
			$found = true;
			if ($val1==null || $val1=="")
			{
				$sql = "update animals set status = 'WAGON', player_id = 0, x = $wagonid, y = 1 where id = '".$thinking['id']."'";
				self::DbQuery( $sql );
				$sql = "update wagons set val1 = '".$thinking['id']."' where status = 'TAKEN'";
				self::DbQuery( $sql );
				$val1 = $thinking['id'];
				if ($pos1=="") $pos1="1";
				else if ($pos2=="") $pos2="1";
				else if ($pos3=="") $pos3="1";
			}
			else if ($val2==null || $val2=="")
			{
				$sql = "update animals set status = 'WAGON', player_id = 0, x = $wagonid, y = 2 where id = '".$thinking['id']."'";
				self::DbQuery( $sql );
				$sql = "update wagons set val2 = '".$thinking['id']."' where status = 'TAKEN'";
				self::DbQuery( $sql );
				$val2 = $thinking['id'];
				if ($pos1=="") $pos1="2";
				else if ($pos2=="") $pos2="2";
				else if ($pos3=="") $pos3="2";
			}
			else if ($val3==null || $val3=="")
			{
				$sql = "update animals set status = 'WAGON', player_id = 0, x = $wagonid, y = 3 where id = '".$thinking['id']."'";
				self::DbQuery( $sql );
				$sql = "update wagons set val3 = '".$thinking['id']."' where status = 'TAKEN'";
				self::DbQuery( $sql );
				$val3 = $thinking['id'];
				if ($pos1=="") $pos1="3";
				else if ($pos2=="") $pos2="3";
				else if ($pos3=="") $pos3="3";
			}
		}		

		self::notifyAllPlayers( "Reset", clienttranslate( '${player_name} reset his wagon during the zoo arrangement.'), 
		array(
			'player_id' => $player_id,
			'player_no' => $player_no,
			'thinkings' => $thinkings,
			'wagonid' => $wagonid,
			'pos1'=>$pos1,
			'pos2'=>$pos2,
			'pos3'=>$pos3,
			'player_name' => self::getCurrentPlayerName()
		) );
	}
	
	function gConfirmArrangement()
	{
		self::checkAction( 'ConfirmArrangement' ); 
		$player_id = self::getCurrentPlayerId();
		$player_no = self::getUniqueValueFromDB("select player_no from player where player_id ='$player_id'" );
		$wagonid = self::getUniqueValueFromDB("select id from wagons where status = 'TAKEN'" );
		
		$coins = self::getUniqueValueFromDB("select count(*) from animals where val = 'Coin' and status = 'WAGON' and x=$wagonid" );
		
		if (intval($coins)>0)
		{
			$cointiles = self::getObjectListFromDB( "SELECT concat('tile_0_',id,'_Coin_',x,'_',y) cointiles, id FROM `animals` WHERE val='Coin' and status = 'WAGON' and x=$wagonid");

			$sql = "update player set money = money + " . intval($coins) . " where player_id = '$player_id'";
			self::DbQuery( $sql );

			self::incStat( intval($coins), "coinsreceived", $player_id);

			$sql = "update animals set status = 'DISCARDED' where val = 'Coin' and status = 'WAGON' and x=$wagonid";
			self::DbQuery( $sql );

			self::notifyAllPlayers( "GetMoney", clienttranslate( '${player_name} collected ${coins} money.'), 
			array(
				'player_id' => $player_id,
				'player_no' => $player_no,
				'coins' => $coins,
				'cointiles' => $cointiles,
				'player_name' => self::getCurrentPlayerName()
			) );
		}
		

		$stall = self::getUniqueValueFromDB("select count(*) from animals where status = 'WAGON' and x=$wagonid" );
		
		if (intval($stall)>0)
		{
			$stalltiles = self::getObjectListFromDB( "SELECT concat('tile_0_',id,'_',val,'_',x,'_',y) stalltiles, id, val,x,y FROM `animals` WHERE status = 'WAGON' and x=$wagonid");

			$sql = "update animals set player_id= '$player_id', status = 'STALL', x=0, y=0 where status = 'WAGON' and x=$wagonid";
			self::DbQuery( $sql );

			self::notifyAllPlayers( "GotoStall", clienttranslate( '${player_name} put ${stall} tiles in his Barn.'), 
			array(
				'player_id' => $player_id,
				'player_no' => $player_no,
				'stall' => $stall,
				'stalltiles' => $stalltiles,
				'player_name' => self::getCurrentPlayerName()
			) );
		}
		
		////////////////////////////
		// KIDS
		////////////////////////////
		
		$enclosures = self::getObjectListFromDB( "SELECT distinct x as x from animals where status = 'THINKING'" );
		foreach( $enclosures as $index => $enclosure)
		{
			$males = intval(self::getUniqueValueFromDB("select count(*) from animals where id < 300 and val like '_M' and player_id='$player_id' and x = '" . $enclosure['x']. "'" ));
			$females = intval(self::getUniqueValueFromDB("select count(*) from animals where id < 300 and val like '_F' and player_id='$player_id' and x = '" . $enclosure['x']. "'" ));
			$kids = intval(self::getUniqueValueFromDB("select count(*) from animals where val like '_K' and player_id='$player_id' and x = '" . $enclosure['x']. "'" ));
			$parents = min($males,$females);
			$maxid = intval(self::getUniqueValueFromDB("select MAX(id) from animals where id < 300" ));
			$spacesoccupied = intval(self::getUniqueValueFromDB("select count(*) from animals where player_id='$player_id' and x = '" . $enclosure['x']. "'" ));
			$animal = self::getUniqueValueFromDB("select distinct LEFT(val , 1) from animals where player_id='$player_id' and x = '" . $enclosure['x']. "'");
			$spacesleft = 0;
			if ($enclosure['x']=="1") $spacesleft = 5 - $spacesoccupied;
			else if ($enclosure['x']=="2") $spacesleft = 4 - $spacesoccupied;
			else if ($enclosure['x']=="3") $spacesleft = 6 - $spacesoccupied;
			else if ($enclosure['x']=="4") $spacesleft = 5 - $spacesoccupied;
			else if ($enclosure['x']=="5") $spacesleft = 5 - $spacesoccupied;
			$totalkids = $parents; //-$kids;
			
			$sql = "update animals set id = id + 300 where id < 300 and val like '_M' and player_id='$player_id' and x = '" . $enclosure['x']. "' limit $totalkids";
			self::DbQuery( $sql );
			$sql = "update animals set id = id + 300 where id < 300 and val like '_F' and player_id='$player_id' and x = '" . $enclosure['x']. "' limit $totalkids";
			self::DbQuery( $sql );

				$newparents = self::getObjectListFromDB( "SELECT concat('tile_',b.player_no,'_',a.id-300,'_',a.val,'_',a.x,'_',a.y) oldparenttile, concat('tile_',b.player_no,'_',a.id,'_',a.val,'_',a.x,'_',a.y) parenttile, a.id, a.val,a.x,a.y, b.player_no FROM `animals` a, player b WHERE a.player_id = b.player_id and a.id > 300");
			
			for( $x=1; $x<= $totalkids; $x++ )
			{
				if ($spacesleft>0)
				{
					$maxid = $maxid + 1;
					$spacesleft = $spacesleft - 1;
					$spacesoccupied = $spacesoccupied + 1;
					
					$found = false;
					$spaceid = 0;
					while (!$found)
					{
						$spaceid = $spaceid + 1;
						$check = intval(self::getUniqueValueFromDB("select count(*) from animals where player_id='$player_id' and x = '" . $enclosure['x']. "' and y='$spaceid'" ));
						if ($check==0)
						{
							$found = true;
						}
					}
					
					$sql = "insert into animals (id, idsel, idorder, player_id, val, status,x,y) values ('" . $maxid . "',null,0,'$player_id','".$animal."K','THIKINGKID',".$enclosure['x'].", $spaceid)";
					self::DbQuery( $sql );
					$kids = self::getObjectListFromDB( "SELECT concat('tile_',$player_no,'_',id,'_',val,'_',x,'_',y) kidtile, id, val,x,y FROM `animals` WHERE status = 'THIKINGKID'");
					$kidsstall = "";

					self::notifyAllPlayers( "Babies", clienttranslate( '${player_name} received a newborn ${translatedval} that goes into his enclosure.'), 
					array(
						'player_id' => $player_id,
						'player_no' => $player_no,
						'kids' => $kids,
						'kidsstall' => $kidsstall,
						'player_name' => self::getCurrentPlayerName(),
						'translatedval' => $this->DecodeAnimal($animal."K"),
						'newparents'=>$newparents,
						'i18n' => array( 'translatedval' )
					) );

					$sql = "update animals set status = 'PLAYED' where status = 'THIKINGKID'";
					self::DbQuery( $sql );
					$sql = "update animals set status = 'STALL' where status = 'THIKINGKIDSTALL'";
					self::DbQuery( $sql );
				}
				else
				{
					$maxid = $maxid + 1;
					$sql = "insert into animals (id, idsel, idorder, player_id, val, status,x,y) values ('" . $maxid . "',null,0,'$player_id','".$animal."K','THIKINGKIDSTALL',0,0)";
					self::DbQuery( $sql );
					$kids = "";
					$kidsstall = self::getObjectListFromDB( "SELECT concat('tile_',".$player_no.",'_',id,'_',val,'_',x,'_',y) as kidtile, id, val,x,y FROM `animals` WHERE status = 'THIKINGKIDSTALL'");

					self::notifyAllPlayers( "Babies", clienttranslate( '${player_name} received a newborn ${translatedval} that goes into his Barn.'), 
					array(
						'player_id' => $player_id,
						'player_no' => $player_no,
						'kids' => $kids,
						'kidsstall' => $kidsstall,
						'player_name' => self::getCurrentPlayerName(),
						'translatedval' => $this->DecodeAnimal($animal."K"),
						'newparents'=>$newparents,
						'i18n' => array( 'translatedval' )
					) );

					$sql = "update animals set status = 'PLAYED' where status = 'THIKINGKID'";
					self::DbQuery( $sql );
					$sql = "update animals set status = 'STALL' where status = 'THIKINGKIDSTALL'";
					self::DbQuery( $sql );
				}
			}

			if ($spacesleft==0)
			{
				$coinsgained = 0;
				if ($enclosure['x']=="1") $coinsgained=2;
				else if ($enclosure['x']=="2") $coinsgained=1;
				else if ($enclosure['x']=="3") $coinsgained=0;
				else if ($enclosure['x']=="4") $coinsgained=1;
				else if ($enclosure['x']=="5") $coinsgained=1;
				
				if ($coinsgained>0)
				{
					$coinsbefore = intval(self::getUniqueValueFromDB("select money from player where player_id = '$player_id'" ));

					$sql = "update player set money = money + " . intval($coinsgained) . " where player_id = '$player_id'";
					self::DbQuery( $sql );

					self::incStat( intval($coinsgained), "coinsreceived", $player_id);

					self::notifyAllPlayers( "CoinsGained", clienttranslate( '${player_name} gained ${coinsgained} money for completing his ${pos} enclosure.'), 
					array(
						'player_id' => $player_id,
						'player_no' => $player_no,
						'coinsgained' => $coinsgained,
						'coinsbefore' => $coinsbefore,
						'enclosure' => $enclosure['x'],
						'player_name' => self::getCurrentPlayerName(),
						'pos' => $this->DecodePos($enclosure['x']),
						'i18n' => array( 'pos' )
					) );
				}
			}
		}		

		$sql = "update animals set status = 'PLAYED' where status = 'THINKING'";
		self::DbQuery( $sql );
		$sql = "update wagons set status = 'PLAYED' where status = 'TAKEN'";
		self::DbQuery( $sql );
		$sql = "update player set skipped='Y' where player_id = '$player_id'";
		self::DbQuery( $sql );

		self::notifyAllPlayers( "ConfirmArrangement", clienttranslate( '${player_name} confirmed the arrangement of his zoo.'), 
		array(
			'player_id' => $player_id,
			'player_no' => $player_no,
			'wagonid' => $wagonid,
			'player_name' => self::getCurrentPlayerName(),
		) );
		
		$this->gamestate->nextState( 'NextPlayer' );
	}
	
	
	function gBuyTile($tileid,$pid,$x0,$y0,$x1,$y1)
	{
		self::checkAction( 'Buy' ); 
		$player_id = self::getCurrentPlayerId();
		$player_no = self::getUniqueValueFromDB("select player_no from player where player_id ='$player_id'" );
		$val = self::getUniqueValueFromDB("select val from animals where id ='$tileid'" );
		$donor_player_id = self::getUniqueValueFromDB("select player_id from animals where id ='$tileid'" );
		$donor_player_no = self::getUniqueValueFromDB("select player_no from player where player_id ='$donor_player_id'" );
		$donor_name = self::getUniqueValueFromDB("select player_name from player where player_id ='$donor_player_id'" );

		$sql = "update animals set x = $x1, y = $y1, status='PLAYED', player_id = '$player_id' where id = '$tileid'";
		self::DbQuery( $sql );

		$sql = "update player set money = money - 2 where player_id = '$player_id'";
		self::DbQuery( $sql );

		$sql = "update player set money = money + 1 where player_id = '$donor_player_id'";
		self::DbQuery( $sql );

		self::incStat( 2, "coinsspent", $player_id);
		self::incStat( 1, "coinsreceived", $donor_player_id);

		$donor_money = self::getUniqueValueFromDB("select money from player where player_id ='$donor_player_id'" );

		if (substr($val, 0, 5) != "Stall")
		{
			self::notifyAllPlayers( "Buy", clienttranslate( '${player_name} bought the ${translatedval} from the Barn of ${donor_name} and put it in his ${pos2} enclosure.'), 
			array(
				'player_id' => $player_id,
				'player_no' => $player_no,
				'tileid' => $tileid,
				'pid' => $pid,
				'x0' => $x0,
				'y0' => $y0,
				'x1' => $x1,
				'y1' => $y1,
				'val' => $val,
				'translatedval' => $this->DecodeAnimal($val),
				'pos2' => $this->DecodePos($x1),
				'player_name' => self::getCurrentPlayerName(),
				'donor_name' => $donor_name,
				'donor_player_id' => $donor_player_id,
				'donor_player_no' => $donor_player_no,
				'donor_money' => $donor_money,
				'i18n' => array( 'translatedval', 'pos2')
			) );
		}
		else
		{
			self::notifyAllPlayers( "Buy", clienttranslate( '${player_name} bought the ${translatedval} from the Barn of ${donor_name}.'), 
			array(
				'player_id' => $player_id,
				'player_no' => $player_no,
				'tileid' => $tileid,
				'pid' => $pid,
				'x0' => $x0,
				'y0' => $y0,
				'x1' => $x1,
				'y1' => $y1,
				'val' => $val,
				'translatedval' => $this->DecodeAnimal($val),
				'pos2' => $this->DecodePos($x1),
				'player_name' => self::getCurrentPlayerName(),
				'donor_name' => $donor_name,
				'donor_player_id' => $donor_player_id,
				'donor_player_no' => $donor_player_no,
				'donor_money' => $donor_money,
				'i18n' => array( 'translatedval', 'pos2')
			) );
		}

		////////////////////////////
		// KIDS
		////////////////////////////
		
		if (substr($val, 0, 5) != "Stall")
		{
			$enclosures = self::getObjectListFromDB( "SELECT distinct x as x from animals where id = '$tileid'" );
			foreach( $enclosures as $index => $enclosure)
			{
				$males = intval(self::getUniqueValueFromDB("select count(*) from animals where id < 300 and val like '_M' and player_id='$player_id' and x = '" . $enclosure['x']. "'" ));
				$females = intval(self::getUniqueValueFromDB("select count(*) from animals where id < 300 and val like '_F' and player_id='$player_id' and x = '" . $enclosure['x']. "'" ));
				$kids = intval(self::getUniqueValueFromDB("select count(*) from animals where val like '_K' and player_id='$player_id' and x = '" . $enclosure['x']. "'" ));
				$parents = min($males,$females);
				$maxid = intval(self::getUniqueValueFromDB("select MAX(id) from animals where id < 300" ));
				$spacesoccupied = intval(self::getUniqueValueFromDB("select count(*) from animals where player_id='$player_id' and x = '" . $enclosure['x']. "'" ));
				$animal = self::getUniqueValueFromDB("select distinct LEFT(val , 1) from animals where player_id='$player_id' and x = '" . $enclosure['x']. "'");
				$spacesleft = 0;
				if ($enclosure['x']=="1") $spacesleft = 5 - $spacesoccupied;
				else if ($enclosure['x']=="2") $spacesleft = 4 - $spacesoccupied;
				else if ($enclosure['x']=="3") $spacesleft = 6 - $spacesoccupied;
				else if ($enclosure['x']=="4") $spacesleft = 5 - $spacesoccupied;
				else if ($enclosure['x']=="5") $spacesleft = 5 - $spacesoccupied;
				$totalkids = $parents; //-$kids;

				$sql = "update animals set id = id + 300 where id < 300 and val like '_M' and player_id='$player_id' and x = '" . $enclosure['x']. "' limit $totalkids";
				self::DbQuery( $sql );
				$sql = "update animals set id = id + 300 where id < 300 and val like '_F' and player_id='$player_id' and x = '" . $enclosure['x']. "' limit $totalkids";
				self::DbQuery( $sql );

				$newparents = self::getObjectListFromDB( "SELECT concat('tile_',b.player_no,'_',a.id-300,'_',a.val,'_',a.x,'_',a.y) oldparenttile, concat('tile_',b.player_no,'_',a.id,'_',a.val,'_',a.x,'_',a.y) parenttile, a.id, a.val,a.x,a.y, b.player_no FROM `animals` a, player b WHERE a.player_id = b.player_id and a.id > 300");

				for( $x=1; $x<= $totalkids; $x++ )
				{
					if ($spacesleft>0)
					{
						$maxid = $maxid + 1;
						$spacesleft = $spacesleft - 1;
						$spacesoccupied = $spacesoccupied + 1;

						$found = false;
						$spaceid = 0;
						while (!$found)
						{
							$spaceid = $spaceid + 1;
							$check = intval(self::getUniqueValueFromDB("select count(*) from animals where player_id='$player_id' and x = '" . $enclosure['x']. "' and y='$spaceid'" ));
							if ($check==0)
							{
								$found = true;
							}
						}

						$sql = "insert into animals (id, idsel, idorder, player_id, val, status,x,y) values ('" . $maxid . "',null,0,'$player_id','".$animal."K','THIKINGKID',".$enclosure['x'].", $spaceid)";
						self::DbQuery( $sql );
						$kids = self::getObjectListFromDB( "SELECT concat('tile_',$player_no,'_',id,'_',val,'_',x,'_',y) kidtile, id, val,x,y FROM `animals` WHERE status = 'THIKINGKID'");
						$kidsstall = "";

						self::notifyAllPlayers( "Babies", clienttranslate( '${player_name} received a newborn ${translatedval} that goes into his enclosure.'), 
						array(
							'player_id' => $player_id,
							'player_no' => $player_no,
							'kids' => $kids,
							'kidsstall' => $kidsstall,
							'player_name' => self::getCurrentPlayerName(),
							'translatedval' => $this->DecodeAnimal($animal."K"),
							'newparents'=>$newparents,
							'i18n' => array( 'translatedval' )
						) );

						$sql = "update animals set status = 'PLAYED' where status = 'THIKINGKID'";
						self::DbQuery( $sql );
						$sql = "update animals set status = 'STALL' where status = 'THIKINGKIDSTALL'";
						self::DbQuery( $sql );
					}
					else
					{
						$maxid = $maxid + 1;
						$sql = "insert into animals (id, idsel, idorder, player_id, val, status,x,y) values ('" . $maxid . "',null,0,'$player_id','".$animal."K','THIKINGKIDSTALL',0,0)";
						self::DbQuery( $sql );
						$kids = "";
						$kidsstall = self::getObjectListFromDB( "SELECT concat('tile_',".$player_no.",'_',id,'_',val,'_',x,'_',y) as kidtile, id, val,x,y FROM `animals` WHERE status = 'THIKINGKIDSTALL'");

						self::notifyAllPlayers( "Babies", clienttranslate( '${player_name} received a newborn ${translatedval} that goes into his Barn.'), 
						array(
							'player_id' => $player_id,
							'player_no' => $player_no,
							'kids' => $kids,
							'kidsstall' => $kidsstall,
							'player_name' => self::getCurrentPlayerName(),
							'translatedval' => $this->DecodeAnimal($animal."K"),
							'newparents'=>$newparents,
							'i18n' => array( 'translatedval' )
						) );

						$sql = "update animals set status = 'PLAYED' where status = 'THIKINGKID'";
						self::DbQuery( $sql );
						$sql = "update animals set status = 'STALL' where status = 'THIKINGKIDSTALL'";
						self::DbQuery( $sql );
					}
				}

				if ($spacesleft==0)
				{
					$coinsgained = 0;
					if ($enclosure['x']=="1") $coinsgained=2;
					else if ($enclosure['x']=="2") $coinsgained=1;
					else if ($enclosure['x']=="3") $coinsgained=0;
					else if ($enclosure['x']=="4") $coinsgained=1;
					else if ($enclosure['x']=="5") $coinsgained=1;
					
					if ($coinsgained>0)
					{
						$coinsbefore = intval(self::getUniqueValueFromDB("select money from player where player_id = '$player_id'" ));

						$sql = "update player set money = money + " . intval($coinsgained) . " where player_id = '$player_id'";
						self::DbQuery( $sql );

						self::incStat( intval($coinsgained) , "coinsreceived", $player_id);

						self::notifyAllPlayers( "CoinsGained", clienttranslate( '${player_name} gained ${coinsgained} money for completing his ${pos} enclosure.'), 
						array(
							'player_id' => $player_id,
							'player_no' => $player_no,
							'coinsgained' => $coinsgained,
							'coinsbefore' => $coinsbefore,
							'enclosure' => $enclosure['x'],
							'player_name' => self::getCurrentPlayerName(),
							'pos' => $this->DecodePos($enclosure['x']),
							'i18n' => array( 'pos' )
						) );
					}
				}
			}		
		}
		////////////////////////////
		// KIDS END
		////////////////////////////

		$this->gamestate->nextState( 'NextPlayer' );
	}



	function gMoveTile($tileid,$pid,$x0,$y0,$x1,$y1)
	{
		self::checkAction( 'Move' ); 
		$player_id = self::getCurrentPlayerId();
		$player_no = self::getUniqueValueFromDB("select player_no from player where player_id ='$player_id'" );
		$val = self::getUniqueValueFromDB("select val from animals where id ='$tileid'" );
		
		if ($val=="")
		{
			throw new BgaVisibleSystemException(self::_("You need to select a valid animal."));
		}

		$sql = "update animals set x = $x1, y = $y1, status='PLAYED' where id = '$tileid'";
		self::DbQuery( $sql );

		$sql = "update player set money = money - 1 where player_id = '$player_id'";
		self::DbQuery( $sql );

		self::incStat( 1, "coinsspent", $player_id);

		////////////////////////////
		// KIDS
		////////////////////////////
		
		if (substr($val, 0, 5) != "Stall")
		{
			$enclosures = self::getObjectListFromDB( "SELECT distinct x as x from animals where id = '$tileid'" );
			foreach( $enclosures as $index => $enclosure)
			{
				$males = intval(self::getUniqueValueFromDB("select count(*) from animals where id < 300 and val like '_M' and player_id='$player_id' and x = '" . $enclosure['x']. "'" ));
				$females = intval(self::getUniqueValueFromDB("select count(*) from animals where id < 300 and val like '_F' and player_id='$player_id' and x = '" . $enclosure['x']. "'" ));
				$kids = intval(self::getUniqueValueFromDB("select count(*) from animals where val like '_K' and player_id='$player_id' and x = '" . $enclosure['x']. "'" ));
				$parents = min($males,$females);
				$maxid = intval(self::getUniqueValueFromDB("select MAX(id) from animals where id < 300" ));
				$spacesoccupied = intval(self::getUniqueValueFromDB("select count(*) from animals where player_id='$player_id' and x = '" . $enclosure['x']. "'" ));
				$animal = self::getUniqueValueFromDB("select distinct LEFT(val , 1) from animals where player_id='$player_id' and x = '" . $enclosure['x']. "'");
				$spacesleft = 0;
				if ($enclosure['x']=="1") $spacesleft = 5 - $spacesoccupied;
				else if ($enclosure['x']=="2") $spacesleft = 4 - $spacesoccupied;
				else if ($enclosure['x']=="3") $spacesleft = 6 - $spacesoccupied;
				else if ($enclosure['x']=="4") $spacesleft = 5 - $spacesoccupied;
				else if ($enclosure['x']=="5") $spacesleft = 5 - $spacesoccupied;
				$totalkids = $parents; //-$kids;

				$sql = "update animals set id = id + 300 where id < 300 and val like '_M' and player_id='$player_id' and x = '" . $enclosure['x']. "' limit $totalkids";
				self::DbQuery( $sql );
				$sql = "update animals set id = id + 300 where id < 300 and val like '_F' and player_id='$player_id' and x = '" . $enclosure['x']. "' limit $totalkids";
				self::DbQuery( $sql );

				$newparents = self::getObjectListFromDB( "SELECT concat('tile_',b.player_no,'_',a.id-300,'_',a.val,'_',a.x,'_',a.y) oldparenttile, concat('tile_',b.player_no,'_',a.id,'_',a.val,'_',a.x,'_',a.y) parenttile, a.id, a.val,a.x,a.y, b.player_no FROM `animals` a, player b WHERE a.player_id = b.player_id and a.id > 300");

				for( $x=1; $x<= $totalkids; $x++ )
				{
					if ($spacesleft>0)
					{
						$maxid = $maxid + 1;
						$spacesleft = $spacesleft - 1;
						$spacesoccupied = $spacesoccupied + 1;

						$found = false;
						$spaceid = 0;
						while (!$found)
						{
							$spaceid = $spaceid + 1;
							$check = intval(self::getUniqueValueFromDB("select count(*) from animals where player_id='$player_id' and x = '" . $enclosure['x']. "' and y='$spaceid'" ));
							if ($check==0)
							{
								$found = true;
							}
						}

						$sql = "insert into animals (id, idsel, idorder, player_id, val, status,x,y) values ('" . $maxid . "',null,0,'$player_id','".$animal."K','THIKINGKID',".$enclosure['x'].", $spaceid)";
						self::DbQuery( $sql );
						$kids = self::getObjectListFromDB( "SELECT concat('tile_',$player_no,'_',id,'_',val,'_',x,'_',y) kidtile, id, val,x,y FROM `animals` WHERE status = 'THIKINGKID'");
						$kidsstall = "";

						self::notifyAllPlayers( "Babies", clienttranslate( '${player_name} received a newborn ${translatedval} that goes into his enclosure.'), 
						array(
							'player_id' => $player_id,
							'player_no' => $player_no,
							'kids' => $kids,
							'kidsstall' => $kidsstall,
							'player_name' => self::getCurrentPlayerName(),
							'translatedval' => $this->DecodeAnimal($animal."K"),
							'newparents'=>$newparents,
							'i18n' => array( 'translatedval' )
						) );

						$sql = "update animals set status = 'PLAYED' where status = 'THIKINGKID'";
						self::DbQuery( $sql );
						$sql = "update animals set status = 'STALL' where status = 'THIKINGKIDSTALL'";
						self::DbQuery( $sql );
					}
					else
					{
						$maxid = $maxid + 1;
						$sql = "insert into animals (id, idsel, idorder, player_id, val, status,x,y) values ('" . $maxid . "',null,0,'$player_id','".$animal."K','THIKINGKIDSTALL',0,0)";
						self::DbQuery( $sql );
						$kids = "";
						$kidsstall = self::getObjectListFromDB( "SELECT concat('tile_',".$player_no.",'_',id,'_',val,'_',x,'_',y) as kidtile, id, val,x,y FROM `animals` WHERE status = 'THIKINGKIDSTALL'");

						self::notifyAllPlayers( "Babies", clienttranslate( '${player_name} received a newborn ${translatedval} that goes into his Barn.'), 
						array(
							'player_id' => $player_id,
							'player_no' => $player_no,
							'kids' => $kids,
							'kidsstall' => $kidsstall,
							'player_name' => self::getCurrentPlayerName(),
							'translatedval' => $this->DecodeAnimal($animal."K"),
							'newparents'=>$newparents,
							'i18n' => array( 'translatedval' )
						) );

						$sql = "update animals set status = 'PLAYED' where status = 'THIKINGKID'";
						self::DbQuery( $sql );
						$sql = "update animals set status = 'STALL' where status = 'THIKINGKIDSTALL'";
						self::DbQuery( $sql );
					}
				}

				if ($spacesleft==0)
				{
					$coinsgained = 0;
					if ($enclosure['x']=="1") $coinsgained=2;
					else if ($enclosure['x']=="2") $coinsgained=1;
					else if ($enclosure['x']=="3") $coinsgained=0;
					else if ($enclosure['x']=="4") $coinsgained=1;
					else if ($enclosure['x']=="5") $coinsgained=1;
					
					if ($coinsgained>0)
					{
						$coinsbefore = intval(self::getUniqueValueFromDB("select money from player where player_id = '$player_id'" ));

						$sql = "update player set money = money + " . intval($coinsgained) . " where player_id = '$player_id'";
						self::DbQuery( $sql );

						self::incStat( intval($coinsgained) , "coinsreceived", $player_id);

						self::notifyAllPlayers( "CoinsGained", clienttranslate( '${player_name} gained ${coinsgained} money for completing his ${pos} enclosure.'), 
						array(
							'player_id' => $player_id,
							'player_no' => $player_no,
							'coinsgained' => $coinsgained,
							'coinsbefore' => $coinsbefore,
							'enclosure' => $enclosure['x'],
							'player_name' => self::getCurrentPlayerName(),
							'pos' => $this->DecodePos($enclosure['x']),
							'i18n' => array( 'pos' )
						) );
					}
				}
			}		
		}
		////////////////////////////
		// KIDS END
		////////////////////////////

		if ($x0=="0" && $y0=="0")
		{
			if (substr($val, 0, 5) != "Stall")
			{
				self::notifyAllPlayers( "Move", clienttranslate( '${player_name} moved the ${translatedval} from his Barn to his ${pos2} enclosure.'), 
				array(
					'player_id' => $player_id,
					'player_no' => $player_no,
					'tileid' => $tileid,
					'pid' => $pid,
					'x0' => $x0,
					'y0' => $y0,
					'x1' => $x1,
					'y1' => $y1,
					'val' => $val,
					'translatedval' => $this->DecodeAnimal($val),
					'pos1' => $this->DecodePos($x0),
					'pos2' => $this->DecodePos($x1),
					'player_name' => self::getCurrentPlayerName(),
					'i18n' => array( 'translatedval', 'pos1', 'pos2')
				) );
			}
			else
			{
				self::notifyAllPlayers( "Move", clienttranslate( '${player_name} moved the ${translatedval} from his Barn.'), 
				array(
					'player_id' => $player_id,
					'player_no' => $player_no,
					'tileid' => $tileid,
					'pid' => $pid,
					'x0' => $x0,
					'y0' => $y0,
					'x1' => $x1,
					'y1' => $y1,
					'val' => $val,
					'translatedval' => $this->DecodeAnimal($val),
					'pos1' => $this->DecodePos($x0),
					'pos2' => $this->DecodePos($x1),
					'player_name' => self::getCurrentPlayerName(),
					'i18n' => array( 'translatedval', 'pos1', 'pos2')
				) );
			}
		}
		else
		{
			if (substr($val, 0, 5) != "Stall")
			{
				self::notifyAllPlayers( "Move", clienttranslate( '${player_name} moved the ${translatedval} from his ${pos1} enclosure to his ${pos2} enclosure.'), 
				array(
					'player_id' => $player_id,
					'player_no' => $player_no,
					'tileid' => $tileid,
					'pid' => $pid,
					'x0' => $x0,
					'y0' => $y0,
					'x1' => $x1,
					'y1' => $y1,
					'val' => $val,
					'translatedval' => $this->DecodeAnimal($val),
					'pos1' => $this->DecodePos($x0),
					'pos2' => $this->DecodePos($x1),
					'player_name' => self::getCurrentPlayerName(),
					'i18n' => array( 'translatedval', 'pos1', 'pos2')
				) );
			}
			else
			{
				self::notifyAllPlayers( "Move", clienttranslate( '${player_name} moved the position of his ${translatedval}.'), 
				array(
					'player_id' => $player_id,
					'player_no' => $player_no,
					'tileid' => $tileid,
					'pid' => $pid,
					'x0' => $x0,
					'y0' => $y0,
					'x1' => $x1,
					'y1' => $y1,
					'val' => $val,
					'translatedval' => $this->DecodeAnimal($val),
					'pos1' => $this->DecodePos($x0),
					'pos2' => $this->DecodePos($x1),
					'player_name' => self::getCurrentPlayerName(),
					'i18n' => array( 'translatedval', 'pos1', 'pos2')
				) );
			}
		}
		$this->gamestate->nextState( 'NextPlayer' );
	}

	function gAutoArrangeTiles($wagonid,$tileid1,$posid1,$tileid2,$posid2,$tileid3,$posid3,$x1,$y1,$x2,$y2,$x3,$y3)
	{
		self::checkAction( 'AutoArrangeTiles' ); 
		$player_id = self::getCurrentPlayerId();
		$player_no = self::getUniqueValueFromDB("select player_no from player where player_id ='$player_id'" );
		$val1 = "";
		$val2 = "";
		$val3 = "";
		if ($tileid1!="")
		{
			$val1 = self::getUniqueValueFromDB("select val from animals where id ='$tileid1'" );

			$sql = "update animals set x = $x1, y = $y1, status = 'THINKING', player_id = '$player_id' where id = '$tileid1'";
			self::DbQuery( $sql );

			$sql = "update wagons set val$posid1 = '' where id = '$wagonid'";
			self::DbQuery( $sql );
		}
		if ($tileid2!="")
		{
			$val2 = self::getUniqueValueFromDB("select val from animals where id ='$tileid2'" );

			$sql = "update animals set x = $x2, y = $y2, status = 'THINKING', player_id = '$player_id' where id = '$tileid2'";
			self::DbQuery( $sql );

			$sql = "update wagons set val$posid2 = '' where id = '$wagonid'";
			self::DbQuery( $sql );
		}
		if ($tileid3!="")
		{
			$val3 = self::getUniqueValueFromDB("select val from animals where id ='$tileid3'" );

			$sql = "update animals set x = $x3, y = $y3, status = 'THINKING', player_id = '$player_id' where id = '$tileid3'";
			self::DbQuery( $sql );

			$sql = "update wagons set val$posid3 = '' where id = '$wagonid'";
			self::DbQuery( $sql );
		}
		
		self::notifyAllPlayers( "AutoArrangeTiles", clienttranslate( '${player_name} decided to auto arrange his tiles from the wagon.'), 
		array(
			'player_id' => $player_id,
			'player_no' => $player_no,
			'wagonid' => $wagonid,
			'tileid1' => $tileid1,
			'posid1' => $posid1,
			'tileid2' => $tileid2,
			'posid2' => $posid2,
			'tileid3' => $tileid3,
			'posid3' => $posid3,
			'val1' => $val1,
			'val2' => $val2,
			'val3' => $val3,
			'x1' => $x1,
			'y1' => $y1,
			'x2' => $x2,
			'y2' => $y2,
			'x3' => $x3,
			'y3' => $y3,
			'player_name' => self::getCurrentPlayerName(),
		) );

		
	}
	
	function gArrangeTiles($tileid,$wagonid,$posid,$x,$y,$pid)
	{
		self::checkAction( 'ArrangeTiles' ); 
		$player_id = self::getCurrentPlayerId();
		$player_no = self::getUniqueValueFromDB("select player_no from player where player_id ='$player_id'" );
		$val = self::getUniqueValueFromDB("select val from animals where id ='$tileid'" );

		$sql = "update animals set x = $x, y = $y, status = 'THINKING', player_id = '$player_id' where id = '$tileid'";
		self::DbQuery( $sql );
		if ($pid=="0")
		{
			$sql = "update wagons set val$posid = '' where id = '$wagonid'";
			self::DbQuery( $sql );
		}
		
		self::notifyAllPlayers( "ArrangeTiles", clienttranslate( '${player_name} placed the ${translatedval} in his ${pos} enclosure.'), 
		array(
			'player_id' => $player_id,
			'player_no' => $player_no,
			'tileid' => $tileid,
			'wagonid' => $wagonid,
			'posid' => $posid,
			'val' => $val,
			'x' => $x,
			'y' => $y,
			'pid' => $pid,
			'translatedval' => $this->DecodeAnimal($val),
			'pos' => $this->DecodePos($x),
			'player_name' => self::getCurrentPlayerName(),
			'i18n' => array( 'translatedval', 'pos' )
		) );

		
	}
	
	function stNextTurn()
	{
		$sql = "update animals set status = 'DISCARDED' where status = 'WAGON'";
		self::DbQuery( $sql );
		$sql = "update wagons set status = 'AVAILABLE', val1='', val2='', val3=''";
		self::DbQuery( $sql );
		$sql = "update player set skipped='N'";
		self::DbQuery( $sql );

		$wagons = self::getObjectListFromDB( "SELECT id, val1, val2, val3, status, size from wagons" );

		$lastround = self::getUniqueValueFromDB("select distinct lastround from player" );
		
		if ($lastround=="N")
		{
			self::notifyAllPlayers( "EndTurn", clienttranslate( 'Turn is over... starting another turn.'), 
			array(
				'wagons' => $wagons,
			) );
			
			$this->gamestate->nextState( 'NextPlayer' );
		}
		else
		{
			$this->CalculateScore();
			$this->gamestate->nextState( 'GameEnd' );
		}
	}
	
	function CalculateScore()
	{
		//////////////////////////////
		/// FULL ENCLOSURES
		//////////////////////////////
		$fullenclosures = self::getObjectListFromDB( "select player_id, x, 
													case 
													  when x = 1 then 8
													  when x = 2 then 5
													  when x = 3 then 10
													  when x = 4 then 9
													  when x = 5 then 9
													  else 0
													end as score, count(*) from animals where status = 'PLAYED' and val not like 'Stall_' group by player_id, x having (x,count(*)) in ((1,5),(2,4),(3,6),(4,5),(5,5)) order by player_id" );
		foreach( $fullenclosures as $index => $fullenclosure)
		{
			$sql = "update player set player_score =  player_score + ".$fullenclosure['score']." where player_id = '" . $fullenclosure['player_id']. "'";
			self::DbQuery( $sql );

			$pname = self::getUniqueValueFromDB("select player_name from player where player_id='".$fullenclosure['player_id']."'" );
			$player_score = self::getUniqueValueFromDB("select player_score from player where player_id='".$fullenclosure['player_id']."'" );
			$player_no = self::getUniqueValueFromDB("select player_no from player where player_id='".$fullenclosure['player_id']."'" );

			self::incStat( $fullenclosure['score'], "full" . $fullenclosure['x'], $fullenclosure['player_id']);

			self::notifyAllPlayers( "Score", clienttranslate( '${player_name} scored ${points} points for his fully completed ${pos} enclosure.'), 
			array(
				'player_id' => $fullenclosure['player_id'],
				'player_no' => $player_no,
				'points' => $fullenclosure['score'],
				'pos' => $this->DecodePos($fullenclosure['x']),
				'type' => 1,
				'enc' => $fullenclosure['x'],
				'player_name' => $pname,
				'player_score' => $player_score,
				'i18n' => array( 'pos' )
			) );
		}		

		//////////////////////////////
		/// PART FULL ENCLOSURES
		//////////////////////////////
		$partfullenclosures = self::getObjectListFromDB( "select player_id, x, 
													case 
													  when x = 1 then 5
													  when x = 2 then 4
													  when x = 3 then 6
													  when x = 4 then 5
													  when x = 5 then 5
													  else 0
													end as score, count(*) from animals where status = 'PLAYED' and val not like 'Stall_' group by player_id, x having (x,count(*)) in ((1,4),(2,3),(3,5),(4,4),(5,4)) order by player_id" );
		foreach( $partfullenclosures as $index => $partfullenclosure)
		{
			$sql = "update player set player_score =  player_score + ".$partfullenclosure['score']." where player_id = '" . $partfullenclosure['player_id']. "'";
			self::DbQuery( $sql );

			$pname = self::getUniqueValueFromDB("select player_name from player where player_id='".$partfullenclosure['player_id']."'" );
			$player_score = self::getUniqueValueFromDB("select player_score from player where player_id='".$partfullenclosure['player_id']."'" );
			$player_no = self::getUniqueValueFromDB("select player_no from player where player_id='".$partfullenclosure['player_id']."'" );

			self::incStat( $partfullenclosure['score'], "part" . $partfullenclosure['x'], $partfullenclosure['player_id']);

			self::notifyAllPlayers( "Score", clienttranslate( '${player_name} scored ${points} points for his ${pos} enclosure with one single space empty.'), 
			array(
				'player_id' => $partfullenclosure['player_id'],
				'player_no' => $player_no,
				'points' => $partfullenclosure['score'],
				'pos' => $this->DecodePos($partfullenclosure['x']),
				'type' => 1,
				'enc' => $partfullenclosure['x'],
				'player_name' => $pname,
				'player_score' => $player_score,
				'i18n' => array( 'pos' )
			) );
		}		

		//////////////////////////////
		/// ENCLOSURES WITH STALLS
		//////////////////////////////
		$stallenclosures = self::getObjectListFromDB( "select player_id, x, 
															count(*) as score, count(*) from animals a where status = 'PLAYED' and val not like 'Stall_' 
															and exists (select 1 from animals b where b.status = 'PLAYED' and b.val like 'Stall_' and a.player_id = b.player_id
															and case when b.y=1 then 1 when b.y=2 then 2 when b.y=3 then 2 when b.y=4 then 3 when b.y=5 then 4 when b.y=6 then 5 end = a.x)
															group by player_id, x 
															having (x,count(*)) not in ((1,5),(2,4),(3,6),(4,5),(5,5),(1,4),(2,3),(3,5),(4,4),(5,4))
															order by player_id" );
		foreach( $stallenclosures as $index => $stallenclosure)
		{
			$sql = "update player set player_score =  player_score + ".$stallenclosure['score']." where player_id = '" . $stallenclosure['player_id']. "'";
			self::DbQuery( $sql );

			$pname = self::getUniqueValueFromDB("select player_name from player where player_id='".$stallenclosure['player_id']."'" );
			$player_score = self::getUniqueValueFromDB("select player_score from player where player_id='".$stallenclosure['player_id']."'" );
			$player_no = self::getUniqueValueFromDB("select player_no from player where player_id='".$stallenclosure['player_id']."'" );

			self::incStat( $stallenclosure['score'], "encstall" . $stallenclosure['x'], $stallenclosure['player_id']);

			self::notifyAllPlayers( "Score", clienttranslate( '${player_name} scored ${points} points for his ${pos} enclosure with a Stall.'), 
			array(
				'player_id' => $stallenclosure['player_id'],
				'player_no' => $player_no,
				'points' => $stallenclosure['score'],
				'pos' => $this->DecodePos($stallenclosure['x']),
				'type' => 1,
				'enc' => $stallenclosure['x'],
				'player_name' => $pname,
				'player_score' => $player_score,
				'i18n' => array( 'pos' )
			) );
		}		

		//////////////////////////////
		/// DIFFERENT STALLS
		//////////////////////////////
		$differentstalls = self::getObjectListFromDB( "select player_id, count(distinct val) diffstalls, count(distinct val)*2 score from animals
															where status = 'PLAYED'
															and val like 'Stall_'
															group by player_id
															order by player_id" );
		foreach( $differentstalls as $index => $differentstall)
		{
			$sql = "update player set player_score =  player_score + ".$differentstall['score']." where player_id = '" . $differentstall['player_id']. "'";
			self::DbQuery( $sql );

			$pname = self::getUniqueValueFromDB("select player_name from player where player_id='".$differentstall['player_id']."'" );
			$player_score = self::getUniqueValueFromDB("select player_score from player where player_id='".$differentstall['player_id']."'" );
			$player_no = self::getUniqueValueFromDB("select player_no from player where player_id='".$differentstall['player_id']."'" );

			self::incStat( $differentstall['score'], "stalls", $differentstall['player_id']);

			self::notifyAllPlayers( "Score", clienttranslate( '${player_name} scored ${points} points his ${diffstalls} different Stalls.'), 
			array(
				'player_id' => $differentstall['player_id'],
				'player_no' => $player_no,
				'points' => $differentstall['score'],
				'diffstalls' => $differentstall['diffstalls'],
				'type' => 2,
				'player_name' => $pname,
				'player_score' => $player_score,
			) );
		}		

		//////////////////////////////
		/// LEFT IN STALLS
		//////////////////////////////
		$leftstalls = self::getObjectListFromDB( "select player_id, case when val like 'Stall_' then val else left(val,1) end val, 2 score from animals
															where status = 'STALL'
															group by player_id, case when val like 'Stall_' then val else left(val,1) end
															order by player_id" );
		foreach( $leftstalls as $index => $leftstall)
		{
			$sql = "update player set player_score =  player_score - ".$leftstall['score']." where player_id = '" . $leftstall['player_id']. "'";
			self::DbQuery( $sql );

			$pname = self::getUniqueValueFromDB("select player_name from player where player_id='".$leftstall['player_id']."'" );
			$player_score = self::getUniqueValueFromDB("select player_score from player where player_id='".$leftstall['player_id']."'" );
			$player_no = self::getUniqueValueFromDB("select player_no from player where player_id='".$leftstall['player_id']."'" );

			self::incStat( $leftstall['score'], "leftinbarn", $leftstall['player_id']);

			self::notifyAllPlayers( "Score", clienttranslate( '${player_name} lost -${points} points his ${translatedval} in his Barn.'), 
			array(
				'player_id' => $leftstall['player_id'],
				'player_no' => $player_no,
				'points' => $leftstall['score'],
				'type' => 3,
				'key' => $leftstall['val'],
				'player_name' => $pname,
				'player_score' => $player_score,
				'translatedval' => $this->DecodeAnimal($leftstall['val']),
				'i18n' => array( 'translatedval' )
			) );
		}		


		$finalscore = self::getObjectListFromDB( "select player_id, player_score, money from player" );
		foreach( $finalscore as $index => $fs)
		{
			self::incStat( $fs['player_score'], "totalpoints", $fs['player_id']);
			self::incStat( $fs['money'], "totalcoins", $fs['player_id']);
		}

		$sql = "UPDATE player set player_score_aux = money";
		self::DbQuery( $sql );
	}
	
	function stNextPlayer()
	{
		$count = self::getUniqueValueFromDB("select count(*) from player where skipped='N'" );
		
		if (intval($count)>0)
		{
			$player_id = self::getActivePlayerId();
			self::giveExtraTime( $player_id );
			$found = false;
			while (!$found)
			{
				$this->activeNextPlayer();
				$player_id = self::getActivePlayerId();
				$count = self::getUniqueValueFromDB("select count(*) from player where skipped='N' and player_id='$player_id'" );
				if (intval($count)>0)
				{
					$found = true;
				}
			}
			$this->gamestate->nextState( 'NextPlayer' );
		}
		else
		{
			$this->gamestate->nextState( 'NextTurn' );
		}
	}

	
	function gDrawTile()
	{
		self::checkAction( 'DrawTile' ); 
		$id = $this->DealAnimalsStatus("DRAWN");
		$player_id = self::getCurrentPlayerId();
		$player_no = self::getUniqueValueFromDB("select player_no from player where player_id ='$player_id'" );
		$val = self::getUniqueValueFromDB("select val from animals where id ='$id'" );

		$tilesleft =  self::getUniqueValueFromDB( "SELECT count(*) from animals where status='AVAILABLE'" );
		$tilesleft2 =  self::getUniqueValueFromDB( "SELECT count(*) from animals where status='LASTSET'" );

		
		self::notifyAllPlayers( "DrawTile", clienttranslate( '${player_name} drew a ${translatedval} tile.'), 
		array(
			'player_id' => $player_id,
			'player_no' => $player_no,
			'id' => $id,
			'val' => $val,
			'tilesleft' => $tilesleft,
			'tilesleft2' => $tilesleft2,
			'translatedval' => $this->DecodeAnimal($val),
			'player_name' => self::getCurrentPlayerName(),
			'i18n' => array( 'translatedval' )
		) );

		$this->gamestate->nextState( 'PlaceTile' );
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


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////    

    /*
        In this space, you can put any utility methods useful for your game logic
    */



//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in zooloretto.action.php)
    */

    /*
    
    Example:

    function playCard( $card_id )
    {
        // Check that this is the player's turn and that it is a "possible action" at this game state (see states.inc.php)
        self::checkAction( 'playCard' ); 
        
        $player_id = self::getActivePlayerId();
        
        // Add your game logic to play a card there 
        ...
        
        // Notify all players about the card played
        self::notifyAllPlayers( "cardPlayed", clienttranslate( '${player_name} plays ${card_name}' ), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card_name' => $card_name,
            'card_id' => $card_id
        ) );
          
    }
    
    */

    
//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    /*
    
    Example for game state "MyGameState":
    
    function argMyGameState()
    {
        // Get some values from the current game situation in database...
    
        // return values:
        return array(
            'variable1' => $value1,
            'variable2' => $value2,
            ...
        );
    }    
    */

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */
    
    /*
    
    Example for game state "MyGameState":

    function stMyGameState()
    {
        // Do some stuff ...
        
        // (very often) go to another gamestate
        $this->gamestate->nextState( 'some_gamestate_transition' );
    }    
    */

//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:
        
        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
        
        Important: your zombie code will be called when the player leaves the game. This action is triggered
        from the main site and propagated to the gameserver from a server, not from a browser.
        As a consequence, there is no current player associated to this action. In your zombieTurn function,
        you must _never_ use getCurrentPlayerId() or getCurrentPlayerName(), otherwise it will fail with a "Not logged" error message. 
    */

    function zombieTurn( $state, $active_player )
    {
    	$statename = $state['name'];
    	
        if ($state['type'] === "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->nextState( "zombiePass" );
                	break;
            }

            return;
        }

        if ($state['type'] === "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $this->gamestate->setPlayerNonMultiactive( $active_player, '' );
            
            return;
        }

        throw new feException( "Zombie mode not supported at this game state: ".$statename );
    }
    
///////////////////////////////////////////////////////////////////////////////////:
////////// DB upgrade
//////////

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
}
