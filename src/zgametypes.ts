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

//
// interfaces for gamedatas
//

export interface ZPlayer extends Player {
  player_id: number;
  money: number;
  purchased_extensions: number;
}

export interface Tile {
  id: number;
  type: string;
}

export interface TruckSpace {
  pos: number;
  tile: Tile | undefined;
}

export interface Truck {
  taken_by_player_id: number | null;
  truck_id: number;
  // Should always be 3. null means empty.
  // FIXME: Need to be careful about 0- and 1- based; probably best to be consistent
  //   and use "null" for "nothing" and 0-based.
  contents: TruckSpace[];
}

export interface EnclosureContents {
  space: number;
  tile: Tile | undefined;
}

export interface EnclosureSummary {
  player_id: number;
  enclosure_id: number;
  tile_type: string;
  count: number;
}

export interface EnclosureShape {
  id: number;
  animal_capacity: number;
  stall_capacity: number;
  extension: number | null;
}

export interface ZGamedatas extends Gamedatas<ZPlayer> {
  enclosure_shapes: EnclosureShape[];
  primary_pile_size: number;
  endgame_pile_size: number;
  lastround: boolean;
  drawntile: Tile | undefined;
  // Should always be 3.
  trucks: Truck[];
  // keyed by player_id
  enclosures: EnclosureContents[][];
  endScores?: any;

  // keyed by player_id
  enclosure_summaries: EnclosureSummary[];

  tile_translations: Record<string, string>;
}

export interface PlacedTile {
  tile: Tile;
  space: number;
  completion_coins: number | null;
  offspring: Offspring | null;
}

export interface Offspring {
  placed_tile: PlacedTile;
  mother: Tile;
  father: Tile;
}

export type Moneys = { [playerId: number]: number };

export interface TruckLocation {
  truck_id: number,
  truck_pos: number
}

export interface CompletedDelivery {
  truck_pos: number;
  placed_tile: PlacedTile;
}
