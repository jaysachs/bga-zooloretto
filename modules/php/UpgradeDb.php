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

use Bga\GameFramework\GamestateMachine;
use Bga\GameFramework\Table;
use Bga\Games\zooloretto\Model\TileType;
use Bga\Games\zooloretto\Utils\Arrays;
use Bga\Games\zooloretto\Utils\Db;

class UpgradeDb {

    public function __construct(private Db $db, private GamestateMachine $gsm, private Table $table) { }

    public function upgrade(int $from_version): void {
        if ($from_version <= 2504011715) {
            $this->upgrade_2504011715();
        }
        if ($from_version <= 2605222008) {
            $this->upgrade_2605222008();
        }
    }

    /** @param list<string> $sql */
    private function applySql(array $sql): void {
        foreach ($sql as $s) {
            $this->table->applyDbUpgradeToAllDB($s);
        }
    }

    private function upgrade_2605222008(): void {
        // This just attempts a surgical fix to table 848677595
        //   which got an extra offspring (bugid:221919)
        // There is no way to match table IDs, though, so we do this
        //   heuristically looking for characteristics of the problem:
        //   1. player ID 86043869
        //   2. enclosure 3 has 4 elephants:
        //      female 321, male 320, offspring 113 and offspring 3210320
        //  If it matches this, we just delete tile 3210320

        $tgtPlayer = 86043869;
        $players = $this->db->getSingleFieldList("SELECT player_id FROM player ORDER BY player_id");
        if ($players != ["84921464", "86043119", "86043869"]) {
            return;
        }
        $rows = $this->db->getSingleFieldList("SELECT id FROM tiles WHERE location = 'E' and loc_id = 3 and player_id = {$tgtPlayer} ORDER BY loc_pos");
        if ($rows <> ["321", "320", "113", "3210320"]) {
            return;
        }
        $this->applySql(['DELETE FROM DBPREFIX_tiles WHERE id = 3210320']);
    }

	private function upgrade_2504011715(): void {
        $sql = [];
        $sql[] = "CREATE TABLE DBPREFIX_tiles (
                `id` INT UNSIGNED NOT NULL,
                `type` VARCHAR(10) NOT NULL,
                `location` VARCHAR(3) NOT NULL,
                `player_id` INT UNSIGNED NOT NULL DEFAULT 0,
                `loc_id` INT UNSIGNED NOT NULL DEFAULT 0,
                `loc_pos` INT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                UNIQUE(`location`, `player_id`, `loc_id`, `loc_pos`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $sql[] = "ALTER TABLE DBPREFIX_player ADD COLUMN `purchased_extensions` INT UNSIGNED NOT NULL DEFAULT 0";
        $sql[] = "ALTER TABLE DBPREFIX_player ADD COLUMN `truck_taken` INT UNSIGNED NOT NULL DEFAULT 0";

        $sql[] = "UPDATE DBPREFIX_player SET purchased_extensions = unblockedzoo";

        $players = $this->db->getObjectList("SELECT * FROM player");
        $is2p = count($players) == 2;
        $barnId = $is2p ? 6 : 5;
        $wagons = $this->db->getObjectList("SELECT * FROM wagons");
        $animals = $this->db->getObjectList("SELECT * FROM animals");
        // FIXME: can we use getActivePlayerId ?
        $gs = $this->db->getSingleFieldList("SELECT global_value FROM global WHERE global_id = 2");
        $active_player_id = intval($gs[0]);

        Arrays::shuffle($animals);

        $stockpos = 1;
        $barnpos = [];
        foreach ($players as $p) {
            $barnpos[intval($p["player_id"])] = 1;
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
        if ($is2p) {
            // Need to add "blocks" into 2p trucks.
            $values = [];
            $blockid = 1000;
            $type = TileType::BLOCK->value;
            $values[] = "($blockid, '$type', 'T', 0, 2, 3)";
            $blockid++;
            $values[] = "($blockid, '$type', 'T', 0, 3, 2)";
            $blockid++;
            $values[] = "($blockid, '$type', 'T', 0, 3, 3)";
            $sql[] = "INSERT INTO DBPREFIX_tiles
                      (id, type, location, player_id, loc_id, loc_pos)
                      VALUES " . implode(",", $values);
        }
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
                //  with positions:
                //   1: enc1 / pos6
                //   2: enc2 / pos5
                //   3: enc2 / pos6
                //   4: enc3 / pos7
                //   5: enc4 / pos6
                //   6: enc5 / pos6
                if ($loc_id == $barnId) {
                    $loc_id = $loc_pos > 2 ? $loc_pos - 1 : $loc_pos;
                    $loc_pos = match ($loc_pos) {
                        2 => 5,
                        4 => 7,
                        default => 6
                    };
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
            $sql[] = "INSERT INTO DBPREFIX_tiles
                      (id, type, location, player_id, loc_id, loc_pos)
                      VALUES ($id, '$type', '$location', $player_id, $loc_id, $loc_pos)";
        }

        // Now set truck_taken on player as needed. We don't know which one
        //  each player took, but we know if they took one.
        $taken = [];
        foreach ($wagons as $w) {
            if ($w["status"] == "PLAYED") { // "TAKEN"
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

        $this->applySql($sql);

        $currentState = $this->gsm->getCurrentMainStateId();
        if ($currentState == 5 || $currentState == 7 || $currentState == 8
            || $currentState == 9 || $currentState == 10) {
            $this->gsm->jumpToState(2);
        } else if ($currentState == 3) {
            $this->gsm->jumpToState(23);
        }
	}
}
