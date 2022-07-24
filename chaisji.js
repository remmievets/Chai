/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Chai implementation : © Steve Immer <remmievets@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * chaisji.js
 *
 * chaisji user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter",
    "ebg/stock"
],
function (dojo, declare) {
    return declare("bgagame.chaisji", ebg.core.gamegui, {
        constructor: function()
        {
            console.log('chaisji constructor');
              
            // Here, you can init the global variables of your user interface
            // Example:
            // this.myGlobalValue = 0;
            console.log("Chai constructor");
            this.flavorwidth = 75;
            this.flavorheight = 75;

            this.flavorConstant = null;
            this.pantryConstant = null;

            // Array of current dojo connections (needed for method addEventToClass)
            this.connections = [];
        },
        
        /*
            setup:
            
            This method must set up the game user interface according to current game situation specified
            in parameters.
            
            The method is called each time the game interface is displayed to a player, ie:
            _ when the game starts
            _ when a player refreshes the game page (F5)
            
            "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
        */
        
        setup: function( gamedatas )
        {
            console.log( "Starting game setup" );
            console.log(gamedatas);
            try 
            {
                // Setting up player boards
                for( var player_id in gamedatas.players )
                {
                    var player = gamedatas.players[player_id];
                         
                    // TODO: Setting up players boards if needed
                }
            
                // TODO: Set up your game interface here, according to "gamedatas"
                // Save game constants from gamedatas
                this.flavorConstant = gamedatas.ordered_flavors;
                this.pantryConstant = gamedatas.ordered_pantry;

                // Setup possible flavors for row 1
                this.flavorRow1 = new ebg.stock();
                this.flavorRow1.create(this, $('row_1'), this.flavorwidth, this.flavorheight);
            
                this.flavorRow1.image_items_per_row = 8;
                this.flavorRow1.setSelectionAppearance('class');
                this.flavorRow1.selectionClass = 'stockitem_selected';
                dojo.connect( this.flavorRow1, "onChangeSelection", this, "onMarketClick" );

                for (var flav = 0; flav < this.flavorRow1.image_items_per_row; flav++) 
                {
                    this.flavorRow1.addItemType(flav, 0, g_gamethemeurl + 'img/flavors.png', flav);
                }

                // Setup possible flavors for row 2
                this.flavorRow2 = new ebg.stock();
                this.flavorRow2.create(this, $('row_2'), this.flavorwidth, this.flavorheight);
            
                this.flavorRow2.image_items_per_row = 8;
                this.flavorRow2.setSelectionAppearance('class');
                this.flavorRow2.selectionClass = 'stockitem_selected';
                dojo.connect( this.flavorRow2, "onChangeSelection", this, "onMarketClick" );

                for (var flav = 0; flav < this.flavorRow2.image_items_per_row; flav++) 
                {
                    this.flavorRow2.addItemType(flav, 0, g_gamethemeurl + 'img/flavors.png', flav);
                }

                // Setup possible flavors for row 3
                this.flavorRow3 = new ebg.stock();
                this.flavorRow3.create(this, $('row_3'), this.flavorwidth, this.flavorheight);
            
                this.flavorRow3.image_items_per_row = 8;
                this.flavorRow3.setSelectionAppearance('class');
                this.flavorRow3.selectionClass = 'stockitem_selected';
                dojo.connect( this.flavorRow3, "onChangeSelection", this, "onMarketClick" );

                for (var flav = 0; flav < this.flavorRow3.image_items_per_row; flav++) 
                {
                    this.flavorRow3.addItemType(flav, 0, g_gamethemeurl + 'img/flavors.png', flav);
                }

                // Setup flavor rows
                console.log("Setup Market");
                for (let x in gamedatas.market_1)
                {
                    let t = this.getStockIdentifer(gamedatas.market_1[x])
                    this.flavorRow1.addToStock(t);
                }
                for (let x in gamedatas.market_2)
                {
                    let t = this.getStockIdentifer(gamedatas.market_2[x])
                    this.flavorRow2.addToStock(t);
                }
                for (let x in gamedatas.market_3)
                {
                    let t = this.getStockIdentifer(gamedatas.market_3[x])
                    this.flavorRow3.addToStock(t);
                }

                // Setup pantry
                console.log("Start Pantry");
                var x = 0;
                var pantry_div;
                for (let p in gamedatas.pantry_board)
                {
                    // Create the div
                    var pantry_div = this.format_block('jstpl_pantry', 
                        {additive_type : this.getAdditiveIdentifer(gamedatas.pantry_board[p]),
                         x : x});
                    // Place the div
                    dojo.place(pantry_div, 'spot_' + x);
                    x++;
                }

                //dojo.query('.additive').connect('onclick', this, 'onFlavorSelectionChanged');
                //dojo.query('.additive').connect('click', this, 'onFlavorSelectionChanged');
                //dojo.query('.additive').connect('mouseover', this, 'onFlavorSelectionChanged');

                // Setup game notifications to handle (see "setupNotifications" method below)
                this.setupNotifications();

                this.addEventToClass("additive", "onclick", "onAdditive");
            }
            catch (e) 
            {
                console.error("Exception thrown", e.stack);
                this.showMessage("Setup Error: Please raise a bug" + "\n" + e, "error");
            }
            this.inSetupMode = false;
            console.log( "Ending game setup" );
        },
       

        ///////////////////////////////////////////////////
        //// Game & client states
        
        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function( stateName, args )
        {
            console.log( 'Entering state: '+stateName );
            
            switch( stateName )
            {
            
            /* Example:
            
            case 'myGameState':
            
                // Show some HTML block at this game state
                dojo.style( 'my_html_block_id', 'display', 'block' );
                
                break;
           */
           
           
            case 'dummmy':
                break;
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function( stateName )
        {
            console.log( 'Leaving state: '+stateName );
            
            switch( stateName )
            {
            
            /* Example:
            
            case 'myGameState':
            
                // Hide the HTML block we are displaying only during this game state
                dojo.style( 'my_html_block_id', 'display', 'none' );
                
                break;
           */
           
           
            case 'dummmy':
                break;
            }               
        }, 

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //        
        onUpdateActionButtons: function( stateName, args )
        {
            console.log( 'onUpdateActionButtons: '+stateName );
                      
            if( this.isCurrentPlayerActive() )
            {            
                switch( stateName )
                {
                 case 'playerTurnAction':
                    
                    // Add 3 action buttons in the action status bar:
                    
                    this.addActionButton( 'button_1_id', _('Visit the Market'), 'onMyMethodToCall' ); 
                    this.addActionButton( 'button_2_id', _('Visit the Pantry'), 'onMyMethodToCall' ); 
                    this.addActionButton( 'button_3_id', _('Reserve Customer'), 'onMyMethodToCall' ); 
                    break;
                }
            }
        },        

        ///////////////////////////////////////////////////
        //// Utility methods
        
        // Finds the integer index into a flavor item from a string
        getStockIdentifer : function( flavorItem ) 
        {
            var selectedItem = 0;
            for (let i=0; i<this.flavorConstant.length; i++)
            {
                if (flavorItem.endsWith(this.flavorConstant[i]))
                {
                    selectedItem = i;
                }
            }
            return selectedItem;
        },

        // Get the CSS additive string from an additive "pantry_#_$add"
        //  Convert "pantry_#_$add" to "additive_$add"
        getAdditiveIdentifer : function( additiveItem ) 
        {
            var selectedItem = 'additive_honey';
            for (let i=0; i<this.pantryConstant.length; i++)
            {
                if (additiveItem.endsWith(this.pantryConstant[i]))
                {
                    selectedItem = 'additive_' + this.pantryConstant[i];
                }
            }
            return selectedItem;
        },


        ///////////////////////////////////////////////////
        //// Player's action
        onMarketClick: function(control_name, item_id)
        {
            console.log('onMarketClick ' + control_name + ' ' + item_id);
        },

        onMyMethodToCall: function(control_name, item_id)
        {
            console.log('onMyMethodToCall' + control_name + ' ' + item_id);
        },

        onFlavor : function(event) 
        {
            console.log('onFlavor');
            /*
            var items = this.flavorRow1.getSelectedItems();

            if (items.length > 0) {
                if (this.checkAction('playCard', true)) {
                    // Can play a card

                    var card_id = items[0].id;
                    console.log("on playCard "+card_id);

                    this.flavorRow1.unselectAll();
                } else if (this.checkAction('giveCards')) {
                    // Can give cards => let the player select some cards
                } else {
                    this.flavorRow1.unselectAll();
                }
            }
            */
        },

        onAdditive : function(event) 
        {
            console.log('onAdditive');
            /*
            var items = this.flavorRow1.getSelectedItems();

            if (items.length > 0) {
                if (this.checkAction('playCard', true)) {
                    // Can play a card

                    var card_id = items[0].id;
                    console.log("on playCard "+card_id);

                    this.flavorRow1.unselectAll();
                } else if (this.checkAction('giveCards')) {
                    // Can give cards => let the player select some cards
                } else {
                    this.flavorRow1.unselectAll();
                }
            }
            */
        },
        /*
        
            Here, you are defining methods to handle player's action (ex: results of mouse click on 
            game objects).
            
            Most of the time, these methods:
            _ check the action is possible at this game state.
            _ make a call to the game server
        
        */
        
        /* Example:
        
        onMyMethodToCall1: function( evt )
        {
            console.log( 'onMyMethodToCall1' );
            
            // Preventing default browser reaction
            dojo.stopEvent( evt );

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if( ! this.checkAction( 'myAction' ) )
            {   return; }

            this.ajaxcall( "/chaisji/chaisji/myAction.html", { 
                                                                    lock: true, 
                                                                    myArgument1: arg1, 
                                                                    myArgument2: arg2,
                                                                    ...
                                                                 }, 
                         this, function( result ) {
                            
                            // What to do after the server call if it succeeded
                            // (most of the time: nothing)
                            
                         }, function( is_error) {

                            // What to do after the server call in anyway (success or failure)
                            // (most of the time: nothing)

                         } );        
        },        
        
        */

        
        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:
            
            In this method, you associate each of your game notifications with your local method to handle it.
            
            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your chaisji.game.php file.
        
        */
        setupNotifications: function()
        {
            console.log( 'notifications subscriptions setup' );
            
            // TODO: here, associate your game notifications with local methods
            
            // Example 1: standard notification handling
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            
            // Example 2: standard notification handling + tell the user interface to wait
            //            during 3 seconds after calling the method in order to let the players
            //            see what is happening in the game.
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            // this.notifqueue.setSynchronous( 'cardPlayed', 3000 );
            // 
        },  
        
        // TODO: from this point and below, you can write your game notifications handling methods
        
        /*
        Example:
        
        notif_cardPlayed: function( notif )
        {
            console.log( 'notif_cardPlayed' );
            console.log( notif );
            
            // Note: notif.args contains the arguments specified during you "notifyAllPlayers" / "notifyPlayer" PHP call
            
            // TODO: play the card in the user interface.
        },    
        
        */
   });             
});
