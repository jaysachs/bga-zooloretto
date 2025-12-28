
-- ------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- Zooloretto implementation : © Jay Sachs vagabond@covariant.org
--
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----


-- For offspring, we'll add a new one starting at id 300.
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
  -- NULL is empty; 0 is "blocked off"
  `tile_id1` int(10) unsigned NOT NULL DEFAULT 0,
  `tile_id2` int(10) unsigned NOT NULL DEFAULT 0,
  `tile_id3` int(10) unsigned NOT NULL DEFAULT 0,
  -- FIXME: this requires null take_by
  FOREIGN KEY (`tile_id1`) REFERENCES `tiles`(`id`),
  FOREIGN KEY (`tile_id2`) REFERENCES `tiles`(`id`),
  FOREIGN KEY (`tile_id3`) REFERENCES `tiles`(`id`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE IF NOT EXISTS `stock` (
  `tile_id` int(10) unsigned NOT NULL,
  `seq_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  -- ideally we'd set a constraint on this saying only one row could have it.
  `drawn` int(1) unsigned,
  FOREIGN KEY (`tile_id`) REFERENCES `tiles`(`id`),
  -- FIXME: would like an index on tile_id since we update/delete by it.
  PRIMARY KEY (`seq_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `zglobals` (
  `id` int(10) unsigned NOT NULL DEFAULT 0,
  `bank_money` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `zglobals` (`bank_money`) VALUES(30);

CREATE TABLE IF NOT EXISTS `enclosure_contents` (
  `player_id` int(10) unsigned NOT NULL,
  -- 0 is barn
  `enclosure_id` int(10) unsigned NOT NULL,
  `pos` int(10) unsigned NOT NULL,
  `tile_id` int(10) unsigned NOT NULL,
  FOREIGN KEY (`tile_id`) REFERENCES `tiles`(`id`),
  FOREIGN KEY (`player_id`) REFERENCES `player`(`player_id`),
  PRIMARY KEY (`player_id`, `enclosure_id`, `pos`),
  -- this means we can't insert EMPTY, which is fine.
  UNIQUE (`tile_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `player` ADD COLUMN `money` int(10) unsigned NOT NULL DEFAULT 0;
ALTER TABLE `player` ADD COLUMN `purchased_extensions` int(10) unsigned NOT NULL DEFAULT 0;
ALTER TABLE `player` ADD COLUMN `truck_taken` int(10) unsigned;
ALTER TABLE `player` ADD FOREIGN KEY (`truck_taken`) REFERENCES `trucks`(`id`);
