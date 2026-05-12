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

import { ZooFlow } from "./zflow";
import { Tile, TruckLocation } from "./zgametypes";
import { Elements, IDS } from "./zhtml";
import { GameView } from "./zview";

// LoadDrawnTile state

interface LoadDrawnTileArgs {
  tile: Tile,
  drawn_from_endgame_pile: boolean,
  available_spaces: TruckLocation[]
};

export class LoadDrawnTileFlow extends ZooFlow<LoadDrawnTileArgs> {
  constructor(gameView: GameView) { super(gameView); }

  protected override start(args: LoadDrawnTileArgs) {
    this.initStatusBar(_('Place ${tile_type} in an available truck'), { tile_type: args.tile.type });
    const elem = Elements.tile(args.tile)!; // was: Elements.drawnTile(args.drawn_from_endgame_pile);
    this.markSelected(elem);
    args.available_spaces.forEach((truckLoc: TruckLocation) =>
        this.addSelectableOnclick(Elements.truckSpace(truckLoc.truck_id, truckLoc.truck_pos),
          () => this.callUndoably("chooseTiletoPlace",
                () => this.placeDrawnTile(elem, args.tile, truckLoc))));
  }

  private placeDrawnTile(tileElem: HTMLElement, tile: Tile, truckLoc: TruckLocation): Promise<any> {
    const space = Elements.truckSpace(truckLoc.truck_id, truckLoc.truck_pos);
    return this.slide(tileElem, space).then(() => this.confirmLoadDrawnTile(tile, truckLoc));
  }

  private confirmLoadDrawnTile(tile: Tile, tl: TruckLocation) {
    this.initStatusBar(_('Place ${tile_type} in truck ${truck_id}?'),
        { tile_type: tile.type,
          truck_id: tl.truck_id });
    // FIXME: restart doesn't re-highlight the truck spaces.
    this.addConfirmAndRestartActionButtons('actLoadDrawnTile', tl);
  }

  private replenishPilesAndUpdateCounters(
    args: {
      drawn_from_endgame_pile: boolean,
      primary_pile_size: number,
      endgame_pile_size: number,
    }
  ): void {
    if (args.drawn_from_endgame_pile) {
      if (args.endgame_pile_size >= 5) {
        $(IDS.ENDGAME_PILE_TILES).insertAdjacentElement('afterbegin', this.view.makeTileBackSpan());
      }
    } else {
      if (args.primary_pile_size >= 5) {
        $(IDS.PRIMARY_PILE_TILES).insertAdjacentElement('afterbegin', this.view.makeTileBackSpan());
      }
    }
    this.view.updateStockCounters(args.primary_pile_size, args.endgame_pile_size);
  }

  private async notif_LoadDrawnTile(args: {
    player_id: number,
    truck_id: number,
    truck_pos: number,
    // FIXME: should we figure this out based on where tile is?
    drawn_from_endgame_pile: boolean,
    tile: Tile,
    primary_pile_size: number,
    endgame_pile_size: number }) {
      await this.view.moreAnimations.slideAndAttach(
        Elements.tile(args.tile)!,
        Elements.truckSpace(args.truck_id, args.truck_pos));
      this.replenishPilesAndUpdateCounters(args);
  }
};