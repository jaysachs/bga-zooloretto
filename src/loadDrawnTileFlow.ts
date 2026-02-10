import { ZooFlow } from "./zflow";
import { Tile, TruckLocation } from "./zgametypes";
import { Elements } from "./zhtml";
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
    this.initStatusBar(_('Place ${tile_type} in an available truck'),
        { tile_type: args.tile.type,
          tile_description: _(args.tile.description) });
    const elem = Elements.tile(args.tile)!; // was: Elements.drawnTile(args.drawn_from_endgame_pile);
    this.markSelected(elem);
    args.available_spaces.forEach((truckLoc: TruckLocation) =>
        this.addSelectableOnclick(Elements.truckSpace(truckLoc.truck_id, truckLoc.truck_pos),
          () => this.callUndoably("chooseTiletoPlace",
                () => this.placeDrawnTile(elem, args.tile, truckLoc))));
  }

  private async placeDrawnTile(tileElem: HTMLElement, tile: Tile, truckLoc: TruckLocation) {
    const space = Elements.truckSpace(truckLoc.truck_id, truckLoc.truck_pos);
    this.slide(tileElem, space).then(() => this.confirmLoadDrawnTile(tile, truckLoc));
  }

  private confirmLoadDrawnTile(tile: Tile, tl: TruckLocation) {
    this.initStatusBar(_('Place ${tile_type} in truck ${truck_id}?'),
        { tile_type: tile.type,
          tile_description: _(tile.description),
          truck_id: tl.truck_id });
    // FIXME: restart doesn't re-highlight the truck spaces.
    this.addConfirmAndRestartActionButtons('actLoadDrawnTile', tl);
  }
};