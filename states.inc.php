<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * chaisji implementation : © Steve Immer <remmievets@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * states.inc.php
 *
 * chaisji game states description
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
    // These are the mandatory actions that the player must choose from on every turn
    2 => array(
        "name" => "playerTurnAction",
        "description" => clienttranslate('${actplayer} must visit market, pantry or invite a customer'),
        "descriptionmyturn" => clienttranslate('${you} must visit the market or visit the pantry or invite a customer and use an ability'),
        "type" => "activeplayer",
        "possibleactions" => array(
            0 => "playStateChange" ),
        "args" => "arg_playerTurnMain",
        "transitions" => array(
            "next" => 25,
            "market" => 10,
            "pantry" => 11,
            "reserve" => 12 )
    ),
    10 => array(
        "name" => "playerMarketAction",
        "description" => clienttranslate('${actplayer} buying flavors from the market'),
        "descriptionmyturn" => clienttranslate('${you} select tiles in the market to purchase'),
        "type" => "activeplayer",
        "possibleactions" => array(
            0 => "playStateChange",
            1 => "playSelection" ),
        "args" => "arg_playerTurnMain",
        "transitions" => array(
            "next" => 25,
            "advance" => 20 )
    ),
    11 => array(
        "name" => "playerPantryAction",
        "description" => clienttranslate('${actplayer} shopping for items in the pantry'),
        "descriptionmyturn" => clienttranslate('${you} select ${pantry_state} items from the pantry'),
        "type" => "activeplayer",
        "possibleactions" => array(
            0 => "playStateChange",
            1 => "playSelection",
            2 => "playBagPantry" ),
        "args" => "arg_playerTurnMain",
        "transitions" => array(
            "next" => 25,
            "advance" => 20 )
    ),
    12 => array(
        "name" => "playerReserveAction",
        "description" => clienttranslate('${actplayer} is reserving a customer and playing an ability'),
        "descriptionmyturn" => clienttranslate('${you} select a customer to reserve'),
        "type" => "activeplayer",
        "possibleactions" => array(
            0 => "playStateChange",
            1 => "playSelection",
            2 => "playAbility" ),
        "args" => "arg_playerTurnMain",
        "transitions" => array(
            "next" => 25,
            "advance" => 20 )
    ),
    20 => array(
        "name" => "playerFulfillOrder",
        "description" => clienttranslate('${actplayer} may fulfill a customer order'),
        "descriptionmyturn" => clienttranslate('${you} may fulfill a customer order from your teahouse or from the plaza'),
        "type" => "activeplayer",
        "possibleactions" => array(
            0 => "playSelection",
            1 => "playStateChange" ),
        "transitions" => array(
            "next" => 25 )
    ),
    25 => array(
        "name" => "gameNextPlayerTurn",
        "description" => clienttranslate('Advance active player'),
        "type" => "game",
        "action" => "st_gameTurnNextPlayer",
        "updateGameProgression" => true,
        "transitions" => array(
            "next" => 2,
            "endRound" => 50 )
    ),
    50 => array(
        "name" => "gameNextRound",
        "description" => clienttranslate('Setup for next round'),
        "type" => "game",
        "action" => "st_gameNextRound",
        "transitions" => array(
            "next" => 51,
            "endGame" => 99 )
    ),
    51 => array(
        "name" => "playerNextRound",
        "description" => clienttranslate('${actplayer} must select an ability to replace'),
        "descriptionmyturn" => clienttranslate('${you} must select an ability to replace with new ability card'),
        "type" => "activeplayer",
        "possibleactions" => array(
            0 => "playStateChange",
            1 => "playSelection" ),
        "transitions" => array(
            "next" => 2 )
    ),
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
