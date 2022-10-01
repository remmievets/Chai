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
  * chaisji.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */


require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );

/// This is a generic class to manage game pieces.
///
/// On DB side this is based on a standard table with the following fields:
/// token_key (string), token_location (string), token_state (int)
class Tokens extends APP_GameClass
{
    var $table;
    var $autoreshuffle = false; // If true, a new deck is automatically formed with a reshuffled discard as soon at is needed
    var $autoreshuffle_trigger = null; // Callback to a method called when an autoreshuffle occurs
    // autoreshuffle_trigger = array( 'obj' => object, 'method' => method_name )
    var $autoreshuffle_custom = array ();
    private $custom_fields;
    private $g_index;

    /// If defined, tell the name of the deck and what is the corresponding discard (ex : "mydeck" => "mydiscard")
    function __construct()
    {
        $this->table = 'token';
        $this->custom_fields = array ();
        $this->g_index = array ();
    }

    /// MUST be called before any other method if db is not called 'token'
    function init($table)
    {
        $this->table = $table;
    }

    // This inserts new records in the database. Generically speaking you should only be calling during setup with some
    // rare exceptions.
    //
    // Tokens are added into location specified, (default is 'deck')
    //
    // Tokens is an array with at least the following fields:
    // array(
    //      array(                              // This is my first token
    //          "key" => <unique key>           // This unique alphanum and underscore key, use {INDEX} to replace with index if 'nbr' > 1, i..e "meeple_{INDEX}_red"
    //          "nbr" => <nbr>                  // Number of tokens with this key, default is 1. If nbr >1 and key does not have {INDEX} it will throw an exception
    //          "location" => <location>        // Optional argument specifies the location, alphanum and underscore
    //          "state" => <state>              // Optional argument specifies integer state, if not specified and $token_state_global is not specified auto-increment is used
    function createTokens($tokens, $location_global, $token_state_global = null)
    {
        if ($location_global)
            $next_pos = $this->getExtremePosition(true, $location_global) + 1;
        else
            $next_pos = 0;
        $values = array ();
        $keys = array ();
        foreach ( $tokens as $token_info ) {
            if (isset($token_info ['nbr']))
                $n = $token_info ['nbr'];
            else
                $n = 1;
            if (isset($token_info ['nbr_start']))
                $start = $token_info ['nbr_start'];
            else
                $start = 0;
            for ($i = $start; $i < $n + $start; $i ++) {
                if (isset($token_info ['location']))
                    $location = $token_info ['location'];
                else
                    $location = $location_global;
                if (isset($token_info ['state']))
                    $token_state = ( int ) ($token_info ['state']);
                else
                    $token_state = $token_state_global;
                if ($token_state === null) {
                    if ($location == $location_global) {
                        $token_state = $next_pos;
                        $next_pos ++;
                    } else {
                        $token_state = 0;
                    }
                }
                $key = $token_info ['key'];
                if ($key == null)
                    throw new feException("createTokens: key cannot be null");
                $key = $this->varsub($key, array_merge($token_info, array ('INDEX' => $i )), true);
                if ($location == null)
                    throw new feException("createTokens: location cannot be null (set per token location or location_global");
                self::checkLocation($location);
                self::checkKey($key);
                $values [] = "( '$key', '$location', '$token_state' )";
                $keys [] = $key;
            }
        }
        $sql = "INSERT INTO " . $this->table . " (token_key,token_location,token_state)";
        $sql .= " VALUES " . implode(",", $values);
        $this->DbQuery($sql);
        return $keys;
    }

    function createTokensPack($key, $location, $nbr = 1, $nbr_start = 0, $iterArr = null)
    {
        if ($iterArr == null)
            $iterArr = array ('' );
        if (! is_array($iterArr))
            throw new feException("iterArr must be an array");
        if (count($iterArr) == 0)
            $iterArr = array ('' );
        $tokenSpec = array ('key' => $key,'location' => $location,'nbr' => $nbr,'nbr_start' => $nbr_start );
        $tokens = array ();
        foreach ( $iterArr as $iterKey ) {
            $newspec = array ();
            foreach ( $tokenSpec as $tokenSpecKey => $value ) {
                $value = $this->varsub($value, array ('TYPE' => $iterKey ));
                $newspec [$tokenSpecKey] = $value;
            }
            $tokens [] = $newspec;
        }
        return $this->createTokens($tokens, null);
    }

    /// Get max on min state on the specific location
    function getExtremePosition($getMax, $location)
    {
        self::checkLocation($location);
        if ($getMax)
            $sql = "SELECT MAX( token_state ) res ";
        else
            $sql = "SELECT MIN( token_state ) res ";
        $sql .= "FROM " . $this->table;
        $sql .= " WHERE token_location='" . addslashes($location) . "' ";
        $dbres = self::DbQuery($sql);
        $row = mysql_fetch_assoc($dbres);
        if ($row)
            return $row ['res'];
        else
            return 0;
    }

    /// Shuffle token of a specified location, result of the operation will changes state of the token to be a position after shuffling
    function shuffle($location)
    {
        self::checkLocation($location);
        $token_keys = self::getObjectListFromDB("SELECT token_key FROM " . $this->table . " WHERE token_location='$location'", true);
        shuffle($token_keys);
        $n = 0;
        foreach ( $token_keys as $token_key ) {
            self::DbQuery("UPDATE " . $this->table . " SET token_state='$n' WHERE token_key='$token_key'");
            $n ++;
        }
    }

    /// Pick the first "$nbr" cards on top of specified deck and place it in target location
    /// Return cards infos or void array if no card in the specified location
    function pickTokensForLocation($nbr, $from_location, $to_location, $state = 0, $no_deck_reform = false)
    {
        $tokens = self::getTokensOnTop($nbr, $from_location);
        $tokens_ids = array ();
        foreach ( $tokens as $i => $card )
        {
            $tokens_ids [] = $card ['key'];
            $tokens [$i] ['location'] = $to_location;
            $tokens [$i] ['state'] = $state;
        }
        $sql = "UPDATE " . $this->table . " SET token_location='" . addslashes($to_location) . "', token_state='$state' ";
        $sql .= "WHERE token_key IN ('" . implode("','", $tokens_ids) . "') ";
        self::DbQuery($sql);
        if (isset($this->autoreshuffle_custom [$from_location]) && (count($tokens) < $nbr) && $this->autoreshuffle && ! $no_deck_reform)
        {
            // No more cards in deck & reshuffle is active => form another deck
            $nbr_token_missing = $nbr - count($tokens);
            self::reformDeckFromDiscard($from_location);
            $newcards = self::pickCardsForLocation($nbr_token_missing, $from_location, $to_location, $state, true); // Note: block anothr deck reform
            foreach ( $newcards as $card )
            {
                $tokens [] = $card;
            }
        }
        return $tokens;
    }

