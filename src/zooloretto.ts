import { Html } from './html';
import { Tile, ZGamedatas, Space, EnclosureSummary } from './zgametypes';
import { PlayFlow, FlowState } from './flow';
import { BaseGame } from './basegame';
import { CSS, IDS, Elements, ZoolorettoHtml, Attrs } from './zhtml';
import { AnimationList, MoreAnimations } from './more-animations';
import { BgaScoreSheet, ScoreSheet } from './libs';

//
// interfaces for args & notifs
//

// general use

interface PlacedTile {
  tile: Tile;
  space: Space;
  money_delta: Moneys | null;
  completed_enclosure: boolean;
}

interface Offspring {
  placed_tile: PlacedTile;
  mother: Tile;
  father: Tile;
}

type Moneys = { [playerId: number]: number };

interface Destination {
  space: Space;
  offspring: Offspring;
  money_delta: Moneys | null;
}

// FIXME: is this useful?
interface TruckLocation {
  truck_id: number,
  truck_pos: number
};


// LoadDrawnTile state

interface LoadDrawnTileArgs {
  tile: Tile,
  drawn_from_endgame_pile: boolean,
  available_spaces: TruckLocation[]
};



// PlayerTurn state

interface PossibleMove {
  src_player_id: number;
  src: Space;
  money_delta: Moneys | null;
  dests: Destination[];
}

interface PossibleExchange {
  src: Space[];
  dest: Space[];
  offspring: Offspring[];
  money_delta: Moneys;
}

interface PossibleDiscard {
  spaces: Space[];
  money_delta: Moneys | null;
}

interface PlayState {
  lastround: boolean;
  can_draw: boolean;
  extension_available: number;
  available_trucks: number[];
  possible_discards: PossibleDiscard;
  possible_moves: PossibleMove[];
  possible_exchanges: PossibleExchange[];
  possible_purchases: PossibleMove[];
}

// notif_TakeTruckAndPlaceTiles

interface Delivery {
  truck_pos: number;
  tile: Tile;
  dest: {
    space: Space;
    offspring: Offspring | undefined;
  } | undefined;
}

//
// UI flows
//
abstract class ZooFlow<T = undefined> extends PlayFlow<T> {

  protected readonly game: Game;
  protected readonly moreAnimations: MoreAnimations;
  constructor(g: Game, flowState: FlowState) {
    super(g.animationManager, g.bga, flowState);
    this.moreAnimations = new MoreAnimations(this.animationManager);
    this.game = g;
  }

  protected override confirmationsEnabled(): boolean {
    // FIXME: process gamepreferences.json and create constants/accessors/etc
    return this.bga.userPreferences.get(100) > 0;
  }

  protected override useAutoclick(): boolean {
    return this.bga.userPreferences.get(101) > 0;
  }

  protected uiStyle(): UIStyle {
    return this.bga.userPreferences.get(102) ? 'pieces' : 'actionbuttons';
  }

  override offboard(): HTMLElement {
    return $(IDS.OFF_BOARD);
  }

  private negate(moneyDelta: Moneys): Moneys {
    return Object.fromEntries(Object.entries(moneyDelta).map(kv => [kv[0], -kv[1]]));
  }

  protected updateMoneyDelta(moneyDelta?: Moneys): void {
    if (! moneyDelta) {
      return;
    }
    this.pushUndoOp('updateMoneyDelta', async () => this.game.updateMoneyDelta(this.negate(moneyDelta)));
    this.game.updateMoneyDelta(moneyDelta);
  }

  protected offspringSlide(offspring : Offspring | undefined): Promise<any> {
    if (offspring) {
      // if it's already on-screen, skip animation.
      if (!$(IDS.tile(offspring.placed_tile.tile))) {
        let offspringElem = this.game.makeTileSpan(offspring.placed_tile.tile);
        // FIXME: why needed?
        offspringElem.style.transform = 'rotate(0deg)';
        return this.game.flashParents(offspring)
          .then(() => this.slideIn(offspringElem, Elements.enclosureSpace(this.player_id, offspring.placed_tile.space)));
      }
    }
    return Promise.resolve();
  }

}

class ExchangeFlow extends ZooFlow<PossibleExchange[]> {
  constructor(g: Game, flowState: FlowState) { super(g, flowState); }

  protected override doStart(possible_exchanges: PossibleExchange[]) {
    let exchangesBySrc : PossibleExchange[][] = [];
    for (let pe of possible_exchanges) {
      let src = pe.src[0]!;
      let p = exchangesBySrc[src.enclosure_id];
      if (!p) {
        exchangesBySrc[src.enclosure_id] = [];
      }
      exchangesBySrc[src.enclosure_id]!.push(pe);
    }

    if (this.uiStyle() == 'actionbuttons') {
      this.initStatusBar(_("Select the first enclosure to exchange"));
      this.addRestartAndUndoButtons();
    }
    exchangesBySrc.forEach((pes: PossibleExchange[], encid: number) => {
      let src = pes[0]!.src;
      src.forEach((p) => {
        if (Elements.enclosureTile(this.player_id, p)) {
          let es = Elements.enclosureSpace(this.player_id, p);
          console.log("add selectExchangeDest to", es);
          this.addSelectableOnclick(
            Elements.enclosureSpace(this.player_id, p),
            () => this.callUndoably("selectExchangeDest", async () => {
              src.forEach(s => this.markSelected(Elements.enclosureSpace(this.player_id, s)));
              this.selectDestinationForExchange(pes);
            }),
            _('Exchange tiles'));
        }
      })
    });
  }

