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

namespace Bga\Games\zoolorettoalpha\Model;

use Bga\Games\zoolorettoalpha\Utils\Arrays;

class Exchanges implements Serializable {

	/**
	 * @param array<int,list<int>> $animal_positions key: encid; val: positions with animals
	 * @param array<int,list<int>> $enclosures key: encid; val: other enclosures
	 * @param array<int,list<BarnExchange>> $barn  key: encid; val: BarnExchange
	 */
	public function __construct(
		// FIXME: could also include enclosure filled animal positions.
		public private(set) array $animal_positions,
		public private(set) array $enclosures,
		public private(set) array $barn) {}

	/** @return array<string,mixed> */
	public function serialize(): array {
		$barns = [];
		foreach ($this->barn as $e => $b) {
			$barns[$e] = array_map(fn (BarnExchange $be) => $be->serialize(), $b);
		}
		return [
			'animal_positions' => $this->animal_positions,
			'enclosures' => $this->enclosures,
			'barn' => $barns,
		];
	}

	/** @param array<Enclosure> $enclosures*/
	public static function forEnclosures(array $enclosures): Exchanges {
		$ex = [];
		$bx = [];
		$occ = [];
		$animals = [];
		foreach ($enclosures as $enc) {
			$animals[$enc->id] = $enc->filledAnimalPositions();
			$occ[$enc->id] = count($animals[$enc->id]);
		}
		/** @var Enclosure|null */
		$barn = null;
		// non-barn first
		foreach ($enclosures as $senc) {
			$sid = $senc->id;
			if ($sid == 0) {
				$barn = $senc;
				continue;
			}
			if ($occ[$sid] == 0) {
				continue;
			}
			foreach ($enclosures as $denc) {
				$did = $denc->id;
				if ($did == 0 || $did == $sid) {
					continue;
				}
				if ($occ[$did] == 0) {
					continue;
				}
				if ($occ[$did] > $senc->animal_capacity || $occ[$sid] > $denc->animal_capacity) {
					continue;
				}
				if ($senc->animalType() == $denc->animalType()) {
					continue;
				}
				if (!isset($ex[$sid])) {
					$ex[$sid] = [];
				}
				$ex[$sid][] = $did;
			}
		}
		if ($barn == null) {
			// throw new ModelException("no barn supplied in enclosures");
			return new Exchanges($animals, $ex, $bx);
		}

		// now barn
		foreach ($enclosures as $enc) {
			if ($enc->id == 0) {
				continue;
			}
			foreach (TileType::allCanonicalAnimals() as $animalType) {
				$barn2 = $barn->clone();
				if ($enc->animalType()->isSameSpecies($animalType)) {
					continue;
				}
		        $dest_pos = $barn2->filledAnimalPositions($animalType);
		        if (count($dest_pos) == 0) {
					continue;
		        }
				if (count($dest_pos) > $enc->animal_capacity) {
					continue;
		        }

				// now extend barn pos with empty spots to accomodate extra.
				// figure this BEFORE offspring possibly added to barn.
				$extra_needed = count($enc->filledAnimalPositions()) - count($dest_pos);
				while ($extra_needed-- > 0) {
					$dest_pos[] = $barn2->placeTile(new Tile(100000, TileType::CAMEL))->space->pos;
				}

				$offspring = self::checkOffspring($enc, $barn2, $dest_pos);

				if (!isset($bx[$enc->id])) {
					$bx[$enc->id] = [];
				}
				$bx[$enc->id][] = new BarnExchange($dest_pos, $offspring);
			}
		}
		return new Exchanges($animals, $ex, $bx);
	}

	/**
	 * @param list<int> $barn_pos
	 */
	private static function checkOffspring(Enclosure $enc, Enclosure $barn, array $barn_pos): ?Offspring {
		$enc = $enc->clone();
		$barn = $barn->clone();
		$tiles = [];
		foreach ($enc->filledAnimalPositions() as $apos) {
			$tiles[] = $enc->takeTileAt($apos);
		}
		foreach ($barn_pos as $bpos) {
			$tile = $barn->takeTileAt($bpos);
			$enc->placeTile($tile);
		}
		foreach ($tiles as $tile) {
			$barn->placeTile($tile);
		}
		// FIXME: need to check overflow!
		return $enc->checkForOffspring($barn);
	}
}

class BarnExchange implements Serializable{
	/**
	 * @param list<int> $positions set of barn positions getting exchanged
	 * @param Offspring $offspring potential offspring from the exchange
	 */
	public function __construct(
		public private(set) array $positions,
		public private(set) ?Offspring $offspring = null) { }

	/** @return array<string,mixed> */
	public function serialize(): array {
		$result = [
			'positions' => $this->positions,
		];
		if ($this->offspring) {
			$result['offspring'] = $this->offspring->serialize();
		}
		return $result;
	}
}