     ///@brief Return token on top of this location, top defined as item with higher state value
    function getTokenOnTop($location)
    {
        $result_arr = $this->getTokensOnTop(1, $location);
        if (count($result_arr) > 0)
            return $result_arr [0];
        return null;
    }

    ///@brief Return "$nbr" tokens on top of this location, top defined as item with higher state value
    function getTokensOnTop($nbr, $location)
    {
        self::checkLocation($location);
        self::checkPosInt($nbr);
        $result = array ();
        $sql = $this->getSelectQuery();
        $sql .= " WHERE token_location='$location'";
        $sql .= " ORDER BY token_state DESC";
        $sql .= " LIMIT $nbr";
        $dbres = self::DbQuery($sql);
        while ( $row = mysql_fetch_assoc($dbres) ) {
            $result [] = $row;
        }
        return $result;
    }

    function reformDeckFromDiscard($from_location)
    {
        self::checkLocation($from_location);
        if (isset($this->autoreshuffle_custom[$from_location]))
            $discard_location = $this->autoreshuffle_custom[$from_location];
        else
            throw new feException("reformDeckFromDiscard: Unknown discard location for $from_location !");
        self::checkLocation($discard_location);
        self::moveAllTokensInLocation($discard_location, $from_location);
        self::shuffle($from_location);
        if ($this->autoreshuffle_trigger)
        {
            $obj = $this->autoreshuffle_trigger ['obj'];
            $method = $this->autoreshuffle_trigger ['method'];
            $obj->$method($from_location);
        }
    }

    /// @brief Move a single token to specific location
    function moveToken($token_key, $location, $state = 0)
    {
        self::checkLocation($location);
        self::checkState($state);
        self::checkKey($token_key);
        $sql = "UPDATE " . $this->table;
        $sql .= " SET token_location='$location', token_state='$state'";
        $sql .= " WHERE token_key='$token_key'";
        self::DbQuery($sql);
    }

    /// @brief Update the state of a token
    function updateStateToken($token_key, $state)
    {
        self::checkState($state);
        self::checkKey($token_key);
        $sql = "UPDATE " . $this->table;
        $sql .= " SET token_state='$state'";
        $sql .= " WHERE token_key='$token_key'";
        self::DbQuery($sql);
    }

    /// @brief Move list of tokens to specific location
    function moveTokens($tokens, $location, $state = 0)
    {
        self::checkLocation($location);
        self::checkState($state);
        self::checkTokenKeyArray($tokens);
        $sql = "UPDATE " . $this->table;
        $sql .= " SET token_location='$location', token_state='$state'";
        $sql .= " WHERE token_key IN ('" . implode("','", $tokens) . "')";
        self::DbQuery($sql);
    }

    /// Move a card to a specific location where card are ordered. If location_arg place is already taken, increment
    /// all tokens after location_arg in order to insert new card at this precise location
    function insertToken($token_key, $location, $state = 0)
    {
        self::checkLocation($location);
        self::checkState($state);
        $sql = "UPDATE " . $this->table;
        $sql .= " SET token_state=token_state+1";
        $sql .= " WHERE token_location='$location' ";
        $sql .= " AND token_state>=$state";
        self::DbQuery($sql);
        self::moveToken($token_key, $location, $state);
    }

    function insertTokenOnExtremePosition($token_key, $location, $bOnTop)
    {
        $extreme_pos = self::getExtremePosition($bOnTop, $location);
        if ($bOnTop)
            self::insertToken($token_key, $location, $extreme_pos + 1);
        else
            self::insertToken($token_key, $location, $extreme_pos - 1);
    }

    /// Move all tokens from a location to another
    /// !!! state is reset to 0 or specified value !!!
    /// if "from_location" and "from_state" are null: move ALL cards to specific location
    function moveAllTokensInLocation($from_location, $to_location, $from_state = null, $to_state = 0)
    {
        if ($from_location != null)
            self::checkLocation($from_location);
        self::checkLocation($to_location);
        $sql = "UPDATE " . $this->table . " ";
        $sql .= "SET token_location='$to_location', token_state='$to_state' ";
        if ($from_location !== null) {
            $sql .= "WHERE token_location='" . addslashes($from_location) . "' ";
            if ($from_state !== null)
                $sql .= "AND token_state='$from_state' ";
        }
        self::DbQuery($sql);
    }

    /// @brief Move all tokens from a location to another location arg stays with the same value
    function moveAllTokensInLocationKeepOrder($from_location, $to_location)
    {
        self::checkLocation($from_location);
        self::checkLocation($to_location);
        $sql = "UPDATE " . $this->table;
        $sql .= " SET token_location='$to_location'";
        $sql .= " WHERE token_location='$from_location'";
        self::DbQuery($sql);
    }

    /// Return all tokens in specific location
    /// note: if "order by" is used, result object is NOT indexed by ids
    function getTokensInLocation($location, $state = null, $order_by = null)
    {
        return $this->getTokensOfTypeInLocation(null, $location, $state, $order_by);
    }