  private selectDestinationForExchange(pes: PossibleExchange[]) {
    this.initStatusBar(_("Select the animals to exchange with"));
    pes.forEach((pe : PossibleExchange) =>
      pe.dest.forEach(d =>
        this.addSelectableOnclick(
          Elements.enclosureSpace(this.player_id, d),
          async () =>  {
            let anims: AnimationList = [];
            for (let i = 0; i < pe.src.length; ++i) {
              anims.push(() => this.moreAnimations.swapFirstChildren(
                Elements.enclosureSpace(this.player_id, pe.src[i]!),
                Elements.enclosureSpace(this.player_id, pe.dest[i]!))
              );
            }
            // FIXME: this should probably happen in the "then".
            if (pe.offspring) {
              pe.offspring.forEach(o => anims.push(() => this.offspringSlide(o)));
            }
            this.updateMoneyDelta(pe.money_delta);
            this.pushUndoOp("exchange", () => this.animationManager.playParallel(anims));
            await this.animationManager.playParallel(anims)
              .then(() => pe.dest.forEach(d => this.markMoved(Elements.enclosureSpace(this.player_id, d))))
              .then(() => this.callUndoably("confirmExchange", async () => this.confirmExchange(pe)));
          }
        )
      )
    );
    this.addRestartAndUndoButtons();
  }

  private confirmExchange(pe: PossibleExchange) {
    this.initStatusBar(_("Confirm exchange"));
    this.addConfirmAndRestartActionButtons("actExchangeEnclosureAnimals", {
  		src_enclosure_id : pe.src[0]!.enclosure_id,
		  src_positions: JSON.stringify(pe.src.map((s) => s.pos)),
		  dest_enclosure_id: pe.dest[0]!.enclosure_id,
      dest_positions: JSON.stringify(pe.dest.map((s) => s.pos)),
    });
  }
}

class PurchaseTilesFlow extends ZooFlow<PossibleMove[]> {
  constructor(g: Game, flowState: FlowState) {
    super(g, flowState);
  }

  protected override doStart(possible_purchases: PossibleMove[]) {
    this.initStatusBar(_("Select a tile to purchase from another player's barn"));
    this.addRestartAndUndoButtons();
    possible_purchases.forEach((pp: PossibleMove) => {
        this.addSelectableOnclick(
          Elements.enclosureSpace(pp.src_player_id, pp.src),
          () => this.callUndoably("selectPurcaseDest", async () => new PurchaseTileFlow(this.game, this.flowState).start(pp))
        );
      });
  }
}

class PurchaseTileFlow extends ZooFlow<PossibleMove> {
  constructor(g: Game, flowState: FlowState) {
    super(g, flowState);
  }

  protected override doStart(pp: PossibleMove) {
    this.updateMoneyDelta(pp.money_delta);
    this.initStatusBar(_("Select a destination for the purchased tile"));
    pp.dests.forEach((dest: Destination) =>
      this.addSelectableOnclick(
        Elements.enclosureSpace(this.player_id, dest.space),
        () => {
          this.slide(Elements.enclosureTile(pp.src_player_id, pp.src)!,
                     Elements.enclosureSpace(this.player_id, dest.space))
            .then(() => this.updateMoneyDelta(dest.money_delta))
            .then(() => this.callUndoably("confirmPurcase", async () => this.confirmPurchase(pp, dest)));
          if (dest.offspring) {
            this.offspringSlide(dest.offspring);
          }
        }
      ));
    this.addRestartAndUndoButtons();
  }

  private confirmPurchase(pp: PossibleMove, dest: Destination) {
    this.initStatusBar(_("Confirm purchase"));
    this.addConfirmAndRestartActionButtons('actPurchaseTile', {
      from_player_id: pp.src_player_id,
      barn_pos: pp.src.pos,
      enclosure_id: dest.space.enclosure_id,
      enclosure_pos: dest.space.pos
    });
  }
}

class ExpandZooFlow extends ZooFlow {
  constructor(g: Game, flowState: FlowState) { super(g, flowState); }

  override doStart() {
    this.initStatusBar(_('Expand zoo?'));
    let current = this.game.getCurrentExtensions(this.player_id);
    this.game.renderExtensions(this.player_id, current + 1);
    this.pushUndoOp('expandZoo', async () => this.game.renderExtensions(this.player_id, current));
    this.addConfirmAndRestartActionButtons('actExpandZoo', {});
  }
};

class DrawTileFlow extends ZooFlow<boolean> {
  constructor(g: Game, flowState: FlowState) { super(g, flowState); }

  override doStart(lastround: boolean) {
    this.initStatusBar(_('Draw a tile? (cannot undo)'));
    this.markSelected(Elements.drawnTile(lastround));
    this.addConfirmAndRestartActionButtons('actDrawTile', {});
  }
};

class LoadDrawnTileFlow extends ZooFlow<LoadDrawnTileArgs> {
  constructor(g: Game, flowState: FlowState) { super(g, flowState); }

  override doStart(args: LoadDrawnTileArgs) {
    this.initStatusBar(_('Place ${tile_type} in an available truck'),
        { tile_type: args.tile.type,
          tile_description: this.game.tileTranslations.get(args.tile.type) });
    let elem = Elements.tile(args.tile)!; // was: Elements.drawnTile(args.drawn_from_endgame_pile);
    this.markSelected(elem);
    args.available_spaces.forEach((truckLoc: TruckLocation) =>
        this.addSelectableOnclick(Elements.truckSpace(truckLoc.truck_id, truckLoc.truck_pos),
          () =>
              this.callUndoably("chooseTiletoPlace",
                () => this.placeDrawnTile(elem, args.tile, truckLoc))));
  }

