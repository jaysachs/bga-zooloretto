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
 * gameoptions.inc.php
 *
 * Zooloretto game options description
 * 
 * In this file, you can define your game options (= game variants).
 *   
 * Note: If your game has no variant, you don't have to modify this file.
 *
 * Note²: All options defined in this file should have a corresponding "game state labels"
 *        with the same ID (see "initGameStateLabels" in zooloretto.game.php)
 *
 * !! It is not a good idea to modify this file when a game is running !!
 *
 */

$game_options = array(

    100 => array(
                'name' => totranslate('Tiles Left - Visible/Hidden'),    
                'values' => array(
                            1 => array( 'name' => totranslate('Hidden'), 
									    'tmdisplay' => totranslate('Hidden'),
										'description' => totranslate('Number of tiles left in the draw deck won\'t be visible during the game.')
										),
                            2 => array( 'name' => totranslate('Visible'), 
									    'tmdisplay' => totranslate('Visible'),
										'description' => totranslate('Number of tiles left in the draw deck will be visible during the game.')
										),
						),
                'default' => 2
            ),
);


