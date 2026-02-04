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

use Bga\Games\zooloretto\Utils\Arrays;
use Bga\Games\zooloretto\Utils\Db;

class UpgradeDb {

    public function __construct(private Db $db) { }

    /** @return list<string>|null */
	public function upgradeSql(int $from_version): ?array {
        if ($from_version > 2504011715) {
            return null;
        }
        if ($from_version >= 0) {
            return null;
        }
        $sql = [];
        $sql[] = "CREATE TABLE DBPREFIX_tiles (
                `id` INT(10) UNSIGNED NOT NULL,
                `type` VARCHAR(10) NOT NULL,
                `location` VARCHAR(1) NOT NULL,
                `player_id` INT(10) UNSIGNED NOT NULL DEFAULT 0,
                `loc_id` INT(10) UNSIGNED NOT NULL DEFAULT 0,
                `loc_pos` INT(10) UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                UNIQUE(`location`, `player_id`, `loc_id`, `loc_pos`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
        $sql[] ="CREATE TABLE DBPREFIX_zglobals (
                `id` int(10) unsigned NOT NULL DEFAULT 0,
                `delivering_truck` int(10) unsigned NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
        $sql[] = "ALTER TABLE DBPREFIX_player ADD COLUMN `purchased_extensions` int(10) unsigned NOT NULL DEFAULT 0";
        $sql[] = "ALTER TABLE DBPREFIX_player ADD COLUMN `truck_taken` int(10) unsigned";

        $sql[] = "UPDATE DBPREFIX_player SET purchased_extensions = unblockedzoo";

        $currentState = intval($this->db->getSingleFieldList("SELECT global_value FROM global WHERE global_id = 1")[0]);
        if (!$currentState) {
            throw new \Exception("Game should have already started but in state $currentState");
        }
        $players = $this->db->getObjectList("SELECT * FROM player");
        $wagons = $this->db->getObjectList("SELECT * FROM wagons");
        $animals = $this->db->getObjectList("SELECT * FROM animals");
        // FIXME: can we use getActivePlayerId ?
        $gs = $this->db->getSingleFieldList("SELECT global_value FROM `global` WHERE global_id = 2");
        $active_player_id = intval($gs[0]);

        Arrays::shuffle($animals);

        $stockpos = 1;
        $barnpos = [];
        foreach ($players as $p) {
            $barnpos[$p["player_id"]] = 0;
        }

        $pending_truck = 0;
        $available_truck_pos = [];
        foreach ($wagons as $wagon) {
            $wid = intval($wagon["id"]);
            if ($wagon["status"] == "TAKEN") {
                if ($pending_truck) {
                    throw new \Exception("Two trucks in pending state: $pending_truck $wid");
                }
                $pending_truck = $wid;
                $a = [];
                $size = intval($wagon["size"]);
                if (!$wagon["val1"]) {
                    $a[] = 1;
                }
                if (!$wagon["val2"] && $size >= 2) {
                    $a[] = 2;
                }
                if (!$wagon["val3"] && $size >= 3) {
                    $a[] = 3;
                }
                $available_truck_pos = $a;
            }
        }
        $values = [];
        foreach ($animals as $a) {
            $location = "";
            $player_id = 0;
            $loc_id = 0;
            $loc_pos = 0;
            $type = $a["val"];
            $id = intval($a["id"]);
            $status = $a["status"];
            switch ($status) {
            case "THIKINGKID":
            case "THIKINGKIDSTALL":
                continue 2;
            case "AVAILABLE":
                $location = "S";
                $loc_pos = $stockpos++;
                break;
            case "LASTSET":
                $location = "S";
                $loc_pos = count($animals) + 1 + $stockpos++;
                break;
            case "DISCARD":
            case "DISCARDED":
                $location = "X";
                $loc_pos = $id + 10000;
                break;
            case "WAGON":
                $location = "T";
                $loc_id = intval($a["x"]);
                $loc_pos = intval($a["y"]);
                break;
            case "DRAWN":
                $location = "D";
                break;
            case "PLAYED":
                $location = "E";
                $player_id = intval($a["player_id"]);
                $loc_id = intval($a["x"]);
                $loc_pos = intval($a["y"]);
                // Fix up stall locs; original has enclosure 6 for stalls
                if ($loc_id == 6) {
                    $loc_id = $loc_pos > 2 ? $loc_pos - 1 : $loc_pos;
                    $loc_pos = $loc_pos == 2 ? 5 : 6;
                }
                break;
            case "STALL": // means barn
                $location = "E";
                $player_id = intval($a["player_id"]);
                $loc_id = 0;
                $loc_pos = $barnpos[$player_id]++;
                break;
            case "THINKING":
                $location = "T";
                $loc_id = $pending_truck;
                if ($loc_id <= 0) {
                    throw new \Exception("no pending truck found but animal $id in status '$status'");
                }
                $loc_pos = array_pop($available_truck_pos);
                if ($loc_pos <= 0) {
                    throw new \Exception("Couldn't find spot in truck $loc_id for tile $id, '$status', '$type'");
                }
                break;
            default:
                throw new \Exception("Unknown status $status");
                // log error / throw exception
            }
            $values[] = "($id, '$type', '$location', $player_id, $loc_id, $loc_pos)";
        }

        $sql[] = "INSERT INTO DBPREFIX_tiles (id, type, location, player_id, loc_id, loc_pos) VALUES " . implode(",", $values);
        // Now set truck_taken on player as needed. We don't know which one
        //  each player took, but we know if they took one.
        $taken = [];
        foreach ($wagons as $w) {
            if ($w["status"] == "TAKEN") {
                $taken[] = intval($w["id"]);
            }
        }
        foreach ($players as $p) {
            $pid = intval($p["player_id"]);
            if ($p["skipped"] == "Y" && $pid != $active_player_id) {
                $t = array_pop($taken);
                $sql[] = "UPDATE DBPREFIX_player SET truck_taken = $t WHERE player_id = $pid";
            }
        }
        if ($currentState == 5 || $currentState == 7 || $currentState == 8
            || $currentState == 9 || $currentState == 10) {
            // FIXME: can we use jumpToState ?
            $sql[] = "UPDATE `global` SET `global_value` = 2 WHERE `global_id` = 1";
        }

        return $sql;
	}
}