  private async placeDrawnTile(tileElem: HTMLElement, tile: Tile, truckLoc: TruckLocation) {
    let space = Elements.truckSpace(truckLoc.truck_id, truckLoc.truck_pos);
    this.slide(tileElem, space).then(() => this.confirmLoadDrawnTile(tile, truckLoc));
  }

  private confirmLoadDrawnTile(tile: Tile, tl: TruckLocation) {
    this.initStatusBar(_('Place ${tile_type} in truck ${truck_id}?'),
        { tile_type: tile.type,
          tile_description: this.game.tileTranslations.get(tile.type),
          truck_id: tl.truck_id });
    // FIXME: restart doesn't re-highlight the truck spaces.
    this.addConfirmAndRestartActionButtons('actLoadDrawnTile', tl);
  }
};

interface PossibleDelivery {
    truck_pos: number;
    /*
    tile: string;
    tile_id: number;
    */
    dests: Destination[];
}

interface DeliverTilesArgs {
  truck_id: number;
  possible_deliveries: PossibleDelivery[];
}

class DeliverTilesFlow extends ZooFlow<DeliverTilesArgs> {
  /*
  protected async doStart(args: DeliverTilesArgs) {
    await this.playParallel(truck.coin_positions.map(pos =>
      () => this.slideOutAndDestroy(
        Elements.truckTile(args.truck_id, pos)!,
        this.getPlayerPanelElement(this.player_id))))
      .then(() => {
        this.updateMoneyDelta(truck.money_delta);
        this.callUndoably("chooseTiletoPlace", () => this.chooseTruckTileToPlace(args.possible_deliveries, args.truck_id));
      });
  }

  private async chooseTruckTileToPlace(pps: PossibleDelivery[], truck_id: number) {
      */
  private restart = { restart: () => this.bga.actions.performAction('actUndo', {}) };
  protected async doStart(args: DeliverTilesArgs) {
    if (!args.possible_deliveries || args.possible_deliveries.length == 0) {
      this.initStatusBar(_('Confirm your truck tile deliveries'));
      // FIXME: clear marked on confirmation
      this.addConfirmAndRestartActionButtons('actConfirmDelivery', {}, this.restart);
    }
    else {
      this.initStatusBar(_('Choose a tile to deliver from the selected truck'));
      args.possible_deliveries.forEach((pp: PossibleDelivery) => {
        let elem = Elements.truckSpace(args.truck_id, pp.truck_pos);
        this.addSelectableOnclick(elem, async () => {
          this.callUndoably("chooseDest", () => this.chooseDestination(pp, args.truck_id));
        });
      });
      this.addRestartAndUndoButtons(this.restart);
    }
  }

  private async chooseDestination(pp: PossibleDelivery, truck_id: number) {
    this.initStatusBar(_('Choose a destination for the selected tile'));

    pp.dests.forEach((dest: Destination) => {
      let encElem = Elements.enclosureSpace(this.player_id, dest.space);
      this.addSelectableOnclick(encElem, async (evt:MouseEvent) => {
        let tileElem = Elements.truckSpace(truck_id, pp.truck_pos).firstElementChild as HTMLElement;
        this.slide(tileElem,encElem).then(() => {
          return this.offspringSlide(dest.offspring).then( () => {
            this.updateMoneyDelta(dest.money_delta);
            this.bga.actions.performAction('actDeliverTile', { truck_pos: pp.truck_pos, enclosure_id: dest.space.enclosure_id, enclosure_pos: dest.space.pos, confirm_if_done: false })
          });
        });
      });
    });
    this.addRestartAndUndoButtons({ restart: () => this.bga.actions.performAction('actUndo', {}) });
  }
}

class TakeTruckFlow extends ZooFlow<number[]> {

  constructor(g: Game, flowState: FlowState) { super(g, flowState); }

  override doStart(availableTruckIds: number[]) {
    this.initStatusBar(_('Select a truck'));

    availableTruckIds.forEach((truck_id: number) => {
      this.addSelectableOnclick($(IDS.truck(truck_id)),
        () => this.bga.actions.performAction('actTakeTruck', { truck_id: truck_id }));
    });
    this.addRestartAndUndoButtons();
  }
};

class DiscardTileFlow extends ZooFlow<PossibleDiscard> {
  constructor(g: Game, flowState: FlowState) { super(g, flowState); }

  override doStart(discardables: PossibleDiscard) {
    if (this.uiStyle() == 'actionbuttons') {
      this.initStatusBar(_('Select a tile in your barn to discard'));
      this.addRestartAndUndoButtons();
    }

    discardables.spaces.forEach((space: Space) => {
      this.addSelectableOnclick(
        Elements.enclosureSpace(this.player_id, space),
        async () => {
          await this.slideOutAndDestroy(Elements.enclosureTile(this.player_id, space)!,$(IDS.OFF_BOARD))
            .then(() => {
              // should always have money delta
              this.updateMoneyDelta(discardables.money_delta!);
              this.callUndoably("confirmDiscard", async () => this.confirmDiscard(space));
            })
        });
    });
  }

  private confirmDiscard(space: Space) {
    this.initStatusBar(_('Confirm discard'));
    this.addConfirmAndRestartActionButtons('actDiscardTile', { barn_pos: space.pos });
  }
}

class MoveTileFlow extends ZooFlow<PossibleMove[]> {
  constructor(g: Game, flowState: FlowState) { super(g, flowState); }

  override doStart(possibleMoves: PossibleMove[]) {
    if (this.uiStyle() == 'actionbuttons') {
      this.initStatusBar(_('Select a tile to move'));
      this.addRestartAndUndoButtons();
    }
    possibleMoves.forEach((m: PossibleMove) => {
      this.addSelectableOnclick(
        Elements.enclosureSpace(this.player_id, m.src),
        () => this.callUndoably("chooseMoveDest", async () => this.chooseDest(m))
      )
    });
  }

