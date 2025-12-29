
-- ------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- Zooloretto implementation : © Jay Sachs vagabond@covariant.org
--
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----


CREATE TABLE IF NOT EXISTS `tiles` (
  `id` INT(10) UNSIGNED NOT NULL,
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
  `type` VARCHAR(10) NOT NULL,
  `reproduced` TINYINT(1) NOT NULL DEFAULT 0,

  -- Truck
  --  T:  {truck_id} {truck_pos}
  -- Enclosure
  --  E:  {player_id} {enc_id} {enc_pos}
  -- Stock
  --  S:  {stock_pos}
  -- Drawn
  --  D
  -- FIXME: could add a status for deleted/ offboard / out of game / in box
  -- but then require a unique pos to maintain uniqueness constraint.
  -- Could do this with two queries, grabbing a max
  --  X: {salt_pos}
  `location` VARCHAR(1) NOT NULL,
  -- ignored unless E
  `player_id` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  -- truck_id if T, enc_id if E
  `loc_id` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  -- truck_pos if T, enc_pos if E, stock_pos if S
  `loc_pos` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  -- Requires uniquifying deleted / temporary positions
  --  we can use id offsets to accomplish this
  UNIQUE(`location`, `player_id`, `loc_id`, `loc_pos`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `zglobals` (
  `id` int(10) unsigned NOT NULL DEFAULT 0,
  `bank_money` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `zglobals` (`bank_money`) VALUES(30);

ALTER TABLE `player` ADD COLUMN `money` int(10) unsigned NOT NULL DEFAULT 0;
ALTER TABLE `player` ADD COLUMN `purchased_extensions` int(10) unsigned NOT NULL DEFAULT 0;
ALTER TABLE `player` ADD COLUMN `truck_taken` int(10) unsigned;
