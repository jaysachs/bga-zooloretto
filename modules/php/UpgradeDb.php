<?php

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * Zooloretto implementation : © Jay Sachs <vagabond@covariant.org>
 *
 * Copyright 2025 Jay Sachs <vagabond@covariant.org>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 */

declare(strict_types=1);

namespace Bga\Games\zooloretto;

use Bga\Games\zooloretto\Model\Db;

class UpgradeDb {

    public function __construct(private Db $db) { }

	public function upgradeSql(int $from_version): ?string {
        if ($from_version > 2504011715) {
            return null;
        }
        $sql = "CREATE TABLE DBPREFIX_tiles (
                `id` INT(10) UNSIGNED NOT NULL,
                `type` VARCHAR(10) NOT NULL,
                `reproduced` TINYINT(1) NOT NULL DEFAULT 0,
                `location` VARCHAR(1) NOT NULL,
                `player_id` INT(10) UNSIGNED NOT NULL DEFAULT 0,
                `loc_id` INT(10) UNSIGNED NOT NULL DEFAULT 0,
                `loc_pos` INT(10) UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                UNIQUE(`location`, `player_id`, `loc_id`, `loc_pos`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

                CREATE TABLE DBPREFIX_zglobals (
                `id` int(10) unsigned NOT NULL DEFAULT 0,
                `bank_money` int(10) unsigned NOT NULL,
                PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

                INSERT INTO DBPREFIX_zglobals (`bank_money`) VALUES(30);

                ALTER TABLE DBPREFIX_player ADD COLUMN `money` int(10) unsigned NOT NULL DEFAULT 0;
                ALTER TABLE DBPREIFX_player ADD COLUMN `purchased_extensions` int(10) unsigned NOT NULL DEFAULT 0;
                ALTER TABLE DBPREFIX_player ADD COLUMN `truck_taken` int(10) unsigned;
";
        return null; // $sql;
	}
}
