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

$this->abbr_flavors = array ('l'=>'lemon', 'm'=>'mint', 'b'=>'berries', 'j'=>'jasmine', 'd'=>'lavender', 'g'=>'ginger', 'w'=>'wild');

// Ordered pantry                  0          1       2        3        4       5
$this->ordered_pantry = array ('chai', 'vanilla', 'milk', 'sugar', 'honey', 'any_additive');

$this->abbr_pantry = array ('c'=>'chai', 'v'=>'vanilla', 'k'=>'milk', 's'=>'sugar', 'h'=>'honey', 'a'=>'any_additive');

// These are the game locations in the database where we will send information in the getAllDatas function.
// For these items the JS only needs to know about the key value
$this->gameDataLocs = array('faceup_ability', 'market_1', 'market_2', 'market_3', 'plaza', 'pantry_board');

$this->token_types = array(
        /* --- gen php begin --- */
'lemon' => array(
  'type' => 'flavor',
  'name' => clienttranslate("Lemon"),
),
'mint' => array(
  'type' => 'flavor',
  'name' => clienttranslate("Mint"),
),
'berries' => array(
  'type' => 'flavor',
  'name' => clienttranslate("Berries"),
),
'jasmine' => array(
  'type' => 'flavor',
  'name' => clienttranslate("Jasmine"),
),
'lavender' => array(
  'type' => 'flavor',
  'name' => clienttranslate("Lavender"),
),
'ginger' => array(
  'type' => 'flavor',
  'name' => clienttranslate("Ginger"),
),
'wild' => array(
  'type' => 'flavor',
  'name' => clienttranslate("Wild flavor"),
),
'chai' => array(
  'type' => 'additive',
  'name' => clienttranslate("Chai"),
),
'vanilla' => array(
  'type' => 'additive',
  'name' => clienttranslate("Vanilla"),
),
'milk' => array(
  'type' => 'additive',
  'name' => clienttranslate("Milk"),
),
'sugar' => array(
  'type' => 'additive',
  'name' => clienttranslate("Sugar"),
),
'honey' => array(
  'type' => 'additive',
  'name' => clienttranslate("Honey"),
),
'any_additive' => array(
  'type' => 'additive',
  'name' => clienttranslate("Wild additive"),
),
'card_ability_0' => array(
  'type' => 'card',
  'name' => clienttranslate("Ability Card"),
  'tooltip' => clienttranslate("Make a copper (1) or silver (2) coin purchase for free in the market."),
),
'card_ability_1' => array(
  'type' => 'card',
  'name' => clienttranslate("Ability Card"),
  'tooltip' => clienttranslate("Sell a flavor tile for a silver (2) coin."),
),
'card_ability_2' => array(
  'type' => 'card',
  'name' => clienttranslate("Ability Card"),
  'tooltip' => clienttranslate("When drawn, place 3 flavor tiles from the tea flavour tiles from the tea flavour bag on this card. The player swaps tiles on this card with tiles from their house."),
),
'card_ability_3' => array(
  'type' => 'card',
  'name' => clienttranslate("Ability Card"),
  'tooltip' => clienttranslate("When fulfilling a customer order this turn, the player receives a gold (3) coin tip."),
),
'card_ability_4' => array(
  'type' => 'card',
  'name' => clienttranslate("Ability Card"),
  'tooltip' => clienttranslate("The player chooses a flavour tile type in the market to be immediately reset."),
),
'card_ability_5' => array(
  'type' => 'card',
  'name' => clienttranslate("Ability Card"),
  'tooltip' => clienttranslate("The player may fulfill a customer order this turn with one fewer pantry item."),
),
'card_ability_6' => array(
  'type' => 'card',
  'name' => clienttranslate("Ability Card"),
  'tooltip' => clienttranslate("The player may swap up to two pantry items in their tea house for items on the pantry board."),
),
'card_ability_7' => array(
  'type' => 'card',
  'name' => clienttranslate("Ability Card"),
  'tooltip' => clienttranslate("The player may take one pantry item from the pantry board for free."),
),
'customer_0_330000' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one Black Tea, one Honey and four Lavender then score 11 points."),
  's' => 11,
  'f' => 'hdddd',
),
'customer_1_330000' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one Black Tea, one Honey and two Lemon then score 7 points."),
  's' => 7,
  'f' => 'hll',
),
'customer_2_330000' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one Black Tea, one Chai, one Ginger, and one Jasmine then score 7 points."),
  's' => 7,
  'f' => 'cgj',
),
'customer_3_330000' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one Black Tea and two Milk then score 5 points."),
  's' => 5,
  'f' => 'kk',
),
'customer_4_330000' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one Black Tea, one Chai, one Milk and one Sugar then score 8 points."),
  's' => 8,
  'f' => 'cks',
),
'customer_5_330000' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one Black Tea, one Chai and two Lavender then score 7 points."),
  's' => 7,
  'f' => 'cdd',
),
'customer_6_330000' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one Black Tea, one Honey, one Sugar, and one Ginger then score 7 points."),
  's' => 7,
  'f' => 'hsg',
),
'customer_7_330000' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one Black Tea, one Vanilla, two Berries, two Ginger, one Mint and one Lemon then score 15 points."),
  's' => 15,
  'f' => 'vbbggml',
),
'customer_8_330000' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one Black Tea, two Vanilla and two Mint then score 9 points."),
  's' => 9,
  'f' => 'vvmm',
),
'customer_9_330000' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one Black Tea, one Milk, two Jasmine and two Lavender then score 11 points."),
  's' => 11,
  'f' => 'kjjdd',
),
'customer_10_330000' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one Black Tea, three Berries, one Mint, one Lemon and one Jasmine then score 12 points."),
  's' => 12,
  'f' => 'bbbmlj',
),
'customer_0_00CC00' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one Green Tea, two Mint, one Lavender and one Jasmine then score 8 points."),
  's' => 8,
  'f' => 'mmdj',
),
'customer_1_00CC00' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one Green Tea, one Milk, one Chai and one Vanilla then score 8 points."),
  's' => 8,
  'f' => 'kcv',
),
'customer_2_00CC00' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one Green Tea, one Sugar and three Mint then score 9 points."),
  's' => 9,
  'f' => 'smmm',
),
'customer_3_00CC00' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one Green Tea, one Honey, one Lemon, one Jasmine and two Ginger then score 11 points."),
  's' => 11,
  'f' => 'hljgg',
),
'customer_4_00CC00' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one Green Tea, one Jasmine and two Mint then score 6 points."),
  's' => 6,
  'f' => 'jmm',
),
'customer_5_00CC00' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one Green Tea, one Honey, two Ginger and four Lemon then score 15 points."),
  's' => 15,
  'f' => 'hggllll',
),
'customer_6_00CC00' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one Green Tea, one Berries and two Jasmine then score 6 points."),
  's' => 6,
  'f' => 'bjj',
),
'customer_7_00CC00' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one Green Tea, one Milk and two Vanilla then score 8 points."),
  's' => 8,
  'f' => 'kvv',
),
'customer_8_00CC00' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one Green Tea, two Sugar and two Lavender then score 9 points."),
  's' => 9,
  'f' => 'ssdd',
),
'customer_9_00CC00' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one Green Tea, one Chai, one Milk and one Sugar then score 8 points."),
  's' => 8,
  'f' => 'cks',
),
'customer_10_00CC00' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one Green Tea, two Berries, two Lemon, one Ginger and one Jasmine then score 12 points."),
  's' => 12,
  'f' => 'bbllgj',
),
'customer_0_0000FF' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one Oolong Tea (Blue), one Chai, one Milk and one Honey then score 8 points."),
  's' => 8,
  'f' => 'ckh',
),
'customer_1_0000FF' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one Oolong Tea (Blue) and four Jasmine then score 8 points."),
  's' => 8,
  'f' => 'jjjj',
),
'customer_2_0000FF' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one Oolong Tea (Blue) and three Mint then score 6 points."),
  's' => 6,
  'f' => 'mmm',
),
'customer_3_0000FF' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one Oolong Tea (Blue), two Honey and one Ginger then score 7 points."),
  's' => 7,
  'f' => 'hhg',
),
'customer_4_0000FF' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one Oolong Tea (Blue), one Chai, one Vanilla and one Lavender then score 7 points."),
  's' => 7,
  'f' => 'cvd',
),
'customer_5_0000FF' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one Oolong Tea (Blue), one Sugar, two Ginger, one Jasmine and three Lemon then score 15 points."),
  's' => 15,
  'f' => 'sggjlll',
),
'customer_6_0000FF' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one Oolong Tea (Blue), two Jasmine, two Lavender and two Lemon then score 12 points."),
  's' => 12,
  'f' => 'jjddll',
),
'customer_7_0000FF' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one Oolong Tea (Blue), one Berries, one Lavender and two Mint then score 8 points."),
  's' => 8,
  'f' => 'bdmm',
),
'customer_8_0000FF' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one Oolong Tea (Blue), two Milk, one Berries and one Ginger then score 9 points."),
  's' => 9,
  'f' => 'kkbg',
),
'customer_9_0000FF' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one Oolong Tea (Blue), one Milk, one Berries, one Mint, one Lemon and one Ginger then score 11 points."),
  's' => 11,
  'f' => 'kbmlg',
),
'customer_10_0000FF' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one Oolong Tea (Blue), two Sugar and one Vanilla then score 8 points."),
  's' => 8,
  'f' => 'ssv',
),
'customer_0_FF0000' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one Rooibos Tea (Red), one Honey, three Berries, one Lemon and two Lavender then score 15 points."),
  's' => 15,
  'f' => 'hbbbldd',
),
'customer_1_FF0000' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one Rooibos Tea (Red), one Sugar, one Chai and two Ginger then score 9 points."),
  's' => 9,
  'f' => 'scgg',
),
'customer_2_FF0000' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one Rooibos Tea (Red), one Sugar, one Lemon, one Mint and one Ginger then score 9 points."),
  's' => 9,
  'f' => 'slmg',
),
'customer_3_FF0000' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one Rooibos Tea (Red), one Sugar, one Vanilla and two Berries then score 9 points."),
  's' => 9,
  'f' => 'svbb',
),
'customer_4_FF0000' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one Rooibos Tea (Red), one Milk, one Sugar and one Ginger then score 7 points."),
  's' => 7,
  'f' => 'ksg',
),
'customer_5_FF0000' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one Rooibos Tea (Red), one Milk, one Vanilla, one Mint and one Jasmine then score 9 points."),
  's' => 9,
  'f' => 'kvmj',
),
'customer_6_FF0000' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one Rooibos Tea (Red) and two Berries then score 4 points."),
  's' => 4,
  'f' => 'bb',
),
'customer_7_FF0000' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one Rooibos Tea (Red), one Vanilla and two Mint then score 7 points."),
  's' => 7,
  'f' => 'vmm',
),
'customer_8_FF0000' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one Rooibos Tea (Red), two Chai, one Lavender and one Jasmine then score 9 points."),
  's' => 9,
  'f' => 'ccdj',
),
'customer_9_FF0000' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one Rooibos Tea (Red), one Chai, one Milk and one Honey then score 8 points."),
  's' => 8,
  'f' => 'ckh',
),
'customer_10_FF0000' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one Rooibos Tea (Red), one Lavender, one Jasmine and four Ginger then score 12 points."),
  's' => 12,
  'f' => 'djgggg',
),
'customer_0_FFFFFF' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one White Tea, one Honey, one Vanilla and four Berries then score 13 points."),
  's' => 13,
  'f' => 'hvbbbb',
),
'customer_1_FFFFFF' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one White Tea and two Berries then score 4 points."),
  's' => 4,
  'f' => 'bb',
),
'customer_2_FFFFFF' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one White Tea, one Sugar and three Lavender then score 9 points."),
  's' => 9,
  'f' => 'sddd',
),
'customer_3_FFFFFF' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one White Tea, one Milk and one Vanilla then score 5 points."),
  's' => 5,
  'f' => 'kv',
),
'customer_4_FFFFFF' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one White Tea, two Chai, one Lemon and one Ginger then score 9 points."),
  's' => 9,
  'f' => 'cclg',
),
'customer_5_FFFFFF' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one White Tea, one Milk and two Jasmine then score 7 points."),
  's' => 7,
  'f' => 'kjj',
),
'customer_6_FFFFFF' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one White Tea, one Berries, one Mint, one Lemon, one Ginger and one Jasmine then score 10 points."),
  's' => 10,
  'f' => 'bmlgj',
),
'customer_7_FFFFFF' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one White Tea, one Chai, one Ginger, one Jasmine and two Lavender then score 11 points."),
  's' => 11,
  'f' => 'cgjdd',
),
'customer_8_FFFFFF' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one White Tea, two Honey and four Mint then score 13 points."),
  's' => 13,
  'f' => 'hhmmmm',
),
'customer_9_FFFFFF' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one White Tea, two Sugar and one Vanilla then score 8 points."),
  's' => 8,
  'f' => 'ssv',
),
'customer_10_FFFFFF' => array(
  'type' => 'customer',
  'name' => clienttranslate("Customer Card"),
  'tooltip' => clienttranslate("Fulfill using one White Tea, three Lemon, one Lavender, one Ginger and one Jasmine then score 12 points."),
  's' => 12,
  'f' => 'llldgj',
),
        /* --- gen php end --- */
        );
