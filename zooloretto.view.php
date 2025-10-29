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
 * zooloretto.view.php
 *
 * This is your "view" file.
 *
 * The method "build_page" below is called each time the game interface is displayed to a player, ie:
 * _ when the game starts
 * _ when a player refreshes the game page (F5)
 *
 * "build_page" method allows you to dynamically modify the HTML generated for the game interface. In
 * particular, you can set here the values of variables elements defined in zooloretto_zooloretto.tpl (elements
 * like {MY_VARIABLE_ELEMENT}), and insert HTML block elements (also defined in your HTML template file)
 *
 * Note: if the HTML of your game interface is always the same, you don't have to place anything here.
 *
 */
  
  require_once( APP_BASE_PATH."view/common/game.view.php" );
  
  class view_zooloretto_zooloretto extends game_view
  {
    function getGameName() {
        return "zooloretto";
    }    
  	function build_page( $viewArgs )
  	{		
  	    // Get players & players number
        $players = $this->game->loadPlayersBasicInfos();
        $players_nbr = count( $players );

        /*********** Place your code below:  ************/


        /*

        // Examples: set the value of some element defined in your tpl file like this: {MY_VARIABLE_ELEMENT}

        // Display a specific number / string
        $this->tpl['MY_VARIABLE_ELEMENT'] = $number_to_display;

        // Display a string to be translated in all languages: 
        $this->tpl['MY_VARIABLE_ELEMENT'] = self::_("A string to be translated");

        // Display some HTML content of your own:
        $this->tpl['MY_VARIABLE_ELEMENT'] = self::raw( $some_html_code );

        */

        /*

        // Example: display a specific HTML block for each player in this game.
        // (note: the block is defined in your .tpl file like this:
        //      <!-- BEGIN myblock --> 
        //          ... my HTML code ...
        //      <!-- END myblock --> 


        $this->page->begin_block( "zooloretto_zooloretto", "myblock" );
        foreach( $players as $player )
        {
            $this->page->insert_block( "myblock", array( 
                                                    "PLAYER_NAME" => $player['player_name'],
                                                    "SOME_VARIABLE" => $some_value
                                                    ...
                                                     ) );
        }

        */

		global $g_user;
		$current_player_id = $g_user->get_id();
		$player_no = $this->game->getPlayerNoForLayout($current_player_id);

		$count = 0;
		foreach( $players as $player_id => $player ) 
		{	
			$count = $count + 1;
		}

		$ratio = 1;
		$delta = 0;
		if ($count==2)
		{
			$ratio = 0.82020423;
			$delta = 17.979577;
		}

        $this->page->begin_block( "zooloretto_zooloretto", "cell" );
		$this->page->insert_block( "cell", array('PNO' => $player_no, 'X' => 1,	'Y' => 1,'LEFT' => $delta+$ratio* 41.7,	'TOP' => 10.3 ) );
		$this->page->insert_block( "cell", array('PNO' => $player_no, 'X' => 1,	'Y' => 2,'LEFT' => $delta+$ratio* 28.5,	'TOP' => 23.5 ) );
		$this->page->insert_block( "cell", array('PNO' => $player_no, 'X' => 1,	'Y' => 3,'LEFT' => $delta+$ratio* 41.7,	'TOP' => 21.2 ) );
		$this->page->insert_block( "cell", array('PNO' => $player_no, 'X' => 1,	'Y' => 4,'LEFT' => $delta+$ratio* 26,	'TOP' => 34 ) );
		$this->page->insert_block( "cell", array('PNO' => $player_no, 'X' => 1,	'Y' => 5,'LEFT' => $delta+$ratio* 39,	'TOP' => 32 ) );

		$this->page->insert_block( "cell", array('PNO' => $player_no, 'X' => 2,	'Y' => 1,'LEFT' => $delta+$ratio* 64.2, 'TOP' => 7.3 ) );
		$this->page->insert_block( "cell", array('PNO' => $player_no, 'X' => 2,	'Y' => 2,'LEFT' => $delta+$ratio* 66.5, 'TOP' => 18 ) );
		$this->page->insert_block( "cell", array('PNO' => $player_no, 'X' => 2,	'Y' => 3,'LEFT' => $delta+$ratio* 62, 'TOP' => 28.5 ) );
		$this->page->insert_block( "cell", array('PNO' => $player_no, 'X' => 2,	'Y' => 4,'LEFT' => $delta+$ratio* 73, 'TOP' => 34.5 ) );

		$this->page->insert_block( "cell", array('PNO' => $player_no, 'X' => 3,	'Y' => 1,'LEFT' => $delta+$ratio* 66, 'TOP' => 51.6 ) );
		$this->page->insert_block( "cell", array('PNO' => $player_no, 'X' => 3,	'Y' => 2,'LEFT' => $delta+$ratio* 81.3, 'TOP' => 54.8 ) );
		$this->page->insert_block( "cell", array('PNO' => $player_no, 'X' => 3,	'Y' => 3,'LEFT' => $delta+$ratio* 68.8, 'TOP' => 62 ) );
		$this->page->insert_block( "cell", array('PNO' => $player_no, 'X' => 3,	'Y' => 4,'LEFT' => $delta+$ratio* 79.4, 'TOP' => 65.6 ) );
		$this->page->insert_block( "cell", array('PNO' => $player_no, 'X' => 3,	'Y' => 5,'LEFT' => $delta+$ratio* 66, 'TOP' => 72.6 ) );
		$this->page->insert_block( "cell", array('PNO' => $player_no, 'X' => 3,	'Y' => 6,'LEFT' => $delta+$ratio* 67.2, 'TOP' => 83.6 ) );

		$this->page->insert_block( "cell", array('PNO' => $player_no, 'X' => 4,	'Y' => 1,'LEFT' => $delta+$ratio* 7.3, 'TOP' => 23.5 ) );
		$this->page->insert_block( "cell", array('PNO' => $player_no, 'X' => 4,	'Y' => 2,'LEFT' => $delta+$ratio* 9, 'TOP' => 34.2 ) );
		$this->page->insert_block( "cell", array('PNO' => $player_no, 'X' => 4,	'Y' => 3,'LEFT' => $delta+$ratio* 7.3, 'TOP' => 44.5 ) );
		$this->page->insert_block( "cell", array('PNO' => $player_no, 'X' => 4,	'Y' => 4,'LEFT' => $delta+$ratio* 9, 'TOP' => 55.5 ) );
		$this->page->insert_block( "cell", array('PNO' => $player_no, 'X' => 4,	'Y' => 5,'LEFT' => $delta+$ratio* 7.3, 'TOP' => 66.2 ) );

		if ($count==2)
		{
			$this->page->insert_block( "cell", array('PNO' => $player_no, 'X' => 5,	'Y' => 1,'LEFT' => $ratio* 7.3, 'TOP' => 23.5 ) );
			$this->page->insert_block( "cell", array('PNO' => $player_no, 'X' => 5,	'Y' => 2,'LEFT' => $ratio* 9, 'TOP' => 34.2 ) );
			$this->page->insert_block( "cell", array('PNO' => $player_no, 'X' => 5,	'Y' => 3,'LEFT' => $ratio* 7.3, 'TOP' => 44.5 ) );
			$this->page->insert_block( "cell", array('PNO' => $player_no, 'X' => 5,	'Y' => 4,'LEFT' => $ratio* 9, 'TOP' => 55.5 ) );
			$this->page->insert_block( "cell", array('PNO' => $player_no, 'X' => 5,	'Y' => 5,'LEFT' => $ratio* 7.3, 'TOP' => 66.2 ) );

			$this->page->insert_block( "cell", array('PNO' => $player_no, 'X' => 6,	'Y' => 1,'LEFT' => $delta+$ratio* 24.5, 'TOP' => 7.5 ) );
			$this->page->insert_block( "cell", array('PNO' => $player_no, 'X' => 6,	'Y' => 2,'LEFT' => $delta+$ratio* 83.6, 'TOP' => 7.5 ) );
			$this->page->insert_block( "cell", array('PNO' => $player_no, 'X' => 6,	'Y' => 3,'LEFT' => $delta+$ratio* 83.6, 'TOP' => 18 ) );
			$this->page->insert_block( "cell", array('PNO' => $player_no, 'X' => 6,	'Y' => 4,'LEFT' => $delta+$ratio* 83.6, 'TOP' => 83.5 ) );
			$this->page->insert_block( "cell", array('PNO' => $player_no, 'X' => 6,	'Y' => 5,'LEFT' => $delta+$ratio* 7.3, 'TOP' => 83.5 ) );
			$this->page->insert_block( "cell", array('PNO' => $player_no, 'X' => 6,	'Y' => 6,'LEFT' => $ratio* 7.3, 'TOP' => 83.5 ) );
		}
		else
		{
			$this->page->insert_block( "cell", array('PNO' => $player_no, 'X' => 5,	'Y' => 1,'LEFT' => $delta+$ratio* 24.5, 'TOP' => 7.5 ) );
			$this->page->insert_block( "cell", array('PNO' => $player_no, 'X' => 5,	'Y' => 2,'LEFT' => $delta+$ratio* 83.6, 'TOP' => 7.5 ) );
			$this->page->insert_block( "cell", array('PNO' => $player_no, 'X' => 5,	'Y' => 3,'LEFT' => $delta+$ratio* 83.6, 'TOP' => 18 ) );
			$this->page->insert_block( "cell", array('PNO' => $player_no, 'X' => 5,	'Y' => 4,'LEFT' => $delta+$ratio* 83.6, 'TOP' => 83.5 ) );
			$this->page->insert_block( "cell", array('PNO' => $player_no, 'X' => 5,	'Y' => 5,'LEFT' => $delta+$ratio* 7.3, 'TOP' => 83.5 ) );
		}

		$this->page->begin_block( "zooloretto_zooloretto", "cell2" );
		$this->page->begin_block( "zooloretto_zooloretto", "playercards" ); 

		$j=0;
		foreach( $players as $player_id => $player ) 
		{	
			if ($player['player_no'] != $player_no)
			{
				$j = $j + 1;
				$this->page->reset_subblocks( 'cell2' ); 

				$this->page->insert_block( "cell2", array('PNO' => $player['player_no'], 'X' => 1,	'Y' => 1,'LEFT' => $delta+$ratio*41.7,	'TOP' => 10.3 ) );
				$this->page->insert_block( "cell2", array('PNO' => $player['player_no'], 'X' => 1,	'Y' => 2,'LEFT' => $delta+$ratio*28.5,	'TOP' => 23.5 ) );
				$this->page->insert_block( "cell2", array('PNO' => $player['player_no'], 'X' => 1,	'Y' => 3,'LEFT' => $delta+$ratio*41.7,	'TOP' => 21.2 ) );
				$this->page->insert_block( "cell2", array('PNO' => $player['player_no'], 'X' => 1,	'Y' => 4,'LEFT' => $delta+$ratio*26,	'TOP' => 34 ) );
				$this->page->insert_block( "cell2", array('PNO' => $player['player_no'], 'X' => 1,	'Y' => 5,'LEFT' => $delta+$ratio*39,	'TOP' => 32 ) );

				$this->page->insert_block( "cell2", array('PNO' => $player['player_no'], 'X' => 2,	'Y' => 1,'LEFT' => $delta+$ratio*64.2, 'TOP' => 7.3 ) );
				$this->page->insert_block( "cell2", array('PNO' => $player['player_no'], 'X' => 2,	'Y' => 2,'LEFT' => $delta+$ratio*66.5, 'TOP' => 18 ) );
				$this->page->insert_block( "cell2", array('PNO' => $player['player_no'], 'X' => 2,	'Y' => 3,'LEFT' => $delta+$ratio*62, 'TOP' => 28.5 ) );
				$this->page->insert_block( "cell2", array('PNO' => $player['player_no'], 'X' => 2,	'Y' => 4,'LEFT' => $delta+$ratio*73, 'TOP' => 34.5 ) );

				$this->page->insert_block( "cell2", array('PNO' => $player['player_no'], 'X' => 3,	'Y' => 1,'LEFT' => $delta+$ratio*66, 'TOP' => 51.6 ) );
				$this->page->insert_block( "cell2", array('PNO' => $player['player_no'], 'X' => 3,	'Y' => 2,'LEFT' => $delta+$ratio*81.3, 'TOP' => 54.8 ) );
				$this->page->insert_block( "cell2", array('PNO' => $player['player_no'], 'X' => 3,	'Y' => 3,'LEFT' => $delta+$ratio*68.8, 'TOP' => 62 ) );
				$this->page->insert_block( "cell2", array('PNO' => $player['player_no'], 'X' => 3,	'Y' => 4,'LEFT' => $delta+$ratio*79.4, 'TOP' => 65.6 ) );
				$this->page->insert_block( "cell2", array('PNO' => $player['player_no'], 'X' => 3,	'Y' => 5,'LEFT' => $delta+$ratio*66, 'TOP' => 72.6 ) );
				$this->page->insert_block( "cell2", array('PNO' => $player['player_no'], 'X' => 3,	'Y' => 6,'LEFT' => $delta+$ratio*67.2, 'TOP' => 83.6 ) );

				$this->page->insert_block( "cell2", array('PNO' => $player['player_no'], 'X' => 4,	'Y' => 1,'LEFT' => $delta+$ratio*7.3, 'TOP' => 23.5 ) );
				$this->page->insert_block( "cell2", array('PNO' => $player['player_no'], 'X' => 4,	'Y' => 2,'LEFT' => $delta+$ratio*9, 'TOP' => 34.2 ) );
				$this->page->insert_block( "cell2", array('PNO' => $player['player_no'], 'X' => 4,	'Y' => 3,'LEFT' => $delta+$ratio*7.3, 'TOP' => 44.5 ) );
				$this->page->insert_block( "cell2", array('PNO' => $player['player_no'], 'X' => 4,	'Y' => 4,'LEFT' => $delta+$ratio*9, 'TOP' => 55.5 ) );
				$this->page->insert_block( "cell2", array('PNO' => $player['player_no'], 'X' => 4,	'Y' => 5,'LEFT' => $delta+$ratio*7.3, 'TOP' => 66.2 ) );

				if ($count==2)
				{
					$this->page->insert_block( "cell2", array('PNO' => $player['player_no'], 'X' => 5,	'Y' => 1,'LEFT' => $ratio*7.3, 'TOP' => 23.5 ) );
					$this->page->insert_block( "cell2", array('PNO' => $player['player_no'], 'X' => 5,	'Y' => 2,'LEFT' => $ratio*9, 'TOP' => 34.2 ) );
					$this->page->insert_block( "cell2", array('PNO' => $player['player_no'], 'X' => 5,	'Y' => 3,'LEFT' => $ratio*7.3, 'TOP' => 44.5 ) );
					$this->page->insert_block( "cell2", array('PNO' => $player['player_no'], 'X' => 5,	'Y' => 4,'LEFT' => $ratio*9, 'TOP' => 55.5 ) );
					$this->page->insert_block( "cell2", array('PNO' => $player['player_no'], 'X' => 5,	'Y' => 5,'LEFT' => $ratio*7.3, 'TOP' => 66.2 ) );

					$this->page->insert_block( "cell2", array('PNO' => $player['player_no'], 'X' => 6,	'Y' => 1,'LEFT' => $delta+$ratio*24.5, 'TOP' => 7.5 ) );
					$this->page->insert_block( "cell2", array('PNO' => $player['player_no'], 'X' => 6,	'Y' => 2,'LEFT' => $delta+$ratio*83.6, 'TOP' => 7.5 ) );
					$this->page->insert_block( "cell2", array('PNO' => $player['player_no'], 'X' => 6,	'Y' => 3,'LEFT' => $delta+$ratio*83.6, 'TOP' => 18 ) );
					$this->page->insert_block( "cell2", array('PNO' => $player['player_no'], 'X' => 6,	'Y' => 4,'LEFT' => $delta+$ratio*83.6, 'TOP' => 83.5 ) );
					$this->page->insert_block( "cell2", array('PNO' => $player['player_no'], 'X' => 6,	'Y' => 5,'LEFT' => $delta+$ratio*7.3, 'TOP' => 83.5 ) );
					$this->page->insert_block( "cell2", array('PNO' => $player['player_no'], 'X' => 6,	'Y' => 6,'LEFT' => $ratio*7.3, 'TOP' => 83.5 ) );
				}
				else
				{
					$this->page->insert_block( "cell2", array('PNO' => $player['player_no'], 'X' => 5,	'Y' => 1,'LEFT' => $delta+$ratio*24.5, 'TOP' => 7.5 ) );
					$this->page->insert_block( "cell2", array('PNO' => $player['player_no'], 'X' => 5,	'Y' => 2,'LEFT' => $delta+$ratio*83.6, 'TOP' => 7.5 ) );
					$this->page->insert_block( "cell2", array('PNO' => $player['player_no'], 'X' => 5,	'Y' => 3,'LEFT' => $delta+$ratio*83.6, 'TOP' => 18 ) );
					$this->page->insert_block( "cell2", array('PNO' => $player['player_no'], 'X' => 5,	'Y' => 4,'LEFT' => $delta+$ratio*83.6, 'TOP' => 83.5 ) );
					$this->page->insert_block( "cell2", array('PNO' => $player['player_no'], 'X' => 5,	'Y' => 5,'LEFT' => $delta+$ratio*7.3, 'TOP' => 83.5 ) );
				}
				$this->page->insert_block( 'playercards', array('X' => $player['player_no'],'LEFT' => 0,'TOP' => 13 + $j * 35,'A'=>$delta+$ratio*20,'B'=>82) );
			}
		}
        /*********** Do not change anything below this line  ************/
  	}
  }
  

