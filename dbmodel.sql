
-- ------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- Zooloretto implementation : © <Your name here> <Your email address here>
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

-- CREATE TABLE IF NOT EXISTS `card` (
--   `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
--   `card_type` varchar(16) NOT NULL,
--   `card_type_arg` int(11) NOT NULL,
--   `card_location` varchar(16) NOT NULL,
--   `card_location_arg` int(11) NOT NULL,
--   PRIMARY KEY (`card_id`)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


-- Example 2: add a custom field to the standard "player" table
-- ALTER TABLE `player` ADD `player_my_custom_field` INT UNSIGNED NOT NULL DEFAULT '0';

CREATE TABLE IF NOT EXISTS `animals` (
  -- if over 300, then a parent of offspring, original is id-300
  `id` int(10) NOT NULL,
  -- only used as part of selecting random tile to draw??
  `idsel` int(10) NULL,
  -- seems unused?
  `idorder` int(10) NULL,
  `player_id` int(10) unsigned NULL,
  -- the tile type:
  --  C: camel generic
  --  CM: camel male
  --  CF: camel female
  --  CK: camel kid
  --  E: elephant
  --  F: flamingo
  --  K: kangaroo
  --  L: leopard
  --  M: monkey
  --  P: panda
  --  Z: zebra
  --  COIN
  --  StallA, StallB, StallC, StallD: Kiosk, Barrow, Snacks, Popcorn
  `val` varchar(32) NULL,
  -- status can be
  --   AVAILABLE: in "main" deck
  --   DRAWN: turned over but not fully placed yet
  --   WAGON: in a wagon
  --   DISCARDED: discards out of the game
  --   PLAYED: if in an enclsoure field
  --   STALL: confusingly, in a barn
  --   LASTSET: not in "deck" but part of last 15
  -- I think these two are kind of "pending" births?
  --   THIKINGKID: ???!??
  --   THIKINGKIDSTALL: ??????
  `status` varchar(32) NULL,
  -- x can be
  --    enclosure number/id, 0 if in STALL
  `x` int(10) NOT NULL,
  -- y can be
  --    spot in enclosure
  `y` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `wagons` (
  `id` int(10) NOT NULL,
  `size` int(10) NOT NULL,
  `val1` varchar(32) NULL,
  `val2` varchar(32) NULL,
  `val3` varchar(32) NULL,
  -- status can be
  --   AVAILABLE: if has not been taken by a player
  --   TAKEN: if a player took it but not confirmed arrangement of contents
  --   PLAYED: confirmed placement of contents
  `status` varchar(32) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- how many coins
ALTER TABLE `player` ADD COLUMN `money` int(10) ;
-- how many expansion boards have been bought & flipped
-- This is awkward. It would be better as "available expansions".
ALTER TABLE `player` ADD COLUMN `unblockedzoo` int(10) ;
-- 'Y' means they've taken a wagon this roundm 'N' otherwise.
ALTER TABLE `player` ADD COLUMN `skipped` varchar(32);
-- No longer used.
ALTER TABLE `player` ADD COLUMN `lastround` varchar(32);
