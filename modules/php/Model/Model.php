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

/*
  Basic guideline: all mutations are done through Model public methods. While it may return modeled objects with mutable fields,
  mutations should not be made directly on those objects.
*/

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

    /** @var Wagon[] */
    private ?array $_wagons = [];

    /** @returns Wagon[] */
    public function getWagons(): array
    {
        if ($this->_wagons == null) {
            $this->_wagons = [];
            $data = $this->db->getObjectList("SELECT id, size, val1, val2, val3, status FROM wagons");
            // FIXME: would prefer to join in the Tiles table but then need a wagon_contents table.
            foreach ($data as $row) {
                $id = intval($row["id"]);
                $contents = [];
                $in_clause = implode(',', array_filter(
                    [$row["val1"], $row["val2"], $row["val3"]],
                    function (string $v): bool {
                        return intval($v) > 0;
                    }
                ));
                if ($in_clause > "") {
                    $wdata = $this->db->getObjectList("SELECT id, val, x, y FROM animals WHERE id IN ($in_clause)");
                    foreach ($wdata as $wrow) {
                        $contents[] = $this->tileFromDataRow($wrow);
                    }
                }
                $this->_wagons[$id] = new Wagon($id, intval($row["size"]), $contents, WagonStatus::from($row["status"]));
            }
        }
        return $this->_wagons;
    }

    private function tileFromDataRow(array $row): Tile {
        return new Tile(intval($row["id"]), TileType::from($row["val"]), intval($row["x"]), intval($row["y"]));
    }

    public function getWagon(int $id): Wagon
    {
        $wagons = $this->getWagons();
        if (isset($wagons[$id])) {
            return $wagons[$id];
        }
        throw new \Exception("attempt to retrieve unknown wagon $id");
    }

    /** @var Player[] */
    private ?array $_players = [];

    /** @returns Player[] */
    public function getPlayers(): array {
        if ($this->_players == null) {
            $this->_players = [];
            $data = $this->db->getObjectList("SELECT player_id, player_no, money, unblockedzoo, skipped FROM player");
            $numPlayers = count($data);
            $potentialExtensions = $numPlayers == 2 ? 2 : 1;
            foreach ($data as $row) {
                $id = intval($row["player_id"]);
                $purchased_extensions = intval($row["unblockedzoo"]);
                $this->_players[$id] = new Player($id, intval($row["player_no"]), intval($row["money"]), $potentialExtensions - $purchased_extensions, $purchased_extensions, ($row["skipped"] == "Y"));
            }
        }
        return $this->_players;
    }

    public function getPlayer(int $id): Player {
        $players = $this->getPlayers();
        if (isset($players[$id])) {
            return $players[$id];
        }
        throw new \Exception("attempt to retrieve unknown player $id");
    }

    private function updatePlayer(Player $player): void {
        $ubz = $player->purchased_extensions;
        $money = $player->money;
        $id = $player->id;
        $wagon_taken = $player->wagon_taken ? 'Y' : 'N';
		$this->db->execute( "UPDATE player SET unblockedzoo = $ubz, money = $money, skipped = '$wagon_taken' WHERE player_id = $id" );
    }

    private ?Deck $_deck = null;

    private function getDeck(): Deck {
        if ($this->_deck == null) {
            $rows = $this->db->getObjectList("SELECT id, val, x, y, status FROM animals");
            $ts = [];
            $ls = [];
            $drawn = null;
            foreach ($rows as $row) {
                $status = $row["status"];
                $tile = $this->tileFromDataRow($row);
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

    public function drawTile(): Deck {
        $deck = $this->getDeck();
        $deck->drawTile();
        $this->updateDeck();
        return $deck;
    }

    private function updateDeck(): void {
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

    public function wasLastRoundTriggered(): bool {
        return $this->getDeck()->wasLastRoundTriggered();
    }

    public function inLastRound(): bool {
        return $this->getDeck()->inLastRound();
    }

    public function prepareNextTurn() {
		$this->db->execute( "UPDATE animals SET status = 'DISCARDED' WHERE status = 'WAGON'" );
        $available = WagonStatus::AVAILABLE->value;
		$this->db->execute( "UPDATE wagons SET status = '$available', val1='', val2='', val3=''" );
		$this->db->execute( "UPDATE player SET skipped='N'" );

        $this->_players = null;
        $this->_deck = null;
    }

    private function doUpdateWagon(Wagon $wagon) {
        $id = $wagon->id;
        $status = $wagon->status->value;
        $val1 = $wagon->valAt(0);
        $val2 = $wagon->valAt(1);
        $val3 = $wagon->valAt(2);
		$this->db->execute( "UPDATE wagons SET status = '$status', val1='$val1', val2='$val2', val3='$val3'  WHERE id = $id" );
    }

    public function takeWagon(Player $player, int $wagon_id): Wagon {
        $this->validatePlayer($player);
        $wagon = $this->getWagon($wagon_id);

        $player->takeWagon();
        $this->updatePlayer($player);
        $wagon->setTaken();
        $this->doUpdateWagon($wagon);
        return $wagon;
    }

    private function getBarnFor(Player $player): Barn {
        $rows = $this->db->getObjectList("SELECT id, val, x, y FROM animals WHERE status = 'STALL' and player_id = $player->id");
        /** @var Tile[] */
        $tiles = [];
        foreach ($rows as $row) {
            $tiles[] = $this->tileFromDataRow($row);
        }
        return new Barn($player->id, $tiles);
    }

    private function doUpdateBarn(Barn $barn): void {
        $in_clause = implode(',', array_map(function (Tile $tile): string { return strval($tile->id); }, $barn->discarded));
        $this->db->execute("UPDATE animals SET x = 0, y = 0. player_id = 0, status = 'DISCARD' WHERE id IN ($in_clause)");
    }

    private function validatePlayer(Player $player): void {
        if ($player !== $this->getPlayer($player->id)) {
            throw new \Exception("Got unknown player");
        }
    }

    public function discardBarnTile(Player $player, int $tileid): Tile {
        $this->validatePlayer($player);
        $barn = $this->getBarnFor($player);
        $tile = $barn->discard($tileid);
        $this->doUpdateBarn($barn);

        return $tile;
    }

    public function buyEnclosure(Player $player): void {
        $this->validatePlayer($player);
		$player->buyEnclosure();
		$this->updatePlayer($player);
    }
}