  private chooseDest(pm: PossibleMove) {
    this.updateMoneyDelta(pm.money_delta);
    this.initStatusBar(_('Select a destination space'));
    this.addRestartAndUndoButtons();
    pm.dests.forEach((dest: Destination) => {
      let elem = Elements.enclosureTile(this.player_id, pm.src);
      let destElem = Elements.enclosureSpace(this.player_id, dest.space)
      this.addSelectableOnclick(destElem,
        () => this.slide(elem!, destElem)
          .then(() => {
            // FIXME: is this line needed?
            destElem.classList.add(CSS.MOVED);
            this.markMoved(destElem);
            this.callUndoably("confirmMove", async () => this.confirmMove(pm.src, dest));
          })
      )
    });
  }

  private async confirmMove(src: Space, dest: Destination) {
    await this.offspringSlide(dest.offspring).then(() => this.updateMoneyDelta(dest.money_delta));
    this.initStatusBar(_('Confirm move'));
    this.addConfirmAndRestartActionButtons('actMoveTile', {
      src_id: src.enclosure_id, src_pos: src.pos, dest_id: dest.space.enclosure_id, dest_pos: dest.space.pos
    });
  }
}

class SpaceMultimapMap<T> {
  private data: Record<number, T[]> = {};

  private asInt(s:Space): number {
    return s.enclosure_id * 100 + s.pos;
  }

  public add(s: Space, t: T) {
    const ix = this.asInt(s);
    let ts = this.data[ix];
    if (!ts) {
      ts = [];
      this.data[ix] = ts;
    }
    ts.push(t);
  }

  public getAll(s: Space) : T[] {
    let ts = this.data[this.asInt(s)];
    return ts ?? [];
  }

  public contains(s: Space): boolean {
    return Boolean(this.data[this.asInt(s)]);
  }
}

class MoveOrDiscardTileFlow extends ZooFlow<{possible_moves: PossibleMove[], possible_discards: PossibleDiscard}> {
  constructor(g: Game, flowState: FlowState) { super(g, flowState); }

  override doStart(args: { possible_moves: PossibleMove[], possible_discards: PossibleDiscard}) {

    const asInt = (s:Space) => s.enclosure_id * 100 + s.pos;

    let movesrcs: number[] = [];
    args.possible_moves.forEach((m: PossibleMove) => movesrcs.push(asInt(m.src)));
    let dissrcs: number[] = [];
    args.possible_discards.spaces.forEach((space: Space) => dissrcs.push(asInt(space)));

    args.possible_moves.forEach((m: PossibleMove) => {
      let es = Elements.enclosureSpace(this.player_id, m.src);
      const discardable = dissrcs.indexOf(asInt(m.src)) >= 0;
      this.addSelectableOnclick(
        es,
        () => this.callUndoably("chooseMoveDest" + asInt(m.src), async () =>
          this.chooseDest(m, discardable ? args.possible_discards.money_delta : null),
      ), discardable ? _('Discard or move tile') : _('Move tile')
      )
    });
    args.possible_discards.spaces.forEach((space: Space) => {
      if (movesrcs.indexOf(asInt(space)) < 0) {
        this.addSelectableOnclick(
          Elements.enclosureSpace(this.player_id, space),
          async () => {
            await this.slideOutAndDestroy(Elements.enclosureTile(this.player_id, space)!,$(IDS.OFF_BOARD))
              .then(() => {
                // should always have money delta
                this.updateMoneyDelta(args.possible_discards.money_delta!);
                this.callUndoably("confirmDiscard", async () => this.confirmDiscard(space));
              })
          }, _('Discard tile'));
        }
    });
  }

  private confirmDiscard(space: Space) {
    this.initStatusBar(_('Confirm discard'));
    this.addConfirmAndRestartActionButtons('actDiscardTile', { barn_pos: space.pos });
  }

  private chooseDest(pm: PossibleMove, discardMoneyDelta?: Moneys) {
    this.initStatusBar(_('Select a destination space or'));
    if (discardMoneyDelta) {
      this.bga.statusBar.addActionButton(_('Discard the tile'),
        async () => {
          // FIXME: should this be exposed? better way to do this? wraap addActionButton?
          this.clearOnclicks();
          await this.slideOutAndDestroy(Elements.enclosureTile(this.player_id, pm.src)!,$(IDS.OFF_BOARD))
          .then(() => {
            this.game.updateMoneyDelta(discardMoneyDelta);
            this.confirmDiscard(pm.src);
          })
        });
    }
    this.addRestartAndUndoButtons();
    pm.dests.forEach((dest: Destination) => {
      let elem = Elements.enclosureTile(this.player_id, pm.src);
      let destElem = Elements.enclosureSpace(this.player_id, dest.space)
      console.log("Adding onclick to ", destElem, "for ", pm.src);
      this.addSelectableOnclick(destElem,
        () => this.slide(elem!, destElem)
          .then(() => {
            this.updateMoneyDelta(pm.money_delta);
            // FIXME: is this line needed?
            destElem.classList.add(CSS.MOVED);
            this.markMoved(destElem);
            this.callUndoably("confirmMove", async () => this.confirmMove(pm.src, dest));
          })
      )
    });
  }

  private async confirmMove(src: Space, dest: Destination) {
    await this.offspringSlide(dest.offspring).then(() => this.updateMoneyDelta(dest.money_delta));
    this.initStatusBar(_('Confirm move'));
    this.addConfirmAndRestartActionButtons('actMoveTile', {
      src_id: src.enclosure_id, src_pos: src.pos, dest_id: dest.space.enclosure_id, dest_pos: dest.space.pos
    });
  }
}

