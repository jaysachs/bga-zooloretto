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
 * states.inc.php
 *
 * Zooloretto game states description
 *
 */

/*
   Game state machine is a tool used to facilitate game developpement by doing common stuff that can be set up
   in a very easy way from this configuration file.

   Please check the BGA Studio presentation about game state to understand this, and associated documentation.

   Summary:

   States types:
   _ activeplayer: in this type of state, we expect some action from the active player.
   _ multipleactiveplayer: in this type of state, we expect some action from multiple players (the active players)
   _ game: this is an intermediary state where we don't expect any actions from players. Your game logic must decide what is the next game state.
   _ manager: special type for initial and final state

   Arguments of game states:
   _ name: the name of the GameState, in order you can recognize it on your own code.
   _ description: the description of the current game state is always displayed in the action status bar on
                  the top of the game. Most of the time this is useless for game state with "game" type.
   _ descriptionmyturn: the description of the current game state when it's your turn.
   _ type: defines the type of game states (activeplayer / multipleactiveplayer / game / manager)
   _ action: name of the method to call when this game state become the current game state. Usually, the
             action method is prefixed by "st" (ex: "stMyGameStateName").
   _ possibleactions: array that specify possible player actions on this step. It allows you to use "checkAction"
                      method on both client side (Javacript: this.checkAction) and server side (PHP: self::checkAction).
   _ transitions: the transitions are the possible paths to go from a game state to another. You must name
                  transitions in order to use transition names in "nextState" PHP method, and use IDs to
                  specify the next game state for each transition.
   _ args: name of the method to call to retrieve arguments for this gamestate. Arguments are sent to the
           client side to be used on "onEnteringState" or to set arguments in the gamestate description.
   _ updateGameProgression: when specified, the game progression is updated (=> call to your getGameProgression
                            method).
*/

//    !! It is not a good idea to modify this file when a game is running !!

 
$machinestates = array(

    // The initial state. Please do not modify.
    1 => array(
        "name" => "gameSetup",
        "description" => "",
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => array( "" => 2 )
    ),
    
    // Note: ID=2 => your first state

    2 => array(
    		"name" => "playerTurn",
    		"description" => clienttranslate('${actplayer} must draw a tile to add to a wagon, take a wagon and pass or perform a money action.'),
    		"descriptionmyturn" => clienttranslate('${you} must draw a tile to add to a wagon, take a wagon and pass or perform a money action.'),
    		"type" => "activeplayer",
			"args" => "argplayerTurn",
    		"possibleactions" => array( "DrawTile", "TakeWagon", "BuyEnclosure", "Move", "Swap", "Buy", "Discard" ),
    		"transitions" => array( "PlaceTile" => 3, "ArrangeZoo" => 5, "NextPlayer" => 4, "Move" => 7, "Swap" => 8, "Buy" => 9, "Discard" => 10)
    ),

    7 => array(
    		"name" => "Move",
    		"description" => clienttranslate('${actplayer} must move an animal tile or a stall tile from one space to another.'),
    		"descriptionmyturn" => clienttranslate('${you} must move an animal tile or a stall tile from one space to another.'),
    		"type" => "activeplayer",
    		"possibleactions" => array( "Move", "Back" ),
    		"transitions" => array( "Back" => 2, "NextPlayer" => 4 )
    ),

    8 => array(
    		"name" => "Swap",
    		"description" => clienttranslate('${actplayer} must swap two sets on animals.'),
    		"descriptionmyturn" => clienttranslate('${you} must swap two sets on animals.'),
    		"type" => "activeplayer",
    		"possibleactions" => array( "Swap", "Back" ),
    		"transitions" => array( "Back" => 2, "NextPlayer" => 4 )
    ),
    

    9 => array(
    		"name" => "Buy",
    		"description" => clienttranslate('${actplayer} must buy a tile from an opponent Barn.'),
    		"descriptionmyturn" => clienttranslate('${you} must buy a tile from an opponent Barn.'),
    		"type" => "activeplayer",
    		"possibleactions" => array( "Buy", "Back" ),
    		"transitions" => array( "Back" => 2, "NextPlayer" => 4 )
    ),

    10 => array(
    		"name" => "Discard",
    		"description" => clienttranslate('${actplayer} must discard a tile from his Barn.'),
    		"descriptionmyturn" => clienttranslate('${you} must discard a tile from your Barn.'),
    		"type" => "activeplayer",
    		"possibleactions" => array( "Discard", "Back" ),
    		"transitions" => array( "Back" => 2, "NextPlayer" => 4 )
    ),


    3 => array(
    		"name" => "PlaceTile",
    		"description" => clienttranslate('${actplayer} must place a tile on a Wagon.'),
    		"descriptionmyturn" => clienttranslate('${you} must place a tile on a Wagon.'),
    		"type" => "activeplayer",
    		"possibleactions" => array( "PlaceTile" ),
    		"transitions" => array( "NextPlayer" => 4 )
    ),

    5 => array(
    		"name" => "ArrangeZoo",
    		"description" => clienttranslate('${actplayer} must arrange tiles in his Zoo.'),
    		"descriptionmyturn" => clienttranslate('${you} must arrange tiles in your Zoo.'),
    		"type" => "activeplayer",
    		"possibleactions" => array( "ArrangeTiles", "AutoArrangeTiles", "ConfirmArrangement", "Reset", "GoBack" ),
    		"transitions" => array( "NextPlayer" => 4, "playerTurn" => 2 )
    ),

    4 => array(
    		"name" => "NextPlayer",
    		"description" => clienttranslate('Changing player...'),
    		"type" => "game",
			"action" => "stNextPlayer",
			"updateGameProgression" => true,      
    		"transitions" => array( "NextPlayer" => 2, "NextTurn" => 6, "GameEnd" => 99)
    ),

    6 => array(
    		"name" => "NextTurn",
    		"description" => clienttranslate('Changing player...'),
    		"type" => "game",
			"action" => "stNextTurn",
			"updateGameProgression" => true,      
    		"transitions" => array( "NextPlayer" => 2, "GameEnd" => 99)
    ),
/*
    Examples:
    
    2 => array(
        "name" => "nextPlayer",
        "description" => '',
        "type" => "game",
        "action" => "stNextPlayer",
        "updateGameProgression" => true,   
        "transitions" => array( "endGame" => 99, "nextPlayer" => 10 )
    ),
    
    10 => array(
        "name" => "playerTurn",
        "description" => clienttranslate('${actplayer} must play a card or pass'),
        "descriptionmyturn" => clienttranslate('${you} must play a card or pass'),
        "type" => "activeplayer",
        "possibleactions" => array( "playCard", "pass" ),
        "transitions" => array( "playCard" => 2, "pass" => 2 )
    ), 

*/    
   
    // Final state.
    // Please do not modify (and do not overload action/args methods).
    99 => array(
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    )

);



