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
  space: Space;
  tile: Tile | undefined;
}

export interface EnclosureSummary {
  player_id: number;
  enclosure_id: number;
  animal_type: string;
  count: number;
}

export interface ZGamedatas extends Gamedatas<ZPlayer> {
  primary_pile_size: number;
  endgame_pile_size: number;
  lastround: boolean;
  drawntile: Tile | undefined;
  bank_money: number;
  // Should always be 3.
  trucks: Truck[];
  // keyed by player_id
  enclosures: EnclosureContents[][];
  endScores: any;

  // name is translated/able
  tile_translations: { type: string, name: string }[];
  enclosure_summaries: EnclosureSummary[];
}

export interface Space {
  enclosure_id: number;
  pos: number;
}
