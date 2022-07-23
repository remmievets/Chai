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
 * material.inc.php
 *
 * chaisji game material description
 *
 * Here, you can describe the material of your game with PHP variables.
 *   
 * This file is loaded in your game logic class constructor, ie these variables
 * are available everywhere in your game logic code.
 *
 */



// black/green/blue/red/white
$this->ordered_colors = array ("330000", "00CC00", "0000FF", "FF0000", "FFFFFF");

// Ordered flavors                   0       1          2          3           4         5       6
$this->ordered_flavors = array ('lemon', 'mint', 'berries', 'jasmine', 'lavender', 'ginger', 'wild');

// Ordered pantry                  0          1       2        3        4       5
$this->ordered_pantry = array ('chai', 'vanilla', 'milk', 'sugar', 'honey', 'wild');

// These are the game locations in the database where we will send information in the getAllDatas function.
// For these items the JS only needs to know about the key value
$this->gameDataLocs = array('faceup_ability', 'market_1', 'market_2', 'market_3', 'plaza', 'pantry_board', 'tip_jars');

/*

Example:

$this->card_types = array(
    1 => array( "card_name" => ...,
                ...
              )
);

*/




