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
 * stats.inc.php
 *
 * Zooloretto game statistics description
 *
 */

/*
    In this file, you are describing game statistics, that will be displayed at the end of the
    game.
    
    !! After modifying this file, you must use "Reload  statistics configuration" in BGA Studio backoffice
    ("Control Panel" / "Manage Game" / "Your Game")
    
    There are 2 types of statistics:
    _ table statistics, that are not associated to a specific player (ie: 1 value for each game).
    _ player statistics, that are associated to each players (ie: 1 value for each player in the game).

    Statistics types can be "int" for integer, "float" for floating point values, and "bool" for boolean
    
    Once you defined your statistics there, you can start using "initStat", "setStat" and "incStat" method
    in your game logic, using statistics names defined below.
    
    !! It is not a good idea to modify this file when a game is running !!

    If your game is already public on BGA, please read the following before any change:
    http://en.doc.boardgamearena.com/Post-release_phase#Changes_that_breaks_the_games_in_progress
    
    Notes:
    * Statistic index is the reference used in setStat/incStat/initStat PHP method
    * Statistic index must contains alphanumerical characters and no space. Example: 'turn_played'
    * Statistics IDs must be >=10
    * Two table statistics can't share the same ID, two player statistics can't share the same ID
    * A table statistic can have the same ID than a player statistics
    * Statistics ID is the reference used by BGA website. If you change the ID, you lost all historical statistic data. Do NOT re-use an ID of a deleted statistic
    * Statistic name is the English description of the statistic as shown to players
    
*/

$stats_type = array(

    // Statistics global to table
    "table" => array(

        "turns_number" => array("id"=> 10,
                    "name" => totranslate("Number of turns"),
                    "type" => "int" ),


    ),
    
    // Statistics existing for each player
    "player" => array(

        "full1" => array(   "id"=> 10,
                                "name" => totranslate("Points for completed Enclosure 1"), 
                                "type" => "int" ),
        "full2" => array(   "id"=> 11,
                                "name" => totranslate("Points for completed Enclosure 2"), 
                                "type" => "int" ),
        "full3" => array(   "id"=> 12,
                                "name" => totranslate("Points for completed Enclosure 3"), 
                                "type" => "int" ),
        "full4" => array(   "id"=> 13,
                                "name" => totranslate("Points for completed Enclosure 4"), 
                                "type" => "int" ),
        "full5" => array(   "id"=> 14,
                                "name" => totranslate("Points for completed Enclosure 5"), 
                                "type" => "int" ),
        "part1" => array(   "id"=> 15,
                                "name" => totranslate("Points for almost completed Enclosure 1"), 
                                "type" => "int" ),
        "part2" => array(   "id"=> 16,
                                "name" => totranslate("Points for almost completed Enclosure 2"), 
                                "type" => "int" ),
        "part3" => array(   "id"=> 17,
                                "name" => totranslate("Points for almost completed Enclosure 3"), 
                                "type" => "int" ),
        "part4" => array(   "id"=> 18,
                                "name" => totranslate("Points for almost completed Enclosure 4"), 
                                "type" => "int" ),
        "part5" => array(   "id"=> 19,
                                "name" => totranslate("Points for almost completed Enclosure 5"), 
                                "type" => "int" ),
        "encstall1" => array(   "id"=> 20,
                                "name" => totranslate("Points for Enclosure 1 with Stalls"), 
                                "type" => "int" ),
        "encstall2" => array(   "id"=> 21,
                                "name" => totranslate("Points for Enclosure 2 with Stalls"), 
                                "type" => "int" ),
        "encstall3" => array(   "id"=> 22,
                                "name" => totranslate("Points for Enclosure 3 with Stalls"), 
                                "type" => "int" ),
        "encstall4" => array(   "id"=> 23,
                                "name" => totranslate("Points for Enclosure 4 with Stalls"), 
                                "type" => "int" ),
        "encstall5" => array(   "id"=> 24,
                                "name" => totranslate("Points for Enclosure 5 with Stalls"), 
                                "type" => "int" ),
        "stalls" => array(   "id"=> 25,
                                "name" => totranslate("Points for different Stalls"), 
                                "type" => "int" ),
        "leftinbarn" => array(   "id"=> 26,
                                "name" => totranslate("Negative Points for Animals/Stalls left in Barn"), 
                                "type" => "int" ),
        "totalpoints" => array(   "id"=> 27,
                                "name" => totranslate("Total Points"), 
                                "type" => "int" ),
        "totalcoins" => array(   "id"=> 28,
                                "name" => totranslate("Total Coins"), 
                                "type" => "int" ),
        "coinsspent" => array(   "id"=> 29,
                                "name" => totranslate("Coins Spent"), 
                                "type" => "int" ),
        "coinsreceived" => array(   "id"=> 30,
                                "name" => totranslate("Coins Received"), 
                                "type" => "int" ),
    
/*
        Examples:    
        
        
        "player_teststat1" => array(   "id"=> 10,
                                "name" => totranslate("player test stat 1"), 
                                "type" => "int" ),
                                
        "player_teststat2" => array(   "id"=> 11,
                                "name" => totranslate("player test stat 2"), 
                                "type" => "float" )

*/    
    )

);
