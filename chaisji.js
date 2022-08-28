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
    "dojo",
    "dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter"
],
function (dojo, declare) {
    return declare("bgagame.chaisji", ebg.core.gamegui, {
        constructor: function()
        {
            console.log('chaisji constructor');
              
            // Here, you can init the global variables of your user interface
            // Example:
            // this.myGlobalValue = 0;

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
            console.log("Starting game setup");
            console.log(gamedatas);
            try 
            {
                // Setting up player boards
                for(var player_id in gamedatas.players)
                {
                    var color = gamedatas.players[player_id].color;
                         
                    // Setting up players boards if needed
                    var playerBoardDiv = dojo.byId('player_board_' + player_id);
                    dojo.place('playerpanel_' + color, playerBoardDiv);

                    // Add tip to player board for items - add name of items
                    for (let t of gamedatas.ordered_flavors)
                    {
                        this.addTooltip('counter_' + t + '_' + color, gamedatas.token_types[t].name, _(''), 1000);
                    }
                    for (let t of gamedatas.ordered_pantry)
                    {
                        this.addTooltip('counter_' + t + '_' + color, gamedatas.token_types[t].name, _(''), 1000);
                    }
                }
            
                // Setup flavor rows
                for (let t of gamedatas.market_1)
                {
                    // Create the div
                    var my_div = this.createToken(t);
                    // Place the div
                    dojo.place(my_div, 'market_1');
                }
                for (let t of gamedatas.market_2)
                {
                    // Create the div
                    var my_div = this.createToken(t);
                    // Place the div
                    dojo.place(my_div, 'market_2');
                }
                for (let t of gamedatas.market_3)
                {
                    // Create the div
                    var my_div = this.createToken(t);
                    // Place the div
                    dojo.place(my_div, 'market_3');
                }

                // Setup pantry
                var x = 0;
                for (let t of gamedatas.pantry_board)
                {
                    // Create the div
                    var my_div = this.createToken(t);
                    // Place the div
                    dojo.place(my_div, 'spot_' + x);
                    x++;
                }

                // Setup abilities
                for (let t of gamedatas.faceup_ability)
                {
                    // Create the div for the item
                    var my_div = this.createToken(t);
                    // Place the div in the plaza
                    dojo.place(my_div, 'ability_area');
                    this.addTooltip(t, gamedatas.token_types[t].tooltip, _(''), 1000);
                }

                // Setup plaza
                for (let t of gamedatas.plaza)
                {
                    // Create the div for the item
                    var my_div = this.createToken(t);
                    // Place the div in the plaza
                    dojo.place(my_div, 'plaza_area');
                    this.addTooltip(t, gamedatas.token_types[t].tooltip, _(''), 1000);
                }

                // Setup tips
                for (let t of gamedatas.tip_jars)
                {
                    // Create the div for the item
                    var my_div = this.createToken(t);
                    // Place the div in the plaza
                    dojo.place(my_div, 'tip_area');
                }

                // Handle items on player boards
                for (let p of gamedatas.playerorder)
                {
                    for (let t of gamedatas.pboard[p])
                    {
                        // Create the div for the item
                        var my_div = this.createToken(t);
                        // Place the div on the player board
                        this.placeTokenOnPlayerBoard(t, my_div, gamedatas.players[p].color);
                        if (typeof gamedatas.token_types[t] != 'undefined') 
                        {
                            this.addTooltip(t, gamedatas.token_types[t].tooltip, _(''), 1000);
                        }
                    }
                }

                // Setup game notifications to handle (see "setupNotifications" method below)
                this.setupNotifications();

                //this.addEventToClass("additive", "onclick", "onAdditive");
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
        ///////////////////////////////////////////////////
        ///////////////////////////////////////////////////
        //// Game & client states
        
        // onEnteringState: 
        // This method is called each time we are entering into a new game state.
        // You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function( stateName, args )
        {
            console.log('Entering state: '+stateName);
            
            switch( stateName )
            {
            case 'playerPantryAction':
                // Activate all of the pantry items and make them selectable
                break;
           
            default:
                break;
            }
        },

        // onLeavingState: 
        // This method is called each time we are leaving a game state.
        //  You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function( stateName )
        {
            switch( stateName )
            {
            case 'dummmy':
                break;
            }               
        }, 

        // onUpdateActionButtons: 
        // In this method you can manage "action buttons" that are displayed in the
        // action status bar (ie: the HTML links in the status bar).
        //        
        onUpdateActionButtons: function( stateName, args )
        {
            console.log('onUpdateActionButtons: '+stateName);
                      
            if( this.isCurrentPlayerActive() )
            {            
                switch( stateName )
                {
                case 'playerTurnAction':
                    // Add 3 action buttons in the action status bar:
                    this.addActionButton( 'button_market_id', _('Visit Market'), 'onMyButtonToCall' ); 
                    this.addActionButton( 'button_pantry_id', _('Visit Pantry'), 'onMyButtonToCall' ); 
                    this.addActionButton( 'button_customer_id', _('Reserve Customer and use Ability'), 'onMyButtonToCall' ); 
                    break;

                case 'playerMarketAction':
                    this.addActionButton( 'button_advance_id', _('Done'), 'onMyButtonToCall' ); 
                    this.addActionButton( 'button_undo_id', _('Undo'), 'onMyButtonToCall' ); 
                    break;

                case 'playerPantryAction':
                    this.addActionButton( 'button_resetpantry_id', _('Reset pantry for 1 coin'), 'onPantryButton' ); 
                    this.addActionButton( 'button_bagpantry_id', _('Pull one pantry token from bag'), 'onPantryButton' ); 
                    this.addActionButton( 'button_advance_id', _('Done'), 'onMyButtonToCall' ); 
                    this.addActionButton( 'button_undo_id', _('Undo'), 'onMyButtonToCall' ); 
                    break;

                case 'playerReserveAction':
                    this.addActionButton( 'button_advance_id', _('Done'), 'onMyButtonToCall' ); 
                    this.addActionButton( 'button_undo_id', _('Undo'), 'onMyButtonToCall' ); 
                    break;

                case 'playerFulfillOrder':
                    this.addActionButton( 'button_next_id', _('End Turn'), 'onMyButtonToCall' ); 
                    break;
                
                case 'playerNextRound':
                    this.addActionButton( 'button_next_id', _('Done'), 'onMyButtonToCall' ); 
                    break;
                }
            }
        },        

        ///////////////////////////////////////////////////
        ///////////////////////////////////////////////////
        ///////////////////////////////////////////////////
        //// Utility methods
        
        // Get the translated string from a string in English
        // @return string
        getTr : function( name )
        {
            // Check for errors
            if (typeof name != 'string')
            {
                console.error("cannot translate " + name);
            }
            else
            {
                name = this.clienttranslate_string(name);
            }
            return name;
        },

        // Get the first part of a token - this is the identifying type of token
        // For example customer_1_FFFFFFFF is converted to customer
        // @returns string
        getTokenMainType : function( token ) 
        {
            var tt = token.split('_');
            var tokenType = tt[0];
            return tokenType;
        },

        // Most items are 3 parts <type>_<uniqueid>_<subtype>.  This removes the unique id
        // @returns string
        getGenericType : function( token ) 
        {
            var tt = token.split('_');
            var tokenGeneric = tt[0] + '_' + tt[2];
            return tokenGeneric;
        },

        // Get the CSS additive string from an additive "pantry_#_$add" (token name)
        //  Convert "pantry_#_$add" to "additive_$add"
        // @returns string
        getAdditiveIdentifer : function( token ) 
        {
            var selectedItem = 'additive_honey';
            for (let i=0; i<this.gamedatas.ordered_pantry.length; i++)
            {
                if (token.endsWith(this.gamedatas.ordered_pantry[i]))
                {
                    selectedItem = 'additive_' + this.gamedatas.ordered_pantry[i];
                }
            }
            return selectedItem;
        },

        // Create a div object from a token name
        // @returns tokenDiv object
        createToken : function( token ) 
        {
            var tokenMainType = this.getTokenMainType(token);
            var tokenClasses = '';
            switch (tokenMainType) 
            {
                case 'card':
                    tokenClasses = 'shadow abilitycard ' + token;
                    break;
                case 'customer':
                    tokenClasses = 'shadow card ' + token;
                    break;
                case 'flavor':
                    tokenClasses = 'shadow flavor ' + this.getGenericType(token);
                    break;
                case 'pantry':
                    tokenClasses = 'shadow additive ' + this.getAdditiveIdentifer(token);
                    break;
                case 'tea':
                    tokenClasses = 'tea ' + this.getGenericType(token);
                    break;
                case 'tip':
                    tokenClasses = 'tipjar';
                    break;
                default:
                    break;
            }
            var tokenDiv = this.format_block('jstpl_token', 
                {
                    "id" : token,
                    "classes" : tokenClasses,
                });
            return tokenDiv;
        },

        // Place a token onto a player board - based on player color.
        //      flavors goto - pflavor_{COLOR} (max 12)
        //      additives goto - padditives_{COLOR} (max 6)
        //      teas goto - ptea_{COLOR}
        //      customers goto - pcards_{COLOR}
        placeTokenOnPlayerBoard : function( token, tokenDiv, playerColor )
        {
            var tokenMainType = this.getTokenMainType(token);
            var locationBase = '';
            switch (tokenMainType)
            {
                case 'customer':
                    locationBase = 'pcards_' + playerColor.toString();
                    break;
                case 'flavor':
                    locationBase = 'pflavor_' + playerColor.toString();
                    break;
                case 'pantry':
                    locationBase = 'padditives_' + playerColor.toString();
                    break;
                case 'tea':
                    locationBase = 'ptea_' + playerColor.toString();
                    break;
                default:
                    break;
            }
            dojo.place(tokenDiv, locationBase);
        },

        // More convenient version of ajaxcall, do not to specify game name, and any of the handlers
        ajaxAction : function( action, args ) 
        {
            console.log("ajax action " + action);
            if (!args) 
            {
                args = [];
            }
            args.lock = true;

            if (this.checkAction(action)) 
            {
                this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/" + action + ".html", args, this, function( result ) {}, function( is_error ) {});
            }
        },

        ///////////////////////////////////////////////////
        ///////////////////////////////////////////////////
        ///////////////////////////////////////////////////
        //// Player's action

        // Handle main button presses
        onMyButtonToCall: function( event )
        {
            var id = event.currentTarget.id;
            this.original_id = id;
            dojo.stopEvent(event);
            // Pass event information to server
            this.ajaxAction("playStateChange",  {main : id});
        },

        onFlavor : function( event ) 
        {
            console.log('onFlavor');
            this.ajaxAction("playAbility", {});
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

        onPantryButton : function ( event )
        {
            // This action covers the pull one tile from bag and pull new tiles for 1 coin cases
            var id = event.currentTarget.id;
            dojo.stopEvent(event);
            this.ajaxAction("playBagPantry",  {main : id});
        },

        onAdditive : function( event ) 
        {
            console.log('onAdditive');
            //this.ajaxAction("playAbility", {});
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
        
        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:
            
            In this method, you associate each of your game notifications with your local method to handle it.
            
            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your chaisji.game.php file.
        
            // Example 1: standard notification handling
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            
            // Example 2: standard notification handling + tell the user interface to wait
            //            during 3 seconds after calling the method in order to let the players
            //            see what is happening in the game.
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            // this.notifqueue.setSynchronous( 'cardPlayed', 3000 );
            // 
        */
        setupNotifications: function()
        {
            console.log( 'notifications subscriptions setup' );
            
            // here, associate your game notifications with local methods
            dojo.subscribe('pantryUpdate', this, 'notif_pantryUpdate');
            dojo.subscribe('tokenUpdate', this, 'notif_tokenUpdate');
        },  
        
        // from this point and below, you can write your game notifications handling methods
        
        notif_pantryUpdate: function( notif )
        {
            console.log('notif_pantryUpdate');
            console.log(notif);

            // Animate and destroy old pantry items
            for (let t of dojo.query(".spot > *"))
            {
                // Place the div
                this.slideToObjectAndDestroy(t, 'pboard_space', 1000, 0 );
            }

            // Save new pantry items
            this.gamedatas.pantry_board = notif.args.pantry_board;

            // Display new items
            var x = 0;
            for (let t of this.gamedatas.pantry_board)
            {
                // Create the div
                var my_div = this.createToken(t);

                // Slide from somewhere to final location
                this.slideTemporaryObject(my_div, 'gameboard', 'pboard_space', 'spot_' + x).play();

                // Place the div
                dojo.place(my_div, 'spot_' + x);
                x++;
            }
        },

        notif_tokenUpdate: function( notif )
        {
            console.log('notif_tokenUpdate');
            console.log(notif);

            console.log(notif.args.player_id);
            console.log(notif.args.token[0].key);
            console.log(notif.args.token[0].location);
            console.log(notif.args.token[0].state);

            // Update gamedata
            this.gamedatas.pboard[notif.args.player_id].push(notif.args.token[0].key);

            // Create new token for this item
            var my_div = this.createToken(notif.args.token[0].key);

            // Place the div on the player board
            this.placeTokenOnPlayerBoard(notif.args.token[0].key, my_div, this.gamedatas.players[notif.args.player_id].color);
        },
   });             
});
