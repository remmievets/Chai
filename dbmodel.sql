
-- ------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- Chai implementation : © Steve Immer <remmievets@gmail.com>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----

-- dbmodel.sql

-- This is the file where you are describing the database schema of your game
-- Basically, you just have to export from PhpMyAdmin your table structure and copy/paste
-- this export here.
-- Note that the database itself and the standard tables ("global", "stats", "gamelog" and "player") are
-- already created and must not be created here

-- Note: The database schema is created from this file when the game starts. If you modify this file,
--       you have to restart a game to see your changes in database.

-- Example 1: create a standard "card" table to be used with the "Deck" tools (see example game "hearts"):

CREATE TABLE IF NOT EXISTS `token` (
  `token_key` varchar(32) NOT NULL,
  `token_location` varchar(32) NOT NULL,
  `token_state` int(10),
  PRIMARY KEY (`token_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- CREATE TABLE IF NOT EXISTS `token` (
--   `token_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
--   `token_type` varchar(16) NOT NULL,
--   `token_type_arg` int(11) NOT NULL,
--   `token_owner` varchar(16) NOT NULL DEFAULT  '',
--   `token_location` varchar(16) NOT NULL,
--   `token_location_arg` int(11) NOT NULL,
--   `token_location_owner` varchar(16) NOT NULL DEFAULT  '',
--   PRIMARY KEY (`token_id`)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


-- Example 2: add a custom field to the standard "player" table
-- ALTER TABLE `player` ADD `player_my_custom_field` INT UNSIGNED NOT NULL DEFAULT '0';