class PlayerTurnFlow extends ZooFlow<PlayState> {
  constructor(g: Game) { super(g, new FlowState(g.animationManager.playSequentially)); }

  protected override doStart(playState: PlayState) {
    this.initStatusBar(_("You must take an action"));
    if (this.uiStyle() == 'actionbuttons') {
      if (playState.can_draw) {
          this.bga.statusBar.addActionButton(_('Draw tile'),
            () => new DrawTileFlow(this.game, this.flowState).start(playState.lastround));
      }
      if (playState.available_trucks.length > 0) {
        this.bga.statusBar.addActionButton(_('Take truck'),
          () => new TakeTruckFlow(this.game, this.flowState).start(playState.available_trucks));
      }
      if (playState.possible_moves.length > 0) {
        this.bga.statusBar.addActionButton(_('Move tile'),
          () => new MoveTileFlow(this.game, this.flowState).start(playState.possible_moves));
      }
      if (playState.possible_exchanges.length > 0) {
        this.bga.statusBar.addActionButton(_('Exchange animals'),
          () => new ExchangeFlow(this.game, this.flowState).start(playState.possible_exchanges));
      }
      if (playState.possible_purchases.length > 0) {
        this.bga.statusBar.addActionButton(_('Purchase tile'),
          () => new PurchaseTilesFlow(this.game, this.flowState).start(playState.possible_purchases));
      }
      if (playState.possible_discards.spaces.length > 0) {
        this.bga.statusBar.addActionButton(_('Discard tile'),
          () => new DiscardTileFlow(this.game, this.flowState).start(playState.possible_discards));
      }
      if (playState.extension_available > 0) {
        this.bga.statusBar.addActionButton(_('Expand zoo'),
          () => new ExpandZooFlow(this.game, this.flowState).start(null));
      }
    }
    else if (this.uiStyle() == 'pieces') {
      // FIXME: need to update the status bar title based on what's possible.

      // Drawing a tile is orthogonal to other actions.
      if (playState.can_draw) {
        let topTile = Elements.drawnTile(playState.lastround);
        if (!topTile && !playState.lastround) {
          topTile = Elements.drawnTile(true);
        }
        this.addSelectableOnclick(
          topTile,
          () => { new DrawTileFlow(this.game, this.flowState).start(playState.lastround) },
          _('Draw tile')
        );
      }

      // Truck delivery is orthogonal.
      playState.available_trucks.forEach(
        truck_id => this.addSelectableOnclick(
          Elements.truck(truck_id),
          () => this.bga.actions.performAction('actTakeTruck', { truck_id: truck_id }),
          _('Take truck'))
        );

      // Expanding is orthogonal to other actions.
      if (playState.extension_available > 0) {
        this.addSelectableOnclick($(IDS.extension(this.player_id, playState.extension_available)),
          () => new ExpandZooFlow(this.game, this.flowState).start(null), _('Expand zoo'));
      }

      // These can be separate since they exclusively are on other players' boards.
      if (playState.possible_purchases.length > 0) {
        playState.possible_purchases.forEach((pp: PossibleMove) => {
          this.addSelectableOnclick(
            Elements.enclosureSpace(pp.src_player_id, pp.src),
            () => new PurchaseTileFlow(this.game, this.flowState).start(pp),
            _('Purchase tile')
          );
        });
      }

      // Animals in enclosures can only be exchanged, so these are also fine to just do.
      if (playState.possible_exchanges.length > 0) {
        new ExchangeFlow(this.game, this.flowState).start(playState.possible_exchanges);
      }

      // It's moves and discards that are non-orthogonal, i.e. a tile in the barn
      //   can be discarded and possibly moved.
      // Probably want:
      //   if can be moved, then highlight destinations but also give 'Discard' button.
      //   if can't be moved, just give 'Discard' button.
      new MoveOrDiscardTileFlow(this.game, this.flowState).start(playState)
    }
  }
}

type UIStyle = 'pieces' | 'actionbuttons';

/** Game class */
export class Game extends BaseGame<ZGamedatas> {
  tileTranslations = new Map<string, string>();;

  private moneyCounter: Counter[] = [];
  private primaryStockCounter: Counter;
  private endgameStockCounter: Counter;
  private scoreSheet: ScoreSheet;

  constructor(bga: Bga<ZGamedatas>) {
    super(bga, Game.special_log_args);
    this.bga.states.register('PlayerTurn', new PlayerTurnFlow(this));
    this.bga.states.register('LoadDrawnTile', new LoadDrawnTileFlow(this, new FlowState(this.animationManager.playSequentially)));
    this.bga.states.register('DeliverTruckTiles', new DeliverTilesFlow(this, new FlowState(this.animationManager.playSequentially)));
  }

  flashParents(offspring: Offspring) : Promise<any> {
    return this.moreAnimations.flash(CSS.PARENT, [Elements.tile(offspring.mother), Elements.tile(offspring.father)]);
  }

  private async renderTileDraw(elem: HTMLElement, tile: Tile): Promise<any> {
    let setTile = () => {
      elem.id = IDS.tile(tile);
      elem.setAttribute(Attrs.TILE, tile.type);
    };
    if (!this.bgaAnimationsActive()) {
      setTile();
      return Promise.resolve(null);
    }
    // Create the front and back of the tile to flip
    let back = this.makeTileBackSpan();
    let front = this.makeTileSpan(tile);

    // "hide" the original tile
    elem.removeAttribute(Attrs.TILE);
    // Need them in the document
    elem.appendChild(front);
    elem.appendChild(back);

    await this.moreAnimations.flip(front, back).then(_ => {
      setTile();
      back.remove();
      front.remove();
    });
  }

