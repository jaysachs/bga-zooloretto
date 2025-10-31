<?php

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * zooloretto implementation : © Jay Sachs <vagabond@covariant.org>
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

namespace Bga\Games\zooloretto\Model;

use \Bga\GameFramework\Table;

class Model {

    public function __construct(private Db $db = new DefaultDb()) {}

    public function createNewGame(int $player_count): void {
        // Deck (tiles)
		$deck = Deck::create($player_count);
		$make = function (Tile $tile, string $status): string {
			$tv = $tile->type->value;
			$id = $tile->id;
			return "($id,'','','','$tv','$status')";
		};
		$values = array_merge(
			array_map(function (Tile $tile) use (&$make): string { return $make($tile, 'AVAILABLE'); }, $deck->tiles),
			array_map(function (Tile $tile)use (&$make): string { return $make($tile, 'LASTSET'); }, $deck->lastset)
		);
		$this->db->execute("INSERT INTO animals (id, idsel, idorder, player_id, val, status) VALUES "
                           . implode(',', $values));

        // Wagons
		$values = [];
		if ($player_count != 2) {
			for ($x = 1; $x <= $player_count; $x++) {
				$values[] = "($x, 3, '', '', '', 'AVAILABLE')";
			}
		} else {
			$values[] = "(1, 3, '', '', '', 'AVAILABLE')";
			$values[] = "(2, 2, '', '', '', 'AVAILABLE')";
			$values[] = "(3, 1, '', '', '', 'AVAILABLE')";
		}
		$this->db->execute("INSERT INTO wagons (id, size, val1, val2, val3, status) VALUES " . implode(',', $values));

        // Extra player info
        $this->db->execute("UPDATE player SET money = 2, unblockedzoo = 0, skipped = 'N', lastround = 'N'");
    }

    /** @var Player[] */
    private ?array $_players = [];

    public function getPlayer(int $id): Player {
        if ($this->_players == null) {
            $this->_players = [];
            $data = $this->db->getObjectList("SELECT player_id, player_no, money, unblockedzoo FROM player");
            foreach ($data as $row) {
                $id = intval($row["player_id"]);
                $this->_players[$id] = new Player($id, intval($row["player_no"]), intval($row["money"]), intval($row["unblockedzoo"]));
            }
        }
        if (isset($this->_players[$id])) {
            return $this->_players[$id];
        }
        throw new \Exception("attempt to retrieve unknown player $id");
    }

    public function updatePlayer(int $id): void {
        if ($this->_players == null) {
            throw new \Exception("attempt to update player when none fetched");
        }

        if (! isset($this->_players[$id])) {
            throw new \Exception("attempt to update player $id that was not found");
        }
        $player = $this->_players[$id];
        $ubz = $player->available_enclosures;
        $money = $player->money;
        $id = $player->id;
		$this->db->execute( "UPDATE player SET unblockedzoo = $ubz, money = $money WHERE player_id = $id" );
    }

    private ?Deck $_deck = null;

    public function getDeck(): Deck {
        if ($this->_deck == null) {
            $rows = $this->db->getObjectList("SELECT id, val, status FROM animals");
            $ts = [];
            $ls = [];
            $drawn = null;
            foreach ($rows as $row) {
                $status = $row["status"];
                $tile = new Tile(intval($row["id"]), TileType::from($row["val"]));
                if ($status == 'AVAILABLE') {
                    $ts[] = $tile;
                } else if ($status == "LASTSET") {
                    $ls[] = $tile;
                } else if ($status == "DRAWN") {
                    if ($drawn != null) {
                        throw new \BgaUserException("Found multiple drawn tiles!");
                    }
                    $drawn = $tile;
                } else {
                    // it's in a wagon, or on a playerboard somewhere.
                }
            }
            $this->_deck = new Deck($ts, $ls, $drawn);
        }
        return $this->_deck;
    }

    public function updateDeck(): void {
        $deck = $this->_deck;
        if ($deck == null) {
            return;
        }
        // FIXME: Come back and re-evaulate this approach.
        // As long as all mutations go through model-owned objects, we can handle this.
        // For now, we only handle newly-drawn tile updates.
        if ($deck->drawn == null) {
            return;
        }
        $id = $deck->drawn->id;
        $this->db->execute("UPDATE animals SET status = 'DRAWN' WHERE id = $id");
    }
}