    /// Get tokens of a specific type in a specific location, since there is no field for type we use like expression on
    /// key
    ///
    /// @param string $type
    /// @param string $location
    /// @param int $state
    ///
    /// @return array mixed
    function getTokensOfTypeInLocation($type, $location = null, $state = null, $order_by = null)
    {
        $sql = $this->getSelectQuery();
        $sql .= " WHERE true ";
        if ($type !== null) {
            if (strpos($type, "%") === false) {
                $type .= "%";
            }
            self::checkType($type);
            $sql .= " AND token_key LIKE '$type'";
        }
        if ($location !== null) {
            self::checkLocation($location, true);
            $like = "LIKE";
            if (strpos($location, "%") === false) {
                $like = "=";
            }
            $sql .= " AND token_location $like '$location' ";
        }
        if ($state !== null) {
            self::checkState($state, true);
            $sql .= " AND token_state = '$state'";
        }
        if ($order_by !== null)
            $sql .= " ORDER BY $order_by";
        $dbres = self::DbQuery($sql);
        $result = array ();
        $i = 0;
        while ( $row = mysql_fetch_assoc($dbres) ) {
            if ($order_by !== null) {
                $result [$i] = $row;
            } else {
                $result [$row ['key']] = $row;
            }
            $i ++;
        }
        return $result;
    }

    /// @brief Get specific token info based on token_key
    /// @return Object with token information {key, location, state}
    function getTokenInfo($token_key)
    {
        self::checkKey($token_key);
        $sql = $this->getSelectQuery();
        $sql .= " WHERE token_key='$token_key' ";
        $dbres = self::DbQuery($sql);
        return mysql_fetch_assoc($dbres);
    }

    /// @brief Get specific tokens info indexed by token_key
    /// @return associate array of token information {key, location, state}
    function getTokensInfo($tokens_array)
    {
        self::checkTokenKeyArray($tokens_array);
        if (count($tokens_array) == 0)
            return array ();
        $sql = $this->getSelectQuery();
        $sql .= " WHERE token_key IN ('" . implode("','", $tokens_array) . "') ";
        $dbres = self::DbQuery($sql);
        $result = array ();
        while ( $row = mysql_fetch_assoc($dbres) ) {
            $result [$row ['key']] = $row;
        }
        if (count($result) != count($tokens_array)) {
            self::error("getTokens: some cards have not been found:");
            self::error("requested: " . implode(",", $tokens_array));
            self::error("received: " . implode(",", array_keys($result)));
            throw new feException("getTokens: Some cards have not been found !");
        }
        return $result;
    }

    /// @brief Returns the number of tokens at a location.  If the location contains % then LIKE is used instead of exact location
    /// @param location
    /// @param state (optional)
    /// @returns integer value
    function countTokensInLocation($location, $state = null)
    {
        self::checkLocation($location, true);
        self::checkState($state, true);
        $like = "LIKE";
        if (strpos($location, "%") === false) {
            $like = "=";
        }
        $sql = "SELECT COUNT( token_key ) cnt FROM " . $this->table;
        $sql .= " WHERE token_location $like '$location' ";
        if ($state !== null)
            $sql .= "AND token_state='$state' ";
        $dbres = self::DbQuery($sql);
        if ($row = mysql_fetch_assoc($dbres))
            return $row ['cnt'];
        else
            return 0;
    }

    /// @brief Returns the number of tokens at a location where token_key starts with a string
    /// @param location
    /// @param tokenSearchKey This should be a search string containing %
    /// @returns integer value
    function countTokensTypeInLocation($location, $tokenSearchKey)
    {
        self::checkLocation($location, true);
        $like = "LIKE";
        if (strpos($location, "%") === false) {
            $like = "=";
        }
        $sql = "SELECT COUNT( token_key ) cnt FROM " . $this->table;
        $sql .= " WHERE token_location $like '$location' ";
        if ($tokenSearchKey !== null)
            $sql .= "AND token_key LIKE '$tokenSearchKey' ";
        $dbres = self::DbQuery($sql);
        if ($row = mysql_fetch_assoc($dbres))
            return $row ['cnt'];
        else
            return 0;
    }

    /// Return an array "location" => number of cards
    function countTokensInLocations()
    {
        $result = array ();
        $sql = "SELECT token_location, COUNT( token_key ) cnt FROM " . $this->table . " GROUP BY token_location ";
        $dbres = self::DbQuery($sql);
        while ( $row = mysql_fetch_assoc($dbres) ) {
            $result [$row ['token_location']] = $row ['cnt'];
        }
        return $result;
    }

    /// Return an array "state" => number of tokens (for this location)
    function countTokensByState($location)
    {
        self::checkLocation($location);
        $result = array ();
        $sql = "SELECT token_state, COUNT( token_key ) cnt FROM " . $this->table . " ";
        $sql .= "WHERE token_location='$location' ";
        $sql .= "GROUP BY token_state ";
        $dbres = self::DbQuery($sql);
        while ( $row = mysql_fetch_assoc($dbres) ) {
            $result [$row ['token_state']] = $row ['cnt'];
        }
        return $result;
    }

    function varsub($line, $keymap, $usegindex = false)
    {
        if ($line === null)
            throw new feException("varsub: line cannot be null");
        if (strpos($line, "{") !== false) {
            foreach ( $keymap as $key => $value ) {
                if (strpos($line, "{$key}") !== false) {
                    $line = preg_replace("/\{$key\}/", $value, $line);
                }
            }
            if ($usegindex)
                foreach ( $this->g_index as $key => $value ) {
                    if (strpos($line, "{$key}") !== false) {
                        $value ++;
                        $line = preg_replace("/\{$key\}/", $value, $line);
                        $this->g_index [$key] = $value;
                    }
                }
        }
        return $line;
    }

    final function checkLocation($location, $like = false)
    {
        if ($location == null)
            throw new feException("location cannot be null");
        $extra = "";
        if ($like)
            $extra = "%";
        if (preg_match("/^[A-Za-z_0-9${extra}-]+$/", $location) == 0) {
            throw new feException("location must be  alphanum and underscore non empty string");
        }
    }

    final function checkState($state, $canBeNull = false)
    {
        if ($state === null && $canBeNull == false)
            throw new feException("state cannot be null");
        if ($state !== null && preg_match("/^-*[0-9]+$/", $state) == 0) {
            throw new feException("state must be integer number");
        }
    }

    final function checkTokenKeyArray($arr)
    {
        if ($arr == null)
            throw new feException("tokens cannot be null");
        if (! is_array($arr))
            throw new feException("tokens must be an array");
        foreach ( $arr as $key ) {
            $this->checkKey($key);
        }
    }