  makeTileSpan(tile: Tile): HTMLElement {
    if (tile.type == 'block' || tile.type == '') {
      return this.makeTileBackSpan();
    }
    const id = IDS.tile(tile);
    let elem = $(id);
    if (elem) {
      if (elem.getAttribute(Attrs.TILE) != tile.type) {
        console.error("Found existing tile", elem, "with different type than ", tile);
      }
      return elem;
    }
    return Html.span({ id: id, attrs: Attrs.tile(tile.type) });
  }

  private makeTileBackSpan(): HTMLElement {
    return Html.span({ attrs: Attrs.tile('back') });
  }

  private renderStock(gamedatas: ZGamedatas) : void {
    const addStockTile = (elemId: string) =>
      $(elemId).insertAdjacentElement('afterbegin', this.makeTileBackSpan() );

    for (let i = Math.min(gamedatas.primary_pile_size, 5); i > 0; i--) {
      addStockTile(IDS.PRIMARY_PILE_TILES);
    }
    for (let i = Math.min(gamedatas.endgame_pile_size, 5); i > 0; i--) {
      addStockTile(IDS.ENDGAME_PILE_TILES);
    }
    if (gamedatas.drawntile) {
      let top = Elements.drawnTile(gamedatas.lastround);
      if (top) {
        // FIXME: might be nicer to create this properly ...
        top.setAttribute(Attrs.TILE, gamedatas.drawntile.type);
        top.id = IDS.tile(gamedatas.drawntile);
      }
    }
    if (!gamedatas.lastround && gamedatas.primary_pile_size > 0) {
      $(IDS.ENDGAME_PILE_TILES).appendChild(Html.span({ id: IDS.DISK, classes: 'zoo-disk' }));
    }
  }

  private renderTrucks(gamedatas: ZGamedatas): void {
    for (let truck of gamedatas.trucks) {
      truck.contents.forEach(contents => {
        if (contents.tile) {
          Elements.truckSpace(truck.truck_id, contents.pos).append(this.makeTileSpan(contents.tile));
        }
      });

      if (truck.taken_by_player_id) {
        // move it to player panel
        let tElem = $(IDS.depotSpace(truck.truck_id)).firstElementChild as HTMLElement;
        $(IDS.takenTruck(truck.taken_by_player_id)).appendChild(tElem);
        this.bga.gameui.disablePlayerPanel(truck.taken_by_player_id);
      }
    }
  }

  private renderEnclosures(gamedatas: ZGamedatas): void {
    for (let player_id in gamedatas.enclosures) {
      gamedatas.enclosures[player_id]!.forEach(es => {
        if (es.tile) {
          Elements.enclosureSpace(Number(player_id), es.space).append(this.makeTileSpan(es.tile));
        }
      })
    }
  }

  private updateEnclosureSummaries(summaries: EnclosureSummary[]) {
    summaries.forEach(summary => {
      let elem = $(IDS.playerPanelBoardSummary(summary.player_id, summary.enclosure_id));
      let type = summary.animal_type;
      elem.setAttribute(Attrs.TILE, summary.animal_type);
      if (type) {
        elem.title = this.tileTranslations.get(type) ?? type;
        elem.firstElementChild!.textContent = `${summary.count}`;
      } else {
        elem.title = '';
        elem.firstElementChild!.textContent = '';
      }
    });
  }

  private setupHtml(gamedatas: ZGamedatas): void {
    let zhtml = new ZoolorettoHtml(gamedatas, this.bga.gameui.player_id);
    this.bga.gameArea.getElement().appendChild(zhtml.baseStructure());
    for (const player of Object.values(gamedatas.players)) {
      this.bga.playerPanels.getElement(player.player_id).append(...zhtml.playerPanel(player));
      let counter = new ebg.counter();
      counter.create(IDS.money(player.player_id), { value: player.money });
      this.moneyCounter[player.player_id] = counter;
    }
    this.primaryStockCounter = new ebg.counter();
    this.primaryStockCounter.create(IDS.PRIMARY_PILE_COUNT, { value: gamedatas.primary_pile_size == 1000 ? null : gamedatas.primary_pile_size });
    this.endgameStockCounter = new ebg.counter();
    this.endgameStockCounter.create(IDS.ENDGAME_PILE_COUNT, { value: gamedatas.endgame_pile_size });
    this.renderStock(gamedatas);
    this.renderTrucks(gamedatas);
    this.renderEnclosures(gamedatas);
    this.updateEnclosureSummaries(gamedatas.enclosure_summaries);
    this.bga.userPreferences.onChange = (prefId: number, value: number) => {
      switch (prefId) {
        case 104:
          if (value) {
            $(IDS.GAME).classList.add('zoo-indicators');
          } else {
            $(IDS.GAME).classList.remove('zoo-indicators');
          }
      }
    }
  }

  private setupTranslations(gamedatas: ZGamedatas): void {
    gamedatas.tile_translations.forEach(v => this.tileTranslations.set(v.type, v.name));
  }

  setup(gamedatas: ZGamedatas) {
    this.setupTranslations(gamedatas);
    this.setupHtml(gamedatas);
    this.setupNotifications();
    this.setupScoreSheet(gamedatas);
    if (gamedatas.lastround) {
      this.showLastTurnBanner();
    }

    console.log('Game setup done');
  }

  private showLastTurnBanner() {
    this.bga.gameArea.addLastTurnBanner(_('This is the last round!'));
  }

  private setupNotifications(): void {
    this.bga.notifications.setupPromiseNotifications({ logger: console.log });
  }

