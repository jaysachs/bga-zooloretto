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
  description: string;
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
  animal_type: string;
  animal_description: string;
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

export interface Delivery {
  truck_pos: number;
  placed_tile: PlacedTile;
}