    final function checkKey($key, $like = false)
    {
        if ($key == null)
            throw new feException("key cannot be null");
        $extra = "";
        if ($like)
            $extra = "%";
        if (preg_match("/^[A-Za-z_0-9${extra}]+$/", $key) == 0) {
            throw new feException("key must be alphanum and underscore non empty string '$key'");
        }
    }

    final function checkType($key)
    {
        if ($key == null)
            throw new feException("type cannot be null");
        $this->checkKey($key, true);
    }

    final function checkPosInt($key)
    {
        if ($key && preg_match("/^[0-9]+$/", $key) == 0) {
            throw new feException("must be integer number");
        }
    }

    final function getSelectQuery()
    {
        $sql = "SELECT token_key AS \"key\", token_location AS \"location\", token_state AS \"state\"";
        if (count($this->custom_fields)) {
            $sql .= ", ";
            $sql .= implode(', ', $this->custom_fields);
        }
        $sql .= " FROM " . $this->table;
        return $sql;
    }

    function setCustomFields($fields_array)
    {
        $this->checkTokenKeyArray($fields_array);
        $this->custom_fields = $fields_array;
    }

    function initGlobalIndex($key, $value = 1)
    {
        if (! array_key_exists($key, $this->g_index)) {
            $this->checkKey($key);
            $this->checkPosInt($value);
            $sql = "INSERT INTO " . $this->table . " (token_key,token_location,token_state)";
            $sql .= " VALUES ('$key','$key','$value')";
            $this->DbQuery($sql);
            $this->g_index [$key] = $value;
        } else {
            $this->g_index [$key] = $value;
        }
        return $value;
    }

    private function setGlobalIndex($key, $value)
    {
        $sql = "UPDATE " . $this->table;
        $sql .= " SET token_state='$value'";
        $sql .= " WHERE token_key='$key'";
        self::DbQuery($sql);
        $this->g_index [$key] = $value;
        return $value;
    }

    function syncGlobalIndex($key)
    {
        $this->checkKey($key);
        $sql = "SELECT token_state";
        $sql .= " FROM " . $this->table;
        $sql .= " WHERE token_key='$key'";
        $dbres = self::DbQuery($sql);
        $row = mysql_fetch_assoc($dbres);
        if ($row)
            $value = $row ['token_state'];
        else {
            unset($this->g_index [$key]);
            $value = $this->initGlobalIndex($key, 1);
        }
        $this->g_index [$key] = $value;
        return $value;
    }

    function commitGlobalIndex($key)
    {
        if (! array_key_exists($key, $this->g_index)) {
            throw new feException("global index $key is not defined");
        }
        $this->setGlobalIndex($key, $this->g_index [$key]);
        return $this->g_index [$key];
    }
}

class chaisji extends Table
{
    function __construct( )
    {
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();

        self::initGameStateLabels( array(
                "market_state" => 10,       // 1 means market purchase has not occurred.  Negative means user must discard
                "pantry_state" => 11,       // # means pantry items remaining to pick up.  Negative means user must discard
                "pantry_reset_avail" => 12, // 1 means pantry reset is available
                "customer_state" => 13,     // 1 means customer reservation has not occurred
                ""
        ));
            //    "my_second_global_variable" => 11,
            //      ...
            //    "my_first_game_variant" => 100,
            //    "my_second_game_variant" => 101,
            //      ...
        $this->tokens = new Tokens();
        $this->gameinit = false;
    }

    /// @brief Get the name of the game
    protected function getGameName( )
    {
        // Used for translations and stuff. Please do not modify.
        return "chaisji";
    }

    /// setupNewGame:
    ///
    /// This method is called only once, when a new game is launched.
    /// In this method, you must setup the game according to the game rules, so that
    /// the game is ready to be played.
    protected function setupNewGame( $players, $options = array() )
    {
        //// Game colors are black/green/blue/red/white
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $default_colors = $this->ordered_colors;

        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        foreach( $players as $player_id => $player )
        {
            $player['player_money'] = 0;
        }
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        foreach( $players as $player_id => $player )
        {
            $color = array_shift( $default_colors );
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."')";
        }
        $sql .= implode( $values, ',' );
        self::DbQuery( $sql );
        self::reattributeColorsBasedOnPreferences($players, $this->ordered_colors);
        self::reloadPlayersBasicInfos();
        $this->gameinit = true;
        try {
            /**
             * ********** Start the game initialization ****
             */
            // Init global values with their initial values
            // During the players turn these will help keep track of the actions performed by the player
            // The player can only perform one of these actions in a turn.
            self::setGameStateInitialValue('market_state', 1);
            self::setGameStateInitialValue('pantry_state', 3);
            self::setGameStateInitialValue('pantry_reset_avail', 1);
            self::setGameStateInitialValue('customer_state', 1);

            // Init game statistics
            // (note: statistics used in this file must be defined in your stats.inc.php file)
            //self::initStat( 'table', 'table_teststat1', 0 );    // Init a table statistics
            //self::initStat( 'player', 'player_teststat1', 0 );  // Init a player statistics (for all players)

            // Setup the initial game situation here
            $this->initTables();
            // Activate first player (which is in general a good idea :) )
            $this->activeNextPlayer();
        } catch ( Exception $e ) {
            $this->dump('err', $e);
        }
        $this->gameinit = false;
        // Create undo point at the start of the next players turn
        $this->undoSavePoint();
        /************ End of the game initialization *****/
    }