  public updateMoneyDelta(delta: Moneys): void {
    Object.entries(delta).forEach(pv => this.moneyCounter[pv[0]]!.incValue(pv[1]));
  }

  // FIXME: consider making async to permit animations
  private updateMoneys(moneys: Moneys): void {
    Object.entries(moneys).forEach(pv => this.moneyCounter[pv[0]].toValue(pv[1]));
  }

  private addMoney(player_id: number, delta: number): void {
    this.moneyCounter[player_id]!.incValue(delta);
  }

  // FIXME: consider making this async to allow for animation
  renderExtensions(player_id : number, extensions: number): void {
    let elem = $(IDS.boardId(player_id));
    elem.setAttribute(Attrs.EXTENSIONS, String(extensions));
  }

  getCurrentExtensions(player_id : number): number {
    let elem = $(IDS.boardId(player_id));
    return Number(elem.getAttribute(Attrs.EXTENSIONS) || 0);
  }

  //
  // Entry point for player turn
  //

  private async notif_DrawTile(
    args: {
      tile: Tile,
      drawn_from_endgame_pile: boolean,
    }
  ): Promise<void> {
    let disk = $(IDS.DISK);
    if (args.drawn_from_endgame_pile) {
      this.showLastTurnBanner();
      if (disk) {
        await this.moreAnimations.slideOutAndDestroy(disk, $(IDS.OFF_BOARD))
      }
    }
    await this.renderTileDraw(Elements.drawnTile(args.drawn_from_endgame_pile), args.tile);
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
        $(IDS.ENDGAME_PILE_TILES).insertAdjacentElement('afterbegin', this.makeTileBackSpan());
      }
    } else {
      if (args.primary_pile_size >= 5) {
        $(IDS.PRIMARY_PILE_TILES).insertAdjacentElement('afterbegin', this.makeTileBackSpan());
      }
    }
    if (args.primary_pile_size != 1000) {
      this.primaryStockCounter.toValue(args.primary_pile_size);
    }
    this.endgameStockCounter.toValue(args.endgame_pile_size);
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
      await this.moreAnimations.slideAndAttach(
        Elements.tile(args.tile)!,
        Elements.truckSpace(args.truck_id, args.truck_pos))
          .then(() => this.replenishPilesAndUpdateCounters(args));
  }

  private async notif_ExpandZoo(args: {
      player_id: number,
      purchased_extensions: number,
      moneys: Moneys,
    }) {
    this.renderExtensions(args.player_id, args.purchased_extensions);
    this.updateMoneys(args.moneys);
  }

  private async notif_DeliverTruckTile(args: {
      player_id: number,
      truck_id: number,
      delivery: Delivery,
  }) {
    let dest = args.delivery.dest;
    if (!dest) {
      // coin
      await this.moreAnimations.slideOutAndDestroy(
        Elements.tile(args.delivery.tile),
          this.bga.playerPanels.getElement(args.player_id),
        ).then(() => this.addMoney(args.player_id, 1));
    }
    else {
      let anims : AnimationList = [];
      anims.push(() => this.moreAnimations.slideAndAttach(
        Elements.tile(args.delivery.tile)!,
        Elements.enclosureSpace(args.player_id, dest.space))
      );
      if (dest.offspring) {
        let offspring = dest.offspring!;
        if (!$(IDS.tile(offspring.placed_tile.tile))) {
          anims.push(() => this.flashParents(offspring));
          anims.push(() => {
            let elem = this.makeTileSpan(offspring.placed_tile.tile);
            let parent = Elements.enclosureSpace(args.player_id, offspring.placed_tile.space);
            parent.appendChild(elem);
            return this.animationManager.slideIn(elem, $(IDS.OFF_BOARD));
          });
        }
      }
      await this.animationManager.playSequentially(anims);
    }
  }

  private async notif_StartDelivery(args: {
    player_id: number,
    truck_id: number,
    coin_positions: number[],
    moneys: Moneys,
  }) {
    // FIXME: highlight the truck in question
    let coinElems = args.coin_positions.map(pos => Elements.truckTile(args.truck_id, pos)).filter(e => e);
    await this.animationManager.playParallel(coinElems.map(elem =>
      () => this.animationManager.slideOutAndDestroy(
              elem!, this.bga.playerPanels.getElement(args.player_id),{})))
      .then(() => this.updateMoneys(args.moneys))
  }

  private async notif_DeliveryCompleted(args: {
    player_id: number,
    truck_id: number,
    moneys: Moneys,
    enclosure_summaries: EnclosureSummary[],
  }) {
    await this.moreAnimations.slideAndAttach(
        Elements.truck(args.truck_id),
        $(IDS.takenTruck(args.player_id))
      )
      .then(() => {
        this.updateMoneys(args.moneys);
        this.updateEnclosureSummaries(args.enclosure_summaries);
        this.bga.gameui.disablePlayerPanel(args.player_id);
      })
  }

  private async notif_MoveTile(args: {
    player_id: number,
    tile: Tile,
    dest: Space,
    moneys: Moneys,
    enclosure_summaries: EnclosureSummary[],
  }) {
    this.updateMoneys(args.moneys);
    await this.moreAnimations.slideAndAttach(
      Elements.tile(args.tile)!,
      Elements.enclosureSpace(args.player_id, args.dest)
    )
      .then(() => this.updateEnclosureSummaries(args.enclosure_summaries))
  }

  private async notif_DiscardTile(args: {
    moneys: Moneys,
    tile: Tile,
    enclosure_summaries: EnclosureSummary[],
  }) {
    this.updateMoneys(args.moneys);
    await this.moreAnimations.slideOutAndDestroy(Elements.tile(args.tile), $(IDS.OFF_BOARD))
      .then(() => this.updateEnclosureSummaries(args.enclosure_summaries))
  }

  private async notif_PurchaseTile(args: {
			player_id: number,
      placed_tiles: PlacedTile[],
			moneys: Moneys,
      enclosure_summaries: EnclosureSummary[],
    }) {
    this.updateMoneys(args.moneys);
    await this.animationManager.playSequentially(
      args.placed_tiles.map(pt =>
        () => this.moreAnimations.slideAndAttach(Elements.tile(pt.tile)!, Elements.enclosureSpace(args.player_id, pt.space))
      )
    )
      .then(() => this.updateEnclosureSummaries(args.enclosure_summaries))
  }

  private async notif_EndRound(args: {
    truck_ids_returned: number[],
    dumped_tiles: Tile[],
    last_round: boolean }
  ) {

    let anims: AnimationList = [];
    args.dumped_tiles.forEach(tile =>
      anims.push(() => this.moreAnimations.slideOutAndDestroy(Elements.tile(tile), $(IDS.OFF_BOARD)))
    );
    args.truck_ids_returned.forEach(tid =>
      anims.push(() => this.moreAnimations.slideAndAttach(Elements.truck(tid), $(IDS.depotSpace(tid))))
    );

    await this.animationManager.playSequentially(anims).then( () => {
      this.bga.gameui.enableAllPlayerPanels();
      if (args.last_round) {
        this.showLastTurnBanner();
      }
    })
  }

  private async notif_ExchangeEnclosureAnimals(args: {
    player_id: number,
    placed_tiles: PlacedTile[],
    moneys: Moneys,
    enclosure_summaries: EnclosureSummary[],
  }) {
    this.updateMoneys(args.moneys);
    let anims: AnimationList = [];
    args.placed_tiles.forEach(pt =>  {
      let elem = Elements.tile(pt.tile);
      if (elem) {
        anims.push(() => this.moreAnimations.slideAndAttach(elem, Elements.enclosureSpace(args.player_id, pt.space)));
      } else {
        let elem = this.makeTileSpan(pt.tile);
        // FIXME: needed?
        elem.style.transform = 'rotate(0deg)';
        // a created offspring, create and slide it in
        anims.push(() => this.animationManager.slideIn(elem, $(IDS.OFF_BOARD), {}));
      }
    });
    await this.animationManager.playParallel(anims)
      .then(() => this.updateEnclosureSummaries(args.enclosure_summaries))
  }

  private setupScoreSheet(gamedatas: ZGamedatas): void {
    this.scoreSheet = new BgaScoreSheet.ScoreSheet(
      $(IDS.SCORE_SHEET),
      {
        animationsActive: () => this.bgaAnimationsActive(),
        // playerNameWidth: 80,
        // playerNameHeight: 30,
        // entryLabelWidth: 120,
        // entryLabelHeight: 20,
        classes: 'zoo-score-sheet',
        players: gamedatas.players,
        entries: [
          {
            property: 'full_enclosure_points',
            label: _('Points for full enclosures'),
            title: _('Amount specified on the enclosure tile, granted if all spaces in enclosure are occupie'),
            labelClasses: 'zoo-score-entries-label',
          },
          {
            property: 'near_full_enclosure_points',
            label: _('Points for nearly full enclosures'),
            title: _('Amount specified on the enclosure tile, granted if all but one space in enclosure are occupie'),
            labelClasses: 'zoo-score-entries-label',
          },
          {
            property: 'other_enclosure_points',
            label: _('Other enclosure points (with stalls)'),
            title: _('One point per animal in a non(-near)-full enclosure that has at least one stall'),
            labelClasses: 'zoo-score-entries-label',
          },
          {
            property: 'stall_points',
            label: _('Points for types of stalls in enclosures'),
            title: _('Two points for each type of stall in any enclosure'),
            labelClasses: 'zoo-score-entries-label',
          },
          {
            property: 'barn_stall_points',
            label: _('Penalty for stall types left in barn'),
            title: _('Two point penalty for each stall type left in barn'),
            labelClasses: 'zoo-score-entries-label',
          },
          {
            property: 'barn_animal_points',
            label: _('Penalty for animal types left in barn'),
            title: _('Two point penalty for each animal type left in barn'),
            labelClasses: 'zoo-score-entries-label',
          },
          {
            property: 'total',
            label: _('Total'),
            title: _('Total points'),
            labelClasses: 'zoo-score-entries-label',
            scoresClasses: 'zoo-score-total',
            width: 80,
            height: 40,
          },
        ],
        scores: gamedatas.endScores,
        onScoreDisplayed: (property: string, playerId: number, score: number) => {
          if (property === 'total') {
            this.bga.playerPanels.getScoreCounter(playerId).setValue(score);
          }
        },
      }
    );
  }

  private async notif_GameEnded(args: { endScores: any, }): Promise<void> {
    await this.scoreSheet.setScores( args.endScores, { startBy: this.bga.gameui.player_id, } );
  }

  ///////
  private static readonly special_log_args : Record<string, (args: any) => HTMLElement> = {
    tile_type: (args: any) => Html.span({attrs: Attrs.tile(args.tile_type), title: _(args.tile_description)}),
    src_tile_type: (args: any) => Html.span({attrs: Attrs.tile(args.src_tile_type), title: _(args.src_tile_description)}),
    dest_tile_type: (args: any) => Html.span({attrs: Attrs.tile(args.dest_tile_type), title: _(args.dest_tile_description)}),
    coins: (args: any) => Html.span({text: ""+args.coins},
        Html.span({classes: 'zoo-money-label', title: _("coins")}))
  };
}
