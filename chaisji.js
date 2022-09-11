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
            this.inSetupMode = true;
            this.marketState = 0;
            this.pantryState = 0;
            this.pantryResetAvail = 0;
            this.customerState = 0;

            // Array for counters
            this.counters = [];

            // Helpers
            this.marketLocations = ['market_1', 'market_2', 'market_3'];
            this.pantryLocations = ['spot_1', 'spot_2', 'spot_3', 'spot_4', 'spot_5'];
        },

        /// setup:
        ///
        /// This method must set up the game user interface according to current game situation specified
        /// in parameters.
        ///
        /// The method is called each time the game interface is displayed to a player, ie:
        ///    - when the game starts
        ///    - when a player refreshes the game page (F5)
        ///
        /// "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
        setup: function( gamedatas )
        {
            console.log("Starting game setup");
            console.log(gamedatas);
            this.inSetupMode = true;
            try
            {
                // Setting up player boards
                for(let player_id in gamedatas.players)
                {
                    let color = gamedatas.players[player_id].color;

                    // Setting up players boards if needed
                    let playerBoardDiv = dojo.byId('player_board_' + player_id);
                    dojo.place('playerpanel_' + color, playerBoardDiv);

                    // Setup counter for each item by player id
                    this.counters[player_id] = [];
                    let counter_div_id = '';

                    // Add tip to player board for items - add name of items
                    for (let t of gamedatas.ordered_flavors)
                    {
                        counter_div_id = 'counter_' + t + '_' + color;
                        this.counters[player_id][t] = new ebg.counter();
                        this.counters[player_id][t].create(counter_div_id);
                        this.addTooltip(counter_div_id, gamedatas.token_types[t].name, _(''), 1000);
                    }
                    for (let t of gamedatas.ordered_pantry)
                    {
                        counter_div_id = 'counter_' + t + '_' + color;
                        this.counters[player_id][t] = new ebg.counter();
                        this.counters[player_id][t].create(counter_div_id);
                        this.addTooltip(counter_div_id, gamedatas.token_types[t].name, _(''), 1000);
                    }
                }

                // Place initial tokens
                for (let token_info of gamedatas.tokens)
                {
                    for (let t of token_info['items'])
                    {
                        this.placeToken(t, token_info['player_id'], token_info['loc'], true);
                    }
                }

                // Setup game notifications to handle (see "setupNotifications" method below)
                this.setupNotifications();
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

        /// onEnteringState:
        /// This method is called each time we are entering into a new game state.
        /// You can use this method to perform some user interface changes at this moment.
        ///
        onEnteringState: function( stateName, args )
        {
            console.log('Entering state: '+stateName);

            // Remove all selections
            dojo.query('.selected_slot').removeClass('selected_slot');
        },

        /// onLeavingState:
        /// This method is called each time we are leaving a game state.
        ///  You can use this method to perform some user interface changes at this moment.
        ///
        onLeavingState: function( stateName )
        {
        },

        /// onUpdateActionButtons:
        /// In this method you can manage "action buttons" that are displayed in the
        /// action status bar (ie: the HTML links in the status bar).
        ///
        onUpdateActionButtons: function( stateName, args )
        {
            console.log('onUpdateActionButtons: '+stateName);
            if( this.isCurrentPlayerActive() )
            {
                if (args != null)
                {
                    console.log(args);
                    // Update state information for available actions on players turn
                    if ('market_state' in args)
                    {
                        this.marketState = args.market_state;
                    }
                    if ('pantry_state' in args)
                    {
                        this.pantryState = args.pantry_state;
                    }
                    if ('pantry_reset_avail' in args)
                    {
                        this.pantryResetAvail = args.pantry_reset_avail;
                    }
                    if ('customer_state' in args)
                    {
                        this.customerState = args.customer_state;
                    }
                }

                switch( stateName )
                {
                case 'playerTurnAction':
                    // Add 3 action buttons in the action status bar:
                    this.addActionButton( 'button_market_id', _('Visit Market'), 'onStateChange' );
                    this.addActionButton( 'button_pantry_id', _('Visit Pantry'), 'onStateChange' );
                    this.addActionButton( 'button_customer_id', _('Reserve Customer and use Ability'), 'onStateChange' );
                    break;

                case 'playerMarketAction':
                    this.addActionButton( 'button_select_market_id', _('Select Tiles'), 'onSelectItems' );
                    this.addActionButton( 'button_advance_id', _('Done'), 'onStateChange' );
                    this.addActionButton( 'button_undo_id', _('Undo'), 'onStateChange' );
                    break;

                case 'playerPantryAction':
                    this.addActionButton( 'button_resetpantry_id', _('Reset pantry for 1 coin'), 'onPantryButton' );
                    this.addActionButton( 'button_bagpantry_id', _('Pull one pantry token from bag'), 'onPantryButton' );
                    this.addActionButton( 'button_select_pantry_id', _('Select Tiles'), 'onSelectItems' );
                    this.addActionButton( 'button_advance_id', _('Done'), 'onStateChange' );
                    this.addActionButton( 'button_undo_id', _('Undo'), 'onStateChange' );
                    break;

                case 'playerReserveAction':
                    this.addActionButton( 'button_select_customer_id', _('Select Tiles'), 'onSelectItems' );
                    this.addActionButton( 'button_advance_id', _('Done'), 'onStateChange' );
                    this.addActionButton( 'button_undo_id', _('Undo'), 'onStateChange' );
                    break;

                case 'playerFulfillOrder':
                    this.addActionButton( 'button_next_id', _('End Turn'), 'onStateChange' );
                    break;

                case 'playerNextRound':
                    this.addActionButton( 'button_next_id', _('Done'), 'onStateChange' );
                    break;
                }
            }
        },

        ///////////////////////////////////////////////////
        ///////////////////////////////////////////////////
        ///////////////////////////////////////////////////
        //// Utility methods

        /// Convenient method to get state name
        /// @return string
        getStateName : function()
        {
            return this.gamedatas.gamestate.name;
        },

        /// Get the translated string from a string in English
        /// @return string
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

        /// Get the first part of a token - this is the identifying type of token
        /// For example customer_1_FFFFFFFF is converted to customer
        /// @returns string
        getTokenMainType : function( token )
        {
            let tt = token.split('_');
            let tokenType = tt[0];
            return tokenType;
        },

        /// Get the last part of a token.  There are 3 parts <type>_<uniqueId>_<subtype>.
        /// @returns string
        getTokenSubType : function( token )
        {
            let tt = token.split('_');
            let tokenType = tt[2];
            return tokenType;
        },

        /// Most items are 3 parts <type>_<uniqueid>_<subtype>.  This removes the unique id
        /// @returns string
        getGenericType : function( token )
        {
            let tt = token.split('_');
            let tokenGeneric = tt[0] + '_' + tt[2];
            return tokenGeneric;
        },

        /// Get the CSS additive string from an additive "pantry_#_$add" (token name)
        ///  Convert "pantry_#_$add" to "additive_$add"
        /// @returns string
        getAdditiveIdentifer : function( token )
        {
            let selectedItem = 'additive_honey';
            for (let i=0; i<this.gamedatas.ordered_pantry.length; i++)
            {
                if (token.endsWith(this.gamedatas.ordered_pantry[i]))
                {
                    selectedItem = 'additive_' + this.gamedatas.ordered_pantry[i];
                }
            }
            return selectedItem;
        },

        /// Get the list of tokens from a location key
        /// @returns associative array from this.gamesdata.tokens
        ///      Elements have loc, items, player_id keys
        ///  if not found then return null
        ///  This may be passed the parent Node - if that happens then we need to check for players locations
        getTokenListByLocation : function( location )
        {
            // Convert pflavor_{COLOR}, padditives_{COLOR}, ptea_{COLOR} to player_{COLOR}
            if (location.startsWith('pflavor_') || location.startsWith('padditives_') || location.startsWith('ptea_'))
            {
                let tt = token.split('_');
                location = 'player_' + tt[1];
            }

            // Search through token lists
            for (let token_info of this.gamedatas.tokens)
            {
                if (token_info['loc'] == location)
                {
                    return token_info;
                }
            }
            return null;
        },

        /// Create a div object from a token name
        /// @returns tokenDiv object
        createToken : function( token )
        {
            let tokenMainType = this.getTokenMainType(token);
            let tokenClasses = '';
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
            let tokenDiv = this.format_block('jstpl_token',
                {
                    "id" : token,
                    "classes" : tokenClasses,
                });
            return tokenDiv;
        },

        /// Remove item - update gamedata and counters
        removeTokenFromGameData : function( token, location )
        {
            // Get current location by parent node
            let tokenInfo = this.getTokenListByLocation(location);

            // Remove item from gamedata
            const index = tokenInfo['items'].indexOf(token);
            if (index > -1)
            {
                tokenInfo['items'].splice(index, 1);
            }

            // Check previous location and subtract from counters if needed
            if (location.startsWith('player_'))
            {
                const player_id = tokenInfo['player_id'];
                const tokenItem = this.getTokenSubType(token);
                if (typeof this.counters[player_id][tokenItem] != 'undefined')
                {
                    this.counters[player_id][tokenItem].decValue(1);
                }
            }
        },

        /// Place a token on the board
        ///      Looks up the current location of token and if it is not on the board then a new token is created
        ///      Then place token on board in new location, if there was an old location it moves from that location to new location
        ///      If location is "destroy" then get rid of token
        /// Set noAnimation to false if you do not want the token to be animated during its creation (todo - not currently implemented)
        ///
        /// Adds tool tip information based on this.gamedatas.token_types[t].tooltip
        placeToken : function( token, player_id, location, noAnimation, debug )
        {
            try
            {
                ////@TODO - need to perform animation part of this function
                // Lookup tokens current div
                let newToken = false;
                let tokenDiv = $(token);

                // If location is "destroy" then just get rid of the item
                if (location == "destroy")
                {
                    // Verify this item is on the board already
                    if ('parentNode' in tokenDiv && tokenDiv.parentNode != null)
                    {
                        // Update game data and counters first
                        this.removeTokenFromGameData(token, tokenDiv.parentNode.id);

                        // Delete the html node
                        if (debug) console.log('Destroy' + token);
                        dojo.destroy(tokenDiv);
                    }

                    // Do nothing else
                    return;
                }

                // Determine if this item is new then create DIV
                if (tokenDiv == null)
                {
                    newToken = true;
                    tokenDiv = this.createToken(token);
                    if (debug) console.log('Create' + token);
                }
                else if ('parentNode' in tokenDiv && tokenDiv.parentNode != null)
                {
                    // Exit if token is being placed in the same location it started in
                    if (tokenDiv.parentNode.id == location)
                    {
                        // No need to change anything
                        return;
                    }
                    // Token previously existed - this will not occur during setup
                    // Remove token from old location
                    this.removeTokenFromGameData(token, tokenDiv.parentNode.id);

                    // Delete the html node
                    dojo.destroy(tokenDiv);
                }
                else
                {
                    // Not a valid case - should not hit
                    return;
                }

                // Place the token in the location (some divs have special rules)
                if (location.startsWith('player_'))
                {
                    this.placeTokenOnPlayerBoard(token, tokenDiv, this.gamedatas.players[player_id].color);

                    // Increment the number of tokens this player has on the player panel
                    const tokenItem = this.getTokenSubType(token);
                    if (typeof this.counters[player_id][tokenItem] != 'undefined')
                    {
                        this.counters[player_id][tokenItem].incValue(1);
                    }
                }
                else
                {
                    // Place the div
                    dojo.place(tokenDiv, location);
                }

                // Make token selectable
                if (newToken)
                {
                    this.connect(dojo.byId(token), 'onclick', 'onUserClickItem');
                }

                // Update gamedata if not in setup
                if (this.inSetupMode == false)
                {
                    // Get current location by parent node
                    let tokenInfo = this.getTokenListByLocation(location);
                    tokenInfo['items'].push(token);
                }

                // Add tool tip if defined for this item
                if (typeof this.gamedatas.token_types[token] != 'undefined')
                {
                    this.addTooltip(token, this.gamedatas.token_types[token].tooltip, _(''), 1000);
                }
            }
            catch (e)
            {
                console.log(token);
                console.log(player_id);
                console.log(location);
                console.log("exception thrown", e.stack)
            }
        },

        /// Place a token onto a player board - based on player color.
        ///      flavors goto - pflavor_{COLOR} (max 12)
        ///      additives goto - padditives_{COLOR} (max 6)
        ///      teas goto - ptea_{COLOR}
        ///      customers goto - pcards_{COLOR}
        placeTokenOnPlayerBoard : function( token, tokenDiv, playerColor )
        {
            let tokenMainType = this.getTokenMainType(token);
            let locationBase = '';
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

        /// Search and select matching adjacent tokens by subtype
        ///     searchForType is a string and is the subtype to search for
        ///     searchList is the list to search (3 rows and up to 6 columns)
        ///     row is the row of the item to search adjacent items
        ///     col is the column of the item to search adjacent items
        /// @note recursive function, exits when no match is found or items are already selected
        /// @return largest column index found during search
        searchAndSelectAdjacent : function ( searchForType, searchList, row, col )
        {
            let srow;
            let scol;
            let maxCol = col;

            // Search in all 4 directions
            for (let i=0; i<4; i++)
            {
                switch (i)
                {
                    case 0:
                        // UP one row from current selection
                        srow = row - 1;
                        scol = col;
                        break;
                    case 1:
                        // DOWN one row from current selection
                        srow = row + 1;
                        scol = col;
                        break;
                    case 2:
                        // LEFT one column from current selection
                        srow = row;
                        scol = col - 1;
                        break;
                    case 3:
                        // RIGHT one column from current selection
                        srow = row;
                        scol = col + 1;
                        break;
                    default:
                        // Cannot occur
                        break;
                }
                // Range check, otherwise skip
                if ((srow >= 0) &&
                    (srow < 3) &&
                    (scol >= 0) &&
                    (scol < searchList[srow].items.length))
                {
                    // subtype must match and the item cannot currently be selected, otherwise pass
                    if ((this.getTokenSubType(searchList[srow].items[scol]) == searchForType) &&
                        (dojo.hasClass(searchList[srow].items[scol], 'selected_slot') == false))
                    {
                        // Select current item
                        dojo.addClass(searchList[srow].items[scol], 'selected_slot');

                        // Update max column if current is larger
                        if (scol > maxCol)
                        {
                            maxCol = scol;
                        }

                        // Perform iterative search of new item
                        let fcol = this.searchAndSelectAdjacent(searchForType, searchList, srow, scol);

                        // Update max column if result from function is larger
                        if (fcol > maxCol)
                        {
                            maxCol = fcol;
                        }
                    }
                }
            }

            return maxCol;
        },

        /// More convenient version of ajaxcall, do not to specify game name, and any of the handlers
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

        /// @brief Send state change request to server
        onStateChange: function( event )
        {
            let id = event.currentTarget.id;
            this.original_id = id;
            dojo.stopEvent(event);
            // Pass event information to server
            this.ajaxAction("playStateChange", {main : id});
        },

        /// @brief Handle pantry special action
        onPantryButton : function ( event )
        {
            // This action covers the pull one tile from bag and pull new tiles for 1 coin cases
            let id = event.currentTarget.id;
            dojo.stopEvent(event);
            this.ajaxAction("playBagPantry", {main : id});
        },

        /// @brief Select a group of items, the game ID determines how server will respond to the selection
        onSelectItems : function ( event )
        {
            let id = event.currentTarget.id;
            dojo.stopEvent(event);

            // Build list of selected items to send
            let gameArgs = '';
            let selection = dojo.query('.selected_slot');
            for (let item of selection)
            {
                // Add to argument to server
                gameArgs += item.id + ' ';
            }

            // Call server
            this.ajaxAction("playSelection", {main : id, selection : gameArgs});
        },

        /// @brief Handles onClick action for all tokens.  Toggles selections if game state allows selection.
        onUserClickItem : function( event )
        {
            dojo.stopEvent(event);

            // Only the active player can perform selections
            if (this.isCurrentPlayerActive())
            {
                //console.log('onUserClickItem');
                //console.log(event.target.id);
                let selectedNode = dojo.query('#' + event.target.id);

                // Determine if current item is selected
                let isSelected = dojo.hasClass(event.target.id, 'selected_slot');

                switch( this.getStateName() )
                {
                case 'playerMarketAction':
                    // Check that selected item is in the correct spot on the board
                    if (selectedNode[0].parentNode.id.includes("market"))
                    {
                        // Only one item can be selected at a time, so unselect all others
                        dojo.query('.selected_slot').removeClass('selected_slot');

                        // Select new item if the item was not selected, otherwise the user was attempting to deselect
                        if (isSelected == false)
                        {
                            // Select the tile that the user selected
                            dojo.addClass(event.target.id, 'selected_slot');

                            let selectedType = this.getTokenSubType(event.target.id);
                            // Search through adjacent items and select matching tiles
                            // Build a search list
                            let searchList = [];
                            searchList.push(this.getTokenListByLocation('market_1'));
                            searchList.push(this.getTokenListByLocation('market_2'));
                            searchList.push(this.getTokenListByLocation('market_3'));
                            let searchRow = 0;
                            let searchCol = 0;

                            // Find which column has the first selected item
                            for (let i=0; i<3; i++)
                            {
                                // Search for item
                                if (searchList[i].items.indexOf(event.target.id) >= 0)
                                {
                                    // Found it
                                    searchRow = i;
                                    searchCol = searchList[i].items.indexOf(event.target.id);
                                    break;
                                }
                            }
                            let maxCol = this.searchAndSelectAdjacent(selectedType, searchList, searchRow, searchCol);
                            console.log(maxCol);
                        }
                    }
                    break;

                case 'playerPantryAction':
                    // Check that selected item is in the correct spot on the board
                    if (selectedNode[0].parentNode.id.includes("spot"))
                    {
                        dojo.toggleClass(event.target.id, 'selected_slot');
                    }
                    break;

                case 'playerReserveAction':
                    // Check that selected item is in the correct spot on the board
                    if (selectedNode[0].parentNode.id.includes("plaza_area"))
                    {
                        // Only one item can be selected at a time, so unselect all others
                        dojo.query('.selected_slot').removeClass('selected_slot');

                        // Select new item if the item was not selected, otherwise the user was attempting to deselect
                        if (isSelected == false)
                        {
                            // Select the tile that the user selected
                            dojo.addClass(event.target.id, 'selected_slot');
                        }
                    }
                    break;

                default:
                    break;
                }
            }
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
            dojo.subscribe('msgUpdate', this, 'notif_msgUpdate');
        },

        // from this point and below, you can write your game notifications handling methods

        notif_pantryUpdate: function( notif )
        {
            console.log('notif_pantryUpdate');
            console.log(notif);

            // Loop through items - destroy old pantry items and save new ones - update gamedata
            let x = 0;
            for (let l of this.pantryLocations)
            {
                // Destroy old token
                let tokenInfo = this.getTokenListByLocation(l);
                this.placeToken(tokenInfo['items'][0], null, 'destroy', true);

                // Place new token
                this.placeToken(notif.args.pantry_board[x], null, l, false);
                x++;
            }
        },

        notif_tokenUpdate: function( notif )
        {
            console.log('notif_tokenUpdate');
            console.log(notif);

            try
            {
                // Perform token adjustment requested
                let player_id = notif.args.player_id;

                for (let tokenInfo of notif.args.token)
                {
                    this.placeToken(tokenInfo.key, player_id, tokenInfo.location, false);
                }

                // Remove all selections
                dojo.query('.selected_slot').removeClass('selected_slot');

                // Update state information for available actions on players turn
                let updateTitle = false;
                if ('market_state' in notif.args)
                {
                    this.gamedatas.gamestate.args.market_state = notif.args.market_state;
                    updateTitle = true;
                }
                if ('pantry_state' in notif.args)
                {
                    this.gamedatas.gamestate.args.pantry_state = notif.args.pantry_state;
                    updateTitle = true;
                }
                if ('pantry_reset_avail' in notif.args)
                {
                    this.gamedatas.gamestate.args.pantry_reset_avail = notif.args.pantry_reset_avail;
                    updateTitle = true;
                }
                if ('customer_state' in notif.args)
                {
                    this.gamedatas.gamestate.args.customer_state = notif.args.customer_state;
                    updateTitle = true;
                }
                if (updateTitle)
                {
                    console.log('Update page title');
                    this.updatePageTitle();
                }
            }
            catch (e)
            {
                console.error(notif, "Exception thrown", e.stack);
            }
        },

        notif_msgUpdate: function( notif )
        {
            console.log('notif_msgUpdate');
        },
   });
});
