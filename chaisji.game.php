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

/*
 * This is a generic class to manage game pieces.
 *
 * On DB side this is based on a standard table with the following fields:
 * token_key (string), token_location (string), token_state (int)
 *
 */
class Tokens extends APP_GameClass {
    var $table;
    var $autoreshuffle = false; // If true, a new deck is automatically formed with a reshuffled discard as soon at is needed
    var $autoreshuffle_trigger = null; // Callback to a method called when an autoreshuffle occurs
    // autoreshuffle_trigger = array( 'obj' => object, 'method' => method_name )
    var $autoreshuffle_custom = array ();
    private $custom_fields;
    private $g_index;

    // If defined, tell the name of the deck and what is the corresponding discard (ex : "mydeck" => "mydiscard")
    function __construct() {
        $this->table = 'token';
        $this->custom_fields = array ();
        $this->g_index = array ();
    }

    // MUST be called before any other method if db is not called 'token'
    function init($table) {
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
    function createTokens($tokens, $location_global, $token_state_global = null) {
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

    function createTokensPack($key, $location, $nbr = 1, $nbr_start = 0, $iterArr = null) {
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

    // Get max on min state on the specific location
    function getExtremePosition($getMax, $location) {
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

    // Shuffle token of a specified location, result of the operation will changes state of the token to be a position after shuffling
    function shuffle($location) {
        self::checkLocation($location);
        $token_keys = self::getObjectListFromDB("SELECT token_key FROM " . $this->table . " WHERE token_location='$location'", true);
        shuffle($token_keys);
        $n = 0;
        foreach ( $token_keys as $token_key ) {
            self::DbQuery("UPDATE " . $this->table . " SET token_state='$n' WHERE token_key='$token_key'");
            $n ++;
        }
    }

    // Pick the first "$nbr" cards on top of specified deck and place it in target location
    // Return cards infos or void array if no card in the specified location
    function pickTokensForLocation($nbr, $from_location, $to_location, $state = 0, $no_deck_reform = false) {
        $tokens = self::getTokensOnTop($nbr, $from_location);
        $tokens_ids = array ();
        foreach ( $tokens as $i => $card ) {
            $tokens_ids [] = $card ['key'];
            $tokens [$i] ['location'] = $to_location;
            $tokens [$i] ['state'] = $state;
        }
        $sql = "UPDATE " . $this->table . " SET token_location='" . addslashes($to_location) . "', token_state='$state' ";
        $sql .= "WHERE token_key IN ('" . implode("','", $tokens_ids) . "') ";
        self::DbQuery($sql);
        if (isset($this->autoreshuffle_custom [$from_location]) && count($tokens) < $nbr && $this->autoreshuffle && ! $no_deck_reform) {
            // No more cards in deck & reshuffle is active => form another deck
            $nbr_token_missing = $nbr - count($tokens);
            self::reformDeckFromDiscard($from_location);
            $newcards = self::pickCardsForLocation($nbr_token_missing, $from_location, $to_location, $state, true); // Note: block anothr deck reform
            foreach ( $newcards as $card ) {
                $tokens [] = $card;
            }
        }
        return $tokens;
    }

    /**
     * Return token on top of this location, top defined as item with higher state value
     */
    function getTokenOnTop($location) {
        $result_arr = $this->getTokensOnTop(1, $location);
        if (count($result_arr) > 0)
            return $result_arr [0];
        return null;
    }

    /**
     * Return "$nbr" tokens on top of this location, top defined as item with higher state value
     */
    function getTokensOnTop($nbr, $location) {
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

    function reformDeckFromDiscard($from_location) {
        self::checkLocation($from_location);
        if (isset($this->autoreshuffle_custom [$from_location]))
            $discard_location = $this->autoreshuffle_custom [$from_location];
        else
            throw new feException("reformDeckFromDiscard: Unknown discard location for $from_location !");
        self::checkLocation($discard_location);
        self::moveAllTokensInLocation($discard_location, $from_location);
        self::shuffle($from_location);
        if ($this->autoreshuffle_trigger) {
            $obj = $this->autoreshuffle_trigger ['obj'];
            $method = $this->autoreshuffle_trigger ['method'];
            $obj->$method($from_location);
        }
    }

    // Move a card to specific location
    function moveToken($token_key, $location, $state = 0) {
        self::checkLocation($location);
        self::checkState($state);
        self::checkKey($token_key);
        $sql = "UPDATE " . $this->table;
        $sql .= " SET token_location='$location', token_state='$state'";
        $sql .= " WHERE token_key='$token_key'";
        self::DbQuery($sql);
    }

    // Move cards to specific location
    function moveTokens($tokens, $location, $state = 0) {
        self::checkLocation($location);
        self::checkState($state);
        self::checkTokenKeyArray($tokens);
        $sql = "UPDATE " . $this->table;
        $sql .= " SET token_location='$location', token_state='$state'";
        $sql .= " WHERE token_key IN ('" . implode("','", $tokens) . "')";
        self::DbQuery($sql);
    }

    // Move a card to a specific location where card are ordered. If location_arg place is already taken, increment
    // all tokens after location_arg in order to insert new card at this precise location
    function insertToken($token_key, $location, $state = 0) {
        self::checkLocation($location);
        self::checkState($state);
        $sql = "UPDATE " . $this->table;
        $sql .= " SET token_state=token_state+1";
        $sql .= " WHERE token_location='$location' ";
        $sql .= " AND token_state>=$state";
        self::DbQuery($sql);
        self::moveToken($token_key, $location, $state);
    }

    function insertTokenOnExtremePosition($token_key, $location, $bOnTop) {
        $extreme_pos = self::getExtremePosition($bOnTop, $location);
        if ($bOnTop)
            self::insertToken($token_key, $location, $extreme_pos + 1);
        else
            self::insertToken($token_key, $location, $extreme_pos - 1);
    }

    // Move all tokens from a location to another
    // !!! state is reset to 0 or specified value !!!
    // if "from_location" and "from_state" are null: move ALL cards to specific location
    function moveAllTokensInLocation($from_location, $to_location, $from_state = null, $to_state = 0) {
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

    /**
     * Move all tokens from a location to another location arg stays with the same value
     */
    function moveAllTokensInLocationKeepOrder($from_location, $to_location) {
        self::checkLocation($from_location);
        self::checkLocation($to_location);
        $sql = "UPDATE " . $this->table;
        $sql .= " SET token_location='$to_location'";
        $sql .= " WHERE token_location='$from_location'";
        self::DbQuery($sql);
    }

    /**
     * Return all tokens in specific location
     * note: if "order by" is used, result object is NOT indexed by ids
     */
    function getTokensInLocation($location, $state = null, $order_by = null) {
        return $this->getTokensOfTypeInLocation(null, $location, $state, $order_by);
    }

    /**
     * Get tokens of a specific type in a specific location, since there is no field for type we use like expression on
     * key
     *
     * @param string $type            
     * @param string $location            
     * @param int $state
     *    
     * @return array mixed
     */
    function getTokensOfTypeInLocation($type, $location = null, $state = null, $order_by = null) {
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

    /**
     * Get specific token info
     */
    function getTokenInfo($token_key) {
        self::checkKey($token_key);
        $sql = $this->getSelectQuery();
        $sql .= " WHERE token_key='$token_key' ";
        $dbres = self::DbQuery($sql);
        return mysql_fetch_assoc($dbres);
    }

    /**
     * Get specific tokens info
     */
    function getTokensInfo($tokens_array) {
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

    function countTokensInLocation($location, $state = null) {
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

    // Return an array "location" => number of cards
    function countTokensInLocations() {
        $result = array ();
        $sql = "SELECT token_location, COUNT( token_key ) cnt FROM " . $this->table . " GROUP BY token_location ";
        $dbres = self::DbQuery($sql);
        while ( $row = mysql_fetch_assoc($dbres) ) {
            $result [$row ['token_location']] = $row ['cnt'];
        }
        return $result;
    }

    // Return an array "state" => number of tokens (for this location)
    function countTokensByState($location) {
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

    function varsub($line, $keymap, $usegindex = false) {
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

    final function checkLocation($location, $like = false) {
        if ($location == null)
            throw new feException("location cannot be null");
        $extra = "";
        if ($like)
            $extra = "%";
        if (preg_match("/^[A-Za-z_0-9${extra}-]+$/", $location) == 0) {
            throw new feException("location must be  alphanum and underscore non empty string");
        }
    }

    final function checkState($state, $canBeNull = false) {
        if ($state === null && $canBeNull == false)
            throw new feException("state cannot be null");
        if ($state !== null && preg_match("/^-*[0-9]+$/", $state) == 0) {
            throw new feException("state must be integer number");
        }
    }

    final function checkTokenKeyArray($arr) {
        if ($arr == null)
            throw new feException("tokens cannot be null");
        if (! is_array($arr))
            throw new feException("tokens must be an array");
        foreach ( $arr as $key ) {
            $this->checkKey($key);
        }
    }

    final function checkKey($key, $like = false) {
        if ($key == null)
            throw new feException("key cannot be null");
        $extra = "";
        if ($like)
            $extra = "%";
        if (preg_match("/^[A-Za-z_0-9${extra}]+$/", $key) == 0) {
            throw new feException("key must be alphanum and underscore non empty string '$key'");
        }
    }

    final function checkType($key) {
        if ($key == null)
            throw new feException("type cannot be null");
        $this->checkKey($key, true);
    }

    final function checkPosInt($key) {
        if ($key && preg_match("/^[0-9]+$/", $key) == 0) {
            throw new feException("must be integer number");
        }
    }

    final function getSelectQuery() {
        $sql = "SELECT token_key AS \"key\", token_location AS \"location\", token_state AS \"state\"";
        if (count($this->custom_fields)) {
            $sql .= ", ";
            $sql .= implode(', ', $this->custom_fields);
        }
        $sql .= " FROM " . $this->table;
        return $sql;
    }

    function setCustomFields($fields_array) {
        $this->checkTokenKeyArray($fields_array);
        $this->custom_fields = $fields_array;
    }

    function initGlobalIndex($key, $value = 1) {
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

    private function setGlobalIndex($key, $value) {
        $sql = "UPDATE " . $this->table;
        $sql .= " SET token_state='$value'";
        $sql .= " WHERE token_key='$key'";
        self::DbQuery($sql);
        $this->g_index [$key] = $value;
        return $value;
    }

    function syncGlobalIndex($key) {
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

    function commitGlobalIndex($key) {
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
                "game_round" => 10
        ));
            //    "my_second_global_variable" => 11,
            //      ...
            //    "my_first_game_variant" => 100,
            //    "my_second_game_variant" => 101,
            //      ...
        $this->tokens = new Tokens();
        $this->gameinit = false;
    }
    
    protected function getGameName( )
    {
        // Used for translations and stuff. Please do not modify.
        return "chaisji";
    }   

    /*
        setupNewGame:
        
        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame( $players, $options = array() )
    {
        //// Game colors are black/green/blue/red/white
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $default_colors = $this->ordered_colors;
 
        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
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
            self::setGameStateInitialValue('game_round', 1);
        
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
        /************ End of the game initialization *****/
    }

    /*
        getAllDatas: 
        
        Gather all informations about current game situation (visible by the current player).
        
        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = array();
    
        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!
    
        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score, player_color color FROM player ";
        $result['players'] = self::getCollectionFromDb( $sql );
  
        // TODO: Gather all information about current game situation (visible by player $current_player_id).
        // Gather some general information first
        $result['ordered_flavors'] = $this->ordered_flavors;
        $result['ordered_pantry'] = $this->ordered_pantry;

        // We want to send the data from the following locations to JS code
        //  faceup_ability -> 3 card abilities
        //  market_1 - market_3 -> market rows
        //  pantry_board -> 5 items for pantry board
        //  plaza -> cards available in plaza
        //  tip_jars -> the available tip jars this round
        //  player_$color -> information for player boards and cards
        // TBD
        //  Round ?
        //  Money ?
        foreach ($this->gameDataLocs as $pos => $loc)
        {
            // These locations just need to send the key data as an array
            $result[$loc] = array();
            $this->fillArrayItems($result[$loc], $this->tokens->getTokensInLocation($loc));
        }

        // Player boards will contain teas, flavors, pantry items, cards, and money
        // TBD

        // There is no hidden information in this game

        return $result;
    }

    /*
        toJsId

        Translate ID to JavaScript ID
    */
    public function toJsId($id) 
    {
        return $id;
    }

    /*
        toPhpId

        Translate ID to PHP ID
    */
    public function toPhpId($id) 
    {
        return $id;
    }

    /*
        fillArrayWithTokenKey

        Copy items from $tokenKeyArray and put then into an array indexed by the key.
    */
    public function fillArrayWithTokenKey(&$array, $tokenKeyArray) 
    {
        foreach ( $tokenKeyArray as $pos => $item ) {
            $jsId = $this->toJsId($item ['key']);
            $array [$jsId] = $item;
        }
    }

    public function fillArrayItems(&$array, $itemList) 
    {
        foreach ( $itemList as $pos => $item ) {
            $jsId = $this->toJsId($item ['key']);
            array_push($array, $jsId);
        }
    }

    /*
        getGameProgression:
        
        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).
    
        This method is called each time we are in a game state with the "updateGameProgression" property set to true 
        (see states.inc.php)
    */
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

    function initTables() 
    {
        $this->tokens->initGlobalIndex('GINDEX', 0);
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
        // 2. Pantry tokens.  10 each of milk sugar honey vanilla chai + 5 wild
        foreach ( $this->ordered_pantry as $res ) 
        {
            if (strcasecmp($res,'wild') != 0)
            {
                $this->tokens->createTokensPack("pantry_{INDEX}_$res", "pantry_stock", 10);
            }
            else
            {
                $this->tokens->createTokensPack("pantry_{INDEX}_$res", "pantry_stock", 5);
            }
        }
        $this->tokens->shuffle('pantry_stock');
        $this->tokens->pickTokensForLocation(5, 'pantry_stock', 'pantry_board');
        // 3. Tip tokens.  6, deal one per player
        $this->tokens->createTokensPack('tip_{INDEX}', "tip_stock", 6);
        $this->tokens->shuffle('tip_stock');
        $this->tokens->pickTokensForLocation($num, 'tip_stock', 'tip_jars');
        // 4. Ability cards.  11 total in game, deal 3
        $this->tokens->createTokensPack('ability_{INDEX}', "ability_deck", 11);
        $this->tokens->shuffle('ability_deck');
        $this->tokens->pickTokensForLocation(3, 'ability_deck', 'faceup_ability');
        // 5. Tea tokens. 6 per player
        // 6. Customer cards. 11 per player
        //  1 in reserve
        //  1 in plaza
        //  6 in deck
        //  Remaining are discarded
        foreach ( $this->players_basic as $player_id => $player_info ) 
        {
            $color = $player_info ['player_color'];
            // 5.
            $this->tokens->createTokensPack("tea_{INDEX}_$color", "player_$color", 6);
            // 6.
            $this->tokens->createTokensPack("customer_{INDEX}_$color", "player_deck_$color", 11);
            $this->tokens->shuffle("player_deck_$color");
            $this->tokens->pickTokensForLocation(1, "player_deck_$color", 'plaza');
            $this->tokens->pickTokensForLocation(1, "player_deck_$color", "player_$color");
            $this->tokens->pickTokensForLocation(6, "player_deck_$color", 'customer_deck');
        }
        // 7. Now shuffle customer deck and deal 2 more to the plaza
        $this->tokens->shuffle('customer_deck');
        $this->tokens->pickTokensForLocation(2, 'customer_deck', 'plaza');
        $this->tokens->commitGlobalIndex('GINDEX');
    }

    public function getNumPlayers() 
    {
        if (! isset($this->players_basic)) {
            $this->players_basic = $this->loadPlayersBasicInfos();
        }
        return count($this->players_basic);
    }

    /*
        In this space, you can put any utility methods useful for your game logic
    */



//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in chaisji.action.php)
    */

    /*
    
    Example:

    function playCard( $card_id )
    {
        // Check that this is the player's turn and that it is a "possible action" at this game state (see states.inc.php)
        self::checkAction( 'playCard' ); 
        
        $player_id = self::getActivePlayerId();
        
        // Add your game logic to play a card there 
        ...
        
        // Notify all players about the card played
        self::notifyAllPlayers( "cardPlayed", clienttranslate( '${player_name} plays ${card_name}' ), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card_name' => $card_name,
            'card_id' => $card_id
        ) );
          
    }
    
    */

    
//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    /*
    
    Example for game state "MyGameState":
    
    function argMyGameState()
    {
        // Get some values from the current game situation in database...
    
        // return values:
        return array(
            'variable1' => $value1,
            'variable2' => $value2,
            ...
        );
    }    
    */

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */
    
    /*
    
    Example for game state "MyGameState":

    function stMyGameState()
    {
        // Do some stuff ...
        
        // (very often) go to another gamestate
        $this->gamestate->nextState( 'some_gamestate_transition' );
    }    
    */

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
        
        if ($state['type'] === "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->nextState( "zombiePass" );
                    break;
            }

            return;
        }

        if ($state['type'] === "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $this->gamestate->setPlayerNonMultiactive( $active_player, '' );
            
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
