<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
* chaisji implementation : © Steve Immer <remmievets@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 * 
 * chaisji.action.php
 *
 * chaisji main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *       
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/chaisji/chaisji/myAction.html", ...)
 *
 */
  
  
  class action_chaisji extends APP_GameAction
  { 
    // Constructor: please do not modify
    public function __default()
    {
        if( self::isArg( 'notifwindow') )
        {
            $this->view = "common_notifwindow";
            $this->viewArgs['table'] = self::getArg( "table", AT_posint, true );
        }
        else
        {
            $this->view = "chaisji_chaisji";
            self::trace( "Complete reinitialization of board game" );
      }
    } 
    
    // Action from JS - play market
    public function playMarket() 
    {
      self::setAjaxMode();
      $this->game->action_playerPass();
      self::ajaxResponse( );
    }

    // Action from JS - play pantry
    public function playPantry() 
    {
      self::setAjaxMode();
      $this->game->action_playerPass();
      self::ajaxResponse( );
    }

    // Action from JS - play reserve customer
    public function playReserveCustomer() 
    {
      self::setAjaxMode();
      $this->game->action_playerPass();
      self::ajaxResponse( );
    }

    // Action from JS - play ability
    public function playAbility() 
    {
      self::setAjaxMode();
      $this->game->action_playerPass();
      self::ajaxResponse( );
    }

    // Action from JS - play order
    public function playOrder() 
    {
      self::setAjaxMode();
      $this->game->action_playerPass();
      self::ajaxResponse( );
    }

    // Action from JS - pass
    public function pass() 
    {
      self::setAjaxMode();
      $this->game->action_playerPass();
      self::ajaxResponse( );
    }

    // Action from JS - play new ability (start of new round)
    public function playNewAbility() 
    {
      self::setAjaxMode();
      $this->game->action_playerPass();
      self::ajaxResponse( );
    }

  }