    /// getAllDatas:
    ///
    /// Gather all informations about current game situation (visible by the current player).
    ///
    /// The method is called each time the game interface is displayed to a player, ie:
    /// _ when the game starts
    /// _ when a player refreshes the game page (F5)
    protected function getAllDatas()
    {
        $result = array();

        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!

        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score, player_color color, player_money money FROM player ";
        $result['players'] = self::getCollectionFromDb( $sql );

        // Note: Gather all information about current game situation (visible by player $current_player_id).
        // Gather some general information first
        $result['ordered_flavors'] = $this->ordered_flavors;
        $result['abbr_flavors'] = $this->abbr_flavors;
        $result['ordered_pantry'] = $this->ordered_pantry;
        $result['abbr_pantry'] = $this->abbr_pantry;
        $result['token_types'] = $this->token_types;

        // We want to send the data from the following locations to JS code
        //  faceup_ability -> 3 card abilities
        //  market_1 - market_3 -> market rows
        //  spot_1-5 -> 5 items for pantry board
        //  plaza -> cards available in plaza
        //  tip_jars -> the available tip jars this round
        //  player_$color -> information for player boards and cards (second loop below)
        $result['tokens'] = array();
        $locValue = 0;
        foreach ($this->gameDataLocs as $pos => $loc)
        {
            // Save the location as 'loc' and the array of items at that location in 'items'
            $result['tokens'][$locValue] = array();
            $result['tokens'][$locValue]['loc'] = $loc;
            $result['tokens'][$locValue]['items'] = array();

            if ($loc == 'tip_area')
            {
                // for tip jars the items are hidden
                $tips = $this->tokens->getTokensInLocation($loc);
                for ($x = 0; $x < count($tips); $x++)
                {
                    array_push($result['tokens'][$locValue]['items'], "tip_pos_$x");
                }
            }
            else
            {
                // Normal fill directly from the database
                $this->fillArrayItems($result['tokens'][$locValue]['items'], $this->tokens->getTokensInLocation($loc));
            }
            $locValue++;
        }

        // Player boards will contain teas, flavors, pantry items, and cards
        $this->players_basic = $this->loadPlayersBasicInfos();
        foreach ($this->players_basic as $player_id => $player_info)
        {
            // Setup info
            $color = $player_info['player_color'];
            $idx = $player_id;

            // Setup element in array - these are directly controlled by player
            $loc = "player_$color";
            $result['tokens'][$locValue] = array();
            $result['tokens'][$locValue]['loc'] = $loc;
            $result['tokens'][$locValue]['player_id'] = $idx;
            $result['tokens'][$locValue]['items'] = array();

            $this->fillArrayItems($result['tokens'][$locValue]['items'], $this->tokens->getTokensInLocation($loc));
            $locValue++;

            // These are the items player has selected, but has yet to confirm
            $loc = "player_holding_$color";
            $result['tokens'][$locValue] = array();
            $result['tokens'][$locValue]['loc'] = $loc;
            $result['tokens'][$locValue]['player_id'] = $idx;
            $result['tokens'][$locValue]['items'] = array();

            $this->fillArrayItems($result['tokens'][$locValue]['items'], $this->tokens->getTokensInLocation($loc));
            $locValue++;
        }

        // Round information
        $result['round'] =  $this->tokens->syncGlobalIndex('ROUND');

        return $result;
    }

    /// @brief Fill an iterative array with the token keys from $itemList
    /// @note This is similar to array_keys function, but this preserves the input array (so nothing is lost)
    ///
    /// @return iterative array with keys from $itemList added at the end of the array
    public function fillArrayItems(&$array, $itemList)
    {
        $array = array_merge($array, array_keys($itemList));
    }

    /// getGameProgression:
    ///
    /// Compute and return the current game progression.
    /// The number returned must be an integer beween 0 (=the game just started) and
    /// 100 (= the game is finished or almost finished).
    ///
    /// This method is called each time we are in a game state with the "updateGameProgression" property set to true
    /// (see states.inc.php)
    function getGameProgression()
    {
        // TODO: compute and return the game progression

        return 0;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////
    function utility______UTILITIES___()
    {
        return 0;
    }

    // Test function
    function dumpTokensPerLocation()
    {
        self::trace("Immer Debug Function");
        // Setup the board for the next players turn
        $tokensPerLocation = $this->tokens->countTokensInLocations();
        $this->dump('tokensPerLocation', $tokensPerLocation);

        // Check pantry that items are in place
        $pantryAreas = array('spot_1', 'spot_2', 'spot_3', 'spot_4', 'spot_5');
        foreach ($pantryAreas as $pos => $loc)
        {
            $this->dump('loc', $loc);
            // If key does not exist then it has a count of zero
            if (!array_key_exists($loc, $tokensPerLocation))
            {
                self::trace('This location is empty');
            }
        }
    }

    // Test function
    function dumpActivePlayer()
    {
        $sql = "SELECT player_id id, player_no pno, player_score score, player_color color FROM player ";
        $data = self::getCollectionFromDb( $sql );

        $this->dump('dumpActivePlayer', $data);
    }

    function testSavePoint()
    {
        // Create a save point at the end of this routine (start of next players turn)
        $this->undoSavePoint();
    }

    /// @brief Set a players money total
    ///
    /// @param player_id
    /// @param money
    function setPlayerMoney($player_id, $money)
    {
        $sql = "UPDATE player
                SET player_money = $money
                WHERE player_id = $player_id";
        self::DbQuery($sql);
    }

    /// @brief Get a players money total as an integer value
    ///
    /// @param player_id
    /// @returns integer value
    function getPlayerMoney($player_id)
    {
        $sql = "SELECT player_money
                FROM player
                WHERE  player_id = $player_id";
        $money = self::getNonEmptyObjectFromDb($sql);
        return intval($money['player_money']);
    }

    /// @brief Increments a players money total by a specific amount
    ///
    /// @param player_id
    /// @param inc The amount of money to increase
    function incPlayerMoney($player_id, $inc)
    {
        $money = $this->getPlayerMoney($player_id);
        $money = $money + $inc;
        $this->setPlayerMoney($player_id, $money);

        self::notifyAllPlayers("moneyUpdate", clienttranslate('${player_name} collects ${inc} coins'), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'inc' => $inc,
            'money' => $money)
        );
    }

    /// @brief Decrements a players money total by an amount.  Returns false if player does not have enough money.
    ///
    /// @param player_id
    /// @param dec The amount of money to decrease
    /// @returns bool - true if money adjusted, false if money would go negative
    function decPlayerMoney($player_id, $dec)
    {
        $money = $this->getPlayerMoney($player_id);
        if ($money >= $dec)
        {
            $money = $money - $dec;
            $this->setPlayerMoney($player_id, $money);
            return true;
        }
        return false;
    }

    /// @brief Test $cond and throw exception if $cond is false
    ///
    /// The message should be translated and shown to the user
    ///
    /// @param message string is server side log message, no translation needed
    /// @param cond boolean condition which will be true or false (optional)
    /// @param log string list of parameters (optional)
    /// @throws BgaUserException
    function userAssertTrue($message, $cond = false, $log = "")
    {
        if ($cond)
            return;
        if ($log)
            $this->warn($message . " " . $log);
        throw new BgaUserException($message);
    }

