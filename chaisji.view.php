<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Chai implementation : © Steve Immer <remmievets@gmail.com>
  *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * chaisji.view.php
 *
 * This is your "view" file.
 *
 * The method "build_page" below is called each time the game interface is displayed to a player, ie:
 * _ when the game starts
 * _ when a player refreshes the game page (F5)
 *
 * "build_page" method allows you to dynamically modify the HTML generated for the game interface. In
 * particular, you can set here the values of variables elements defined in chaisji_chaisji.tpl (elements
 * like {MY_VARIABLE_ELEMENT}), and insert HTML block elements (also defined in your HTML template file)
 *
 * Note: if the HTML of your game interface is always the same, you don't have to place anything here.
 *
 */
  
  require_once( APP_BASE_PATH."view/common/game.view.php" );
  
  class view_chaisji_chaisji extends game_view
  {
    function getGameName() 
    {
        return "chaisji";
    }

    function getTemplateName() 
    {
        return self::getGameName() . "_" . self::getGameName();
    }

    function processPlayerBlock($player_id, $player) 
    {
        $color = $player['player_color'];
        $name = $player['player_name'];
        $this->page->insert_block("player_board", array("COLOR" => $color, "PLAYER_NAME" => $name ));
    }

    function build_page( $viewArgs )
    {       
        // Get players & players number
        $players = $this->game->loadPlayersBasicInfos();
        $players_nbr = count( $players );

        /*********** Place your code below:  ************/
        $template = self::getTemplateName();
        $num = $players_nbr;
    
        $this->page->begin_block($template, "player_board");
        // make current player panel first
        global $g_user;
        $cplayer = $g_user->get_id();
        if (isset($players[$cplayer])) 
        { // may be not set if spectator
            $ciplayer = $players[$cplayer];
            $this->processPlayerBlock($cplayer, $ciplayer);
        }

        // Make all other player boards
        foreach ( $players as $player_id => $player ) 
        {
            if ($player_id != $cplayer)
            {
                $this->processPlayerBlock($player_id, $player);
            }
        }


        /*********** Do not change anything below this line  ************/
    }
  }
  

