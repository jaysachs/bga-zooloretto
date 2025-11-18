
-- ------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- Zooloretto implementation : © Jay Sachs vagabond@covariant.org
--
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----


-- For offspring, we'll add a new one starting at id 200.
CREATE TABLE IF NOT EXISTS `tiles` (
  `id` int(10) unsigned NOT NULL,
  -- The tile type:
  --  C: camel generic
  --  CM: camel male
  --  CF: camel female
  --  CK: camel kid
  --    similar for
  --  E: elephant
  --  F: flamingo
  --  K: kangaroo
  --  L: leopard
  --  M: monkey
  --  P: panda
  --  Z: zebra
  --  StallA, StallB, StallC, StallD: Kiosk, Barrow, Snacks, Popcorn
  --  COIN: coin
  `type` varchar(10) NOT NULL,
  `reproduced` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `trucks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  -- which player id took it, or 0 if still available
  `taken_by` int(10) NOT NULL DEFAULT 0,
  -- NULL is empty; 0 is "blocked off"
  `tile_id1` int(10) unsigned NOT NULL DEFAULT 0,
  `tile_id2` int(10) unsigned NOT NULL DEFAULT 0,
  `tile_id3` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `primary_stock` (
  `tile_id` int(10) unsigned NOT NULL,
  `seq_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`seq_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `endgame_stock` (
  `tile_id` int(10) unsigned NOT NULL,
  `seq_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`seq_id`)
)  ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `enclosure_contents` (
  `player_id` int(10) unsigned NOT NULL,
  -- 0 is barn
  `enclosure_id` int(10) unsigned NOT NULL,
  `pos` int(10) NOT NULL,
  `tile_id` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`player_id`, `enclosure_id`, `pos`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- For players, we need (a) money and (b) how many extensions were bought
ALTER TABLE `player` ADD COLUMN `money` int(10) unsigned NOT NULL DEFAULT 0;
ALTER TABLE `player` ADD COLUMN `purchased_extensions` int(10) unsigned NOT NULL DEFAULT 0;