    /// @brief Test $cond and throw exception if $cond is false
    ///
    /// Use this over userAssertTrue if client prevents error, error can only occur if user hacks game
    ///
    /// @param log string server side log message, no translation needed
    /// @param cond boolean condition which will be true or false (optional)
    /// @throws BgaUserException
    function systemAssertTrue($log, $cond = false)
    {
        if ($cond)
            return;
        //trigger_error("bt") ;
        //$bt = debug_backtrace();
        //$this->dump('bt',$bt);
        $this->error("Internal Error during move: $log|");
        //throw new feException($log);
        throw new BgaUserException(self::_("Internal Error. That should not have happened. Please raise a bug. ") . $log); // TODO remove
    }

    /// @brief initialize the tables, this performs the token setup for the game
    ///
    /// Helper for setupNewGame - updates $this->tokens
    function initTables()
    {
        // ROUND will contain the round.  The game is played over 5 rounds.
        $this->tokens->initGlobalIndex('ROUND', 1);

        $num = $this->getNumPlayers();

        // 1. Tea flavors.  12 each of mint jasmine lemon ginger berries and lavender
        foreach ( $this->ordered_flavors as $res )
        {
            if (strcasecmp($res,'wild') != 0)
            {
                $this->tokens->createTokensPack("flavor_{INDEX}_$res", "flavor_stock", 12);
            }
            else
            {
                $this->tokens->createTokensPack("flavor_{INDEX}_$res", "flavor_stock", 6);
            }
        }
        $this->tokens->shuffle('flavor_stock');

        // Create 3 rows of 6 tokens for the market
        $this->tokens->pickTokensForLocation(6, 'flavor_stock', 'market_1');
        $this->tokens->pickTokensForLocation(6, 'flavor_stock', 'market_2');
        $this->tokens->pickTokensForLocation(6, 'flavor_stock', 'market_3');

        // 2. Pantry tokens.  10 each of milk sugar honey vanilla chai + 5 any_pantry
        foreach ( $this->ordered_pantry as $res )
        {
            if (strcasecmp($res,'any_pantry') != 0)
            {
                $this->tokens->createTokensPack("pantry_{INDEX}_$res", "pantry_stock", 10);
            }
            else
            {
                $this->tokens->createTokensPack("pantry_{INDEX}_$res", "pantry_stock", 5);
            }
        }
        $this->tokens->shuffle('pantry_stock');
        $this->tokens->pickTokensForLocation(1, 'pantry_stock', 'spot_1');
        $this->tokens->pickTokensForLocation(1, 'pantry_stock', 'spot_2');
        $this->tokens->pickTokensForLocation(1, 'pantry_stock', 'spot_3');
        $this->tokens->pickTokensForLocation(1, 'pantry_stock', 'spot_4');
        $this->tokens->pickTokensForLocation(1, 'pantry_stock', 'spot_5');

        // 3. Tip tokens.  6, deal one per player
        $this->tokens->createTokensPack('tip_{INDEX}', "tip_stock", 6);
        $this->tokens->shuffle('tip_stock');
        $this->tokens->pickTokensForLocation($num, 'tip_stock', 'tip_area');

        // 4. Ability cards.  8 total in game, deal 3
        $this->tokens->createTokensPack('card_ability_{INDEX}', "ability_deck", 8);
        $this->tokens->shuffle('ability_deck');
        $this->tokens->pickTokensForLocation(3, 'ability_deck', 'ability_area');

        // 4a. If card_ability_2 is taken then put 3 flavor tokens on the card
        $specialToken = $this->tokens->getTokenInfo('card_ability_2');
        if ($specialToken['location'] == 'ability_area')
        {
            $this->tokens->pickTokensForLocation(3, 'flavor_stock', 'card_ability_2');
        }

        // 5. Tea tokens. 6 per player
        // 6. Customer cards. 11 per player
        //  1 in reserve
        //  1 in plaza
        //  6 in deck
        //  Remaining are discarded
        // 7. Money (1 for first player and 2 for everyone else)
        $money = 1;
        foreach ( $this->players_basic as $player_id => $player_info )
        {
            $color = $player_info ['player_color'];
            // 5.
            $this->tokens->createTokensPack("tea_{INDEX}_$color", "player_$color", 6);
            // 6.
            $this->tokens->createTokensPack("customer_{INDEX}_$color", "player_deck_$color", 11);
            $this->tokens->shuffle("player_deck_$color");
            $this->tokens->pickTokensForLocation(1, "player_deck_$color", 'plaza_area');
            $this->tokens->pickTokensForLocation(1, "player_deck_$color", "player_$color");
            $this->tokens->pickTokensForLocation(6, "player_deck_$color", 'customer_deck');
            // 7.
            $this->setPlayerMoney($player_id, $money);
            $money = 2;
        }
        // 8. Now shuffle customer deck and deal 2 more to the plaza
        $this->tokens->shuffle('customer_deck');
        $this->tokens->pickTokensForLocation(2, 'customer_deck', 'plaza_area');

        // Commit globals
        $this->tokens->commitGlobalIndex('ROUND');
    }

    /// @brief Get the number of players in this game
    /// @returns int  the number of players in the game
    public function getNumPlayers()
    {
        if (!isset($this->players_basic))
        {
            $this->players_basic = $this->loadPlayersBasicInfos();
        }
        return count($this->players_basic);
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    function st______PLAYER_ACTIONS___()
    {
        return 0;
    }

    /// This item allows control of the game states via user action
    /// $stateId - is the button ID that was selected to get into this position
    function action_gameStateChange( $stateId )
    {
        // Verify that action is legal
        $this->checkAction('playStateChange');

        $player_id = self::getActivePlayerId();
        $color = $this->getPlayerColorById($player_id);

        // TBD - check that action is legal
        // Once state changes then move any tokens from the holding area to the player board
        $this->tokens->moveAllTokensInLocation("player_holding_$color", "player_$color");

        // Advance to the next game state
        switch ($stateId)
        {
            case 'button_market_id':
                $this->incPlayerMoney($player_id, 3);
                $this->gamestate->nextState('market');
                break;
            case 'button_pantry_id':
                $this->gamestate->nextState('pantry');
                break;
            case 'button_customer_id':
                $this->gamestate->nextState('reserve');
                break;
            case 'button_advance_id':
                $this->gamestate->nextState('advance');
                break;
            case 'button_next_id':
                $this->gamestate->nextState('next');
                break;
            case 'button_undo_id':
                $this->undoRestorePoint();
                break;
            default:
                $this->gamestate->nextState('next');
                break;
        }
        //self::trace("Immer Passes");
        //$this->dump('main', $stateId);
    }

    /// This handles pantry bag selection.  There are two possible actions: reset and bag.
    ///  'Reset' costs 1 money and resets all 5 pantry tokens
    ///  'Bag' selects one token from the bag.  This is one of the 3 that the user is allowed
    function action_PantryBagSelection( $cmdId )
    {
        // Verify that action is legal
        $this->checkAction('playBagPantry');

        $player_id = self::getActivePlayerId();

        // TBD - Check action is legal
        if ($cmdId == 'button_resetpantry_id')
        {
            // TODO - subtract one money from player
            // TODO - Don't allow going back once this is done
            // TODO - Only allow once per player - cannot do once a tile is taken
            // Move all tokens back to bag
            $this->tokens->moveAllTokensInLocation('spot_1', 'pantry_stock');
            $this->tokens->moveAllTokensInLocation('spot_2', 'pantry_stock');
            $this->tokens->moveAllTokensInLocation('spot_3', 'pantry_stock');
            $this->tokens->moveAllTokensInLocation('spot_4', 'pantry_stock');
            $this->tokens->moveAllTokensInLocation('spot_5', 'pantry_stock');

            // Shuffle tiles from bag
            $this->tokens->shuffle('pantry_stock');

            // Pick 5 new items
            $this->tokens->pickTokensForLocation(1, 'pantry_stock', 'spot_1');
            $this->tokens->pickTokensForLocation(1, 'pantry_stock', 'spot_2');
            $this->tokens->pickTokensForLocation(1, 'pantry_stock', 'spot_3');
            $this->tokens->pickTokensForLocation(1, 'pantry_stock', 'spot_4');
            $this->tokens->pickTokensForLocation(1, 'pantry_stock', 'spot_5');

            // Notify all players of the changes
            ///TODO THIS NEEDS TO BE UPDATED
            $new_board = array();
            $this->fillArrayItems($new_board, $this->tokens->getTokensInLocation('spot_1'));
            $this->fillArrayItems($new_board, $this->tokens->getTokensInLocation('spot_2'));
            $this->fillArrayItems($new_board, $this->tokens->getTokensInLocation('spot_3'));
            $this->fillArrayItems($new_board, $this->tokens->getTokensInLocation('spot_4'));
            $this->fillArrayItems($new_board, $this->tokens->getTokensInLocation('spot_5'));
            self::notifyAllPlayers("pantryUpdate", clienttranslate('${player_name} reset pantry'), array(
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName(),
                'action_id' => $cmdId,
                'pantry_board' => $new_board)
            );

            // Cannot undo this action
            $this->undoSavePoint();
        }
        else if ($cmdId == 'button_bagpantry_id')
        {
            // TODO - Keep track of number of tiles taken
            $color = $this->getPlayerColorById($player_id);

            // Take one item from the pantry stock and give it to the active player
            $token_update = $this->tokens->pickTokensForLocation(1, "pantry_stock", "player_$color");

            // Notify all players of the change
            //$token = array('obj' => object, 'method' => method_name);
            self::notifyAllPlayers("tokenUpdate", clienttranslate('${player_name} selects an item from the pantry supply'), array(
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName(),
                'token' => $token_update)
            );

            // Cannot undo this action
            $this->undoSavePoint();
        }
    }

    function action_GenericSelection( $cmdId, $selection )
    {
        // Verify that action is legal
        $this->checkAction('playSelection');

        $player_id = self::getActivePlayerId();
        $color = $this->getPlayerColorById($player_id);

        switch ($cmdId)
        {
            case 'button_select_market_id':
                // Verify selection of all items are in the market
                /// TODO

                // Verify that all items selected are connected properly
                /// TODO

                // Verify that player has enough money to cover the cost of the tiles
                /// TODO

                // Move items to player board of the active player
                $this->tokens->moveTokens($selection, "player_$color");
                break;

            case 'button_select_pantry_id':
                // Items should be in pantry, otherwise there is a problem
                //$tokenInfoList = $this->tokens->getTokensInfo($selection);
                //$this->dump('tokenList', $tokenInfoList);
                //TBD

                $pantry = self::getGameStateValue('pantry_state');

                // Verify that the number of items selected does not exceed available number player can select
                if (count($selection) <= $pantry)
                {
                    // Move items to player board of the active player
                    $this->tokens->moveTokens($selection, "player_holding_$color");

                    // Update number of available tokens
                    $pantry = $pantry - count($selection);

                    // If selections have been made (count is zero then check for discards)
                    if ($pantry == 0)
                    {
                        $totalPantry = $this->tokens->countTokensTypeInLocation("player_$color","pantry_%") + $this->tokens->countTokensInLocation("player_holding_$color");
                        if ($totalPantry > 6)
                        {
                            // Must discard (should be a negative value to tell JS to perform a discard selection)
                            $pantry = 6 - $totalPantry;
                        }
                    }
                    self::setGameStateValue('pantry_state', $pantry);
                }
                else
                {
                    // Exception
                    $this->userAssertTrue(self::_('Too many pantry items selected'));
                }
                break;

                case 'button_trash_pantry_id':
                    // Items should be in pantry, otherwise there is a problem
                    //$tokenInfoList = $this->tokens->getTokensInfo($selection);
                    //$this->dump('tokenList', $tokenInfoList);
                    //TBD

                    $pantry = self::getGameStateValue('pantry_state');

                    // Verify that the number of items selected does not exceed available number player can select
                    if (($pantry < 0) && (count($selection) <= (-1 * $pantry)))
                    {
                        // Move items to player board of the active player
                        $this->tokens->moveTokens($selection, "pantry_discard");

                        $totalPantry = $this->tokens->countTokensTypeInLocation("player_$color","pantry_%") + $this->tokens->countTokensInLocation("player_holding_$color");
                        // Must discard (should be a negative value to tell JS to perform a discard selection)
                        $pantry = 6 - $totalPantry;
                        self::setGameStateValue('pantry_state', $pantry);
                    }
                    else
                    {
                        // Exception
                        $this->userAssertTrue(self::_('Too many pantry items selected'));
                    }
                    break;

                case 'button_select_customer_id':
                // Only one customer can be reserved
                $this->systemAssertTrue('Too many items selected', count($selection) == 1);

                // Verify that customer is in the plaza_area
                ///TODO

                // Verify that player does not exceed maximum items to reserve which is 3
                ///TODO

                // Move items to player board of the active player
                $this->tokens->moveTokens($selection, "player_$color");
                break;

            default:
                $this->systemAssertTrue(self::_('Unexpected operation in action_GenericSelection'));
                break;
        }

        // This needs to be an iterable array
        $tokenInfoList = $this->tokens->getTokensInfo($selection);
        $tokenInfoList = array_values($tokenInfoList);

        self::notifyAllPlayers("tokenUpdate", clienttranslate('${player_name} selects an item from the pantry supply'), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'token' => $tokenInfoList,
            'market_state' => self::getGameStateValue('market_state'),
            'pantry_state' => self::getGameStateValue('pantry_state'),
            'pantry_reset_avail' => self::getGameStateValue('pantry_reset_avail'),
            'customer_state' => self::getGameStateValue('customer_state'))
        );
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */
    function arg______ARGS___()
    {
        return 0;
    }

    //////////// --- Game state arguments generated begin ---
    function arg_playerTurnMain()
    {
        return array(
            'market_state' => self::getGameStateValue('market_state'),
            'pantry_state' => self::getGameStateValue('pantry_state'),
            'pantry_reset_avail' => self::getGameStateValue('pantry_reset_avail'),
            'customer_state' => self::getGameStateValue('customer_state')
        );
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////
    /*
     * Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
     * The action method of state X is called everytime the current game state is set to X.
     */
    function st______GAME_STATE_ACTIONS___()
    {
        return 0;
    }

    function st_gameTurnNextPlayer()
    {
        // Setup the board for the next players turn
        $tokensPerLocation = $this->tokens->countTokensInLocations();

        // Check pantry for refresh
        $pantryAreas = array('spot_1', 'spot_2', 'spot_3', 'spot_4', 'spot_5');
        $tokensAddedToBoard = array();
        foreach ($pantryAreas as $pos => $loc)
        {
            if (!array_key_exists($loc, $tokensPerLocation))
            {
                ///TODO - need to handle case where pantry_stock is empty
                $tokens = $this->tokens->pickTokensForLocation(1, 'pantry_stock', $loc);
                $tokensAddedToBoard = array_merge($tokensAddedToBoard, array_values($tokens));
            }
        }

        if (count($tokensAddedToBoard) > 0)
        {
            self::notifyAllPlayers("tokenUpdate", clienttranslate('Pantry restocked'), array(
                'player_id' => 0,
                'token' => $tokensAddedToBoard)
            );
        }

        // Check market for refresh
        $pantryAreas = array('market_1', 'market_2', 'market_3');
        $tokensAddedToBoard = array();

        foreach ($pantryAreas as $pos => $loc)
        {
            // If there are no items
            if (!array_key_exists($loc, $tokensPerLocation))
            {
                ///TODO - need to handle case where pantry_stock is empty
                $tokens = $this->tokens->pickTokensForLocation(6, 'flavor_stock', $loc);
                $tokensAddedToBoard = array_merge($tokensAddedToBoard, array_values($tokens));
            }
            else if ($tokensPerLocation[$loc] < 6)
            {
                $numToSelect = 6 - $tokensPerLocation[$loc];
                $tokens = $this->tokens->pickTokensForLocation($numToSelect, 'flavor_stock', $loc);
                $tokensAddedToBoard = array_merge($tokensAddedToBoard, array_values($tokens));
            }
        }

        if (count($tokensAddedToBoard) > 0)
        {
            self::notifyAllPlayers("tokenUpdate", clienttranslate('Market restocked'), array(
                'player_id' => 0,
                'token' => $tokensAddedToBoard)
            );
        }

        // Set active player to the next person in turn order
        $next_player_id = $this->activeNextPlayer();

        // If number of tip jars is zero then setup a new round


        ////$next_player_id = $this->activeNextPlayerCustom();
        //if ($next_player_id == null) {
            // active player wins the game
            //$this->gamestate->nextState('last');
            //return;
        //}

        // Reset globals as turn advances
        self::setGameStateInitialValue('market_state', 1);
        self::setGameStateInitialValue('pantry_state', 3);

        ///TODO if next player has no money then pantry reset option is not available at the start of the turn
        self::setGameStateInitialValue('pantry_reset_avail', 1);

        ///TODO if next player already has 3 customers reserved then customer option is not available
        self::setGameStateInitialValue('customer_state', 1);

        // Otherwise continue with next players turn
        $this->gamestate->nextState('next');

        // Create undo point at the start of the next players turn
        $this->undoSavePoint();
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:

        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).

        Important: your zombie code will be called when the player leaves the game. This action is triggered
        from the main site and propagated to the gameserver from a server, not from a browser.
        As a consequence, there is no current player associated to this action. In your zombieTurn function,
        you must _never_ use getCurrentPlayerId() or getCurrentPlayerName(), otherwise it will fail with a "Not logged" error message.
    */
    function zombieTurn( $state, $active_player )
    {
        $statename = $state['name'];

        if ($state['type'] === "activeplayer")
        {
            switch ($statename) {
                default:
                    $this->gamestate->nextState( "next" );
                    break;
            }

            return;
        }

        throw new feException( "Zombie mode not supported at this game state: ".$statename );
    }

///////////////////////////////////////////////////////////////////////////////////:
////////// DB upgrade
//////////

    /*
        upgradeTableDb:

        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.

    */

    function upgradeTableDb( $from_version )
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345

        // Example:
//        if( $from_version <= 1404301345 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        if( $from_version <= 1405061421 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        // Please add your future database scheme changes here
//
//


    }
}
