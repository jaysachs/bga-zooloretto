//
// interfaces for gamedatas
//
interface ZPlayer extends Player {
  player_id: number;
  money: number;
  purchased_extensions: number;
}

interface Tile {
  id: number;
  type: string;
}

interface TruckSpace {
  pos: number;
  tile: Tile | undefined;
}

interface Truck {
  taken_by_player_id: number;
  truck_id: number;
  // Should always be 3. null means empty.
  // FIXME: Need to be careful about 0- and 1- based; probably best to be consistent
  //   and use "null" for "nothing" and 0-based.
  contents: TruckSpace[];
}

interface EnclosureContents {
  space: Space;
  tile: Tile | undefined;
}

interface ZGamedatas extends Gamedatas<ZPlayer> {
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
}

//
// interfaces for args & notifs
//

// general use

interface Space {
  enclosure_id: number;
  pos: number;
}

interface Offspring {
  space: Space;
  tile: Tile;
}

interface Moneys {
  bank: number;
  players: { [ playerId: number ]: number };
}

interface Destination {
  space: Space;
  offspring: Offspring;
  money_delta: Moneys;
}

// FIXME: is this useful?
interface TruckLocation {
  truck_id: number,
  truck_pos: number
};


// PlaceDrawnTile state

interface PlaceDrawnTileArgs {
  tile: Tile,
  drawn_from_endgame_pile: boolean,
  available_spaces: TruckLocation[]
};



// PlayerTurn state

interface PossibleEnclosurePlacement {
  space: Space;
  next: PossiblePlacement[];
  offspring: Offspring | undefined;
  money_delta: Moneys | undefined;
}
interface PossiblePlacement {
  truck_pos: number;
  tile_type: string;
  encs: PossibleEnclosurePlacement[];
}

interface AvailableTruck {
  truck_id: number;
  coin_positions: number[];
  money_delta: Moneys;
  playable: PossiblePlacement[];
}

interface PossibleMove {
  src: Space;
  money_delta: Moneys;
  dests: Destination[];
}

interface PossiblePurchase {
  from_player_id: number;
  src: Space;
  money_delta: Moneys;
  dests: Destination[];
}

interface PossibleExchange {
  src: Space[];
  dest: Space[];
  offspring: Offspring[];
  money_delta: Moneys;
}

interface PlayState {
  lastround: boolean;
  can_draw: boolean;
  can_expand: boolean;
  available_trucks: AvailableTruck[];
  possible_discards: Destination[];
  possible_moves: PossibleMove[];
  possible_exchanges: PossibleExchange[];
  possible_purchases: PossiblePurchase[];
}

// notif_TakeTruckAndPlaceTiles

interface Delivery {
  truck_pos: number;
  tile: Tile;
  dest: EnclosurePlacement | undefined;
}

interface EnclosurePlacement {
  space: Space;
  offspring: Offspring | undefined;
}


//
// HTML structures
//

class Attrs {
  // FIXME: rename value to have zoo- prefix.
  static readonly ENCLOSURE : string = 'zoo-enclosure';
  static readonly EXTENSIONS : string = 'zoo-extensions';
  static readonly TILE : string = 'zoo-tile';

  static tile(tile_type: string): Record<string, string> {
    let a = {};
    a[Attrs.TILE] = tile_type;
    return a;
  }
}

class IDS {
  static readonly GAME = 'zoo-game'; // top-level element
  static readonly PRIMARY_PILE_TILES = 'zoo-primary-pile-tiles';
  static readonly ENDGAME_PILE_TILES = 'zoo-endgame-pile-tiles';
  static readonly PRIMARY_PILE_COUNT = 'zoo-primary-pile-count';
  static readonly ENDGAME_PILE_COUNT = 'zoo-endgame-pile-count';
  static readonly OFF_BOARD = 'overall-footer';
  static readonly BANK_MONEY = 'zoo-bank-money';
  static readonly DISK = 'zoo-disk';
  static readonly SCORE_SHEET = 'zoo-score-sheet';

  static depotSpace(truck_id: number) { return `zoo-depot-space-${truck_id}`}
  static truck(id : number) { return `truck-${id}`; }
  static truckSpace(truck_id : number, pos: number) { return `truckspace-${truck_id}-${pos}`; }
  static enclosure(player_id: number, enclosure_id: number): string { return `enclosure-${player_id}-${enclosure_id}`; }
  static enclosureSpace(player_id: number, enclosure_id: number, pos: number): string { return `enclosure-${player_id}-${enclosure_id}-${pos}`; }
  static takenTruck(player_id: number): string { return `zoo-taken-truck-${player_id}`; }
  static money(player_id: number): string { return `playermoney-counter-${player_id}` };
  static boardId(player_id: number): string { return `zoo-board-${player_id}`; }
  static tile(t : Tile): string { return `zoo-tile-${t.id}`; }
}

class CSS {
  static readonly TRUCK = 'zoo-truck';
  static readonly TARGETABLE = 'zoo-targetable';
  static readonly SELECTABLE = 'zoo-selectable';
  static readonly SELECTED = 'zoo-selected';
  static readonly MOVED = 'zoo-moved';
  static readonly DEPOT_SPACE = 'zoo-depot-space';
  static readonly PILE = 'zoo-pile'
}

class Elements {
  static tile(tile: Tile): HTMLElement | undefined {
    return $(IDS.tile(tile));
  }

  static drawnTile(endgame: boolean): HTMLElement {
    return $(endgame ? IDS.ENDGAME_PILE_TILES : IDS.PRIMARY_PILE_TILES).lastElementChild as HTMLElement;
  }

  static truck(truck_id: number) : HTMLElement {
    return $(IDS.truck(truck_id));
  }

  static truckSpace(truck_id: number, truck_pos: number) : HTMLElement{
    return $(IDS.truckSpace(truck_id, truck_pos))
  }

  static truckTile(truck_id: number, truck_pos: number) : (HTMLElement | undefined) {
    return this.truckSpace(truck_id, truck_pos).firstChild as (HTMLElement | undefined);
  }

  static enclosureSpace(player_id: number, space: Space) : HTMLElement {
    return $(IDS.enclosureSpace(player_id, space.enclosure_id, space.pos));
  }

  static enclosureTile(player_id: number, space: Space) : HTMLElement | undefined {
    return this.enclosureSpace(player_id, space).firstElementChild as HTMLElement;
  }

}

//
// UI flows
//

abstract class PlayFlow<T, U extends Gamedatas = Gamedatas, G extends BaseGame<U> = BaseGame<U>> {
  protected readonly game: G;
  protected readonly player_id: number;
  private onClickAbortController : AbortController = new AbortController();
  private undos: AnimationList = [];

  constructor(g : G) {
    this.game = g;
    this.player_id = g.player_id;
  }

  protected pushUndoAnim(anim: (() => Promise<any>) | (() => any)): void {
    this.undos.push(anim);
  }

  start(args?: T) {
    console.debug("Starting", this, args);
    this.undos = [];
    this.onClickAbortController = new AbortController();
    this.doStart(args);
  }

  protected abstract doStart(args?: T);

  protected playParallel(anims: AnimationList): Promise<any> {
    return this.game.animationManager.playParallel(anims);
  }

  protected slide(elem: HTMLElement, newParent: HTMLElement): Promise<any> {
    let currParent = elem.parentElement as HTMLElement;
    this.pushUndoAnim(() => this.game.animationManager.slideAndAttach(elem, currParent, {}));
    return this.game.animationManager.slideAndAttach(elem, newParent, {})
      .then(() => this.markMoved(newParent));
  }

  protected slideIn(elem: HTMLElement, newParent: HTMLElement): Promise<any> {
    this.pushUndoAnim(() => this.game.animationManager.slideOutAndDestroy(elem, $(IDS.OFF_BOARD), {}));
    newParent.appendChild(elem);
    return this.game.animationManager.slideIn(elem, $(IDS.OFF_BOARD), { });
  }

  protected slideOutAndDestroy(elem: HTMLElement, toElem: HTMLElement): Promise<any> {
    let backup = elem.cloneNode() as HTMLElement;
    let parent = elem.parentElement as HTMLElement;
    this.pushUndoAnim(() => {
      parent.appendChild(backup);
      this.game.animationManager.slideIn(backup, toElem, {});
    });
    return this.game.animationManager.slideOutAndDestroy(elem, toElem, {});
  }

  protected async rollback(): Promise<any> {
    let anims: AnimationList = [];
    while (this.undos.length > 0) {
      anims.push(this.undos.pop()!);
    }
    this.clearMarked();
    await this.game.animationManager.playParallel(anims);
  }

  protected initStatusBar(title: string, args?: any) {
    this.game.statusBar.removeActionButtons();
    this.game.statusBar.setTitle(title, args);
  }

  protected addConfirmActionButton(bgaAction: string, args: any, autoclick? : boolean) {
    this.game.statusBar.addActionButton(
      _('Confirm'),
      () => {
        this.clearMarked();
        this.game.statusBar.removeActionButtons();
        this.game.bgaPerformAction(bgaAction, args);
      },
      { autoclick: (autoclick === undefined) || autoclick });
  }

  protected addRestartTurnButton(onCancel?: CallableFunction): void {
    this.game.statusBar.addActionButton(_('Restart turn'),
        () => {
          this.rollback().then(() => {
            this.game.statusBar.removeActionButtons();
            onCancel && onCancel();
            this.game.restoreServerGameState();
          })
        },
      { color: "secondary"});
  }

  private clearOnclicks(): void {
    this.onClickAbortController.abort();
    this.onClickAbortController = new AbortController();
    this.clearMarked();
  }

  private marked: HTMLElement[] = [];

  protected markSelected(elem?: HTMLElement) {
    if (!elem) {
      return;
    }
    console.log("marking selected", elem);
    this.marked.push(elem);
    elem.classList.add(CSS.SELECTED);
  }

  protected markMoved(elem: HTMLElement) {
    this.marked.push(elem);
    elem.classList.add(CSS.MOVED);
  }

  protected markTargetable(elem: HTMLElement) {
    this.marked.push(elem);
    elem.classList.add(CSS.TARGETABLE);
  }

  protected markSelectable(elem?: HTMLElement) {
    if (!elem) {
      return;
    }
    console.log("marking selectable", elem);
    this.marked.push(elem);
    elem.classList.add(CSS.SELECTABLE);
  }

  protected clearMarked() {
    console.log("clearing marked", this.marked);
    this.marked.forEach(e => e.classList.remove(CSS.SELECTABLE, CSS.SELECTED, CSS.TARGETABLE, CSS.MOVED));
  }

  protected addSelectableOnclick(elem: HTMLElement, onclick: (evt: MouseEvent) => any ) {
    this.markSelectable(elem);
    elem.addEventListener(
      "click",
      async (ev: MouseEvent) => { this.clearOnclicks(); this.markSelected(elem); await onclick(ev); },
      { signal: this.onClickAbortController.signal });
  }

  protected getPlayerPanelElement(player_id: number): HTMLElement {
    return this.game.getPlayerPanelElement(player_id);
  }
}

abstract class ZooFlow<T = undefined> extends PlayFlow<T, ZGamedatas, ZoolorettoGame> {

  private negate(moneyDelta: Moneys): Moneys {
    return {
      bank: -moneyDelta.bank,
      players: Object.fromEntries(Object.entries(moneyDelta.players).map(kv => [kv[0], -kv[1]])),
    };
  }

  protected updateMoneyDelta(moneyDelta?: Moneys): void {
    if (! moneyDelta) {
      return;
    }
    this.pushUndoAnim(() => this.game.updateMoneyDelta(this.negate(moneyDelta)));
    this.game.updateMoneyDelta(moneyDelta);
  }

  protected offspringSlide(offspring : Offspring | undefined): Promise<any> {
    console.log("offspringSlide", offspring);
    if (offspring) {
      let offspringElem = this.game.makeTileSpan(offspring.tile);
      offspringElem.style.transform = 'rotate(0deg)';
      return this.slideIn(offspringElem, Elements.enclosureSpace(this.player_id, offspring.space));
    }
    return Promise.resolve();
  }

}

class ExchangeFlow extends ZooFlow<PossibleExchange[]> {
  constructor(g: ZoolorettoGame) { super(g); }

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

    this.initStatusBar(_("Select the first enclosure to exchange"));
    exchangesBySrc.forEach((pes: PossibleExchange[]) => {
      let src = pes[0]!.src;
      src.forEach((p) => {
        if (Elements.enclosureTile(this.player_id, p)) {
          this.addSelectableOnclick(Elements.enclosureSpace(this.player_id, p),
                                    () => this.selectDestinationForExchange(pes));
        }
      })
    });
    this.addRestartTurnButton();
  }

  private selectDestinationForExchange(pes: PossibleExchange[]) {
    this.initStatusBar(_("Select the destingation enclosure for the exchange"));
    pes.forEach((pe : PossibleExchange) =>
      pe.dest.forEach(d =>
        this.addSelectableOnclick(
          Elements.enclosureSpace(this.player_id, d),
          () =>  {
            let anims: AnimationList = [];
            for (let i = 0; i < pe.src.length; ++i) {
              let srcElem = Elements.enclosureTile(this.player_id, pe.src[i]!);
              if (srcElem) {
                anims.push(() => this.slide(
                  srcElem,
                  Elements.enclosureSpace(this.player_id, pe.dest[i]!)
                ));
              }
              let destElem = Elements.enclosureTile(this.player_id, pe.dest[i]!);
              if (destElem) {
                anims.push(() => this.slide(destElem, Elements.enclosureSpace(this.player_id, pe.src[i]!)));
              }
            }
            if (pe.offspring) {
              pe.offspring.forEach(o => anims.push(() => this.offspringSlide(o)));
            }
            this.updateMoneyDelta(pe.money_delta);
            this.playParallel(anims).then(() => this.confirmExchange(pe));
          }
        )
      )
    );
    this.addRestartTurnButton();
  }

  private confirmExchange(pe: PossibleExchange) {
    this.initStatusBar(_("Confirm exchange"));
    this.addConfirmActionButton("actExchangeEnclosureAnimals", {
  		src_enclosure_id : pe.src[0]!.enclosure_id,
		  src_positions: JSON.stringify(pe.src.map((s) => s.pos)),
		  dest_enclosure_id: pe.dest[0]!.enclosure_id,
      dest_positions: JSON.stringify(pe.dest.map((s) => s.pos)),
    });
    this.addRestartTurnButton();
  }
}

class PurchaseTileFlow extends ZooFlow<PossiblePurchase[]> {
  constructor(g: ZoolorettoGame) { super(g); }

  protected override doStart(possible_purchases: PossiblePurchase[]) {
    this.initStatusBar(_("Select a tile to purchase from another player's barn"));
    possible_purchases.forEach((pp: PossiblePurchase) => {
        this.addSelectableOnclick(
          Elements.enclosureSpace(pp.from_player_id, pp.src),
          () => this.selectDestinationForPurchase(pp)
        );
      });
    this.addRestartTurnButton();
  }

  private selectDestinationForPurchase(pp: PossiblePurchase) {
    this.updateMoneyDelta(pp.money_delta);
    this.initStatusBar(_("Select a destination for the purchased tile"));
    pp.dests.forEach((dest: Destination) =>
      this.addSelectableOnclick(
        Elements.enclosureSpace(this.player_id, dest.space),
        () => {
          this.slide(
            Elements.enclosureTile(pp.from_player_id, pp.src)!,
            Elements.enclosureSpace(this.player_id, dest.space)).then( () => {
              this.updateMoneyDelta(dest.money_delta);
              this.confirmPurchase(pp, dest)
            });
          if (dest.offspring) {
            this.offspringSlide(dest.offspring);
          }
        }
      ));
    this.addRestartTurnButton();
  }

  private confirmPurchase(pp: PossiblePurchase, dest: Destination) {
    this.initStatusBar(_("Confirm purchase"));
    this.addConfirmActionButton('actPurchaseTile', {
      from_player_id: pp.from_player_id,
      barn_pos: pp.src.pos,
      enclosure_id: dest.space.enclosure_id,
      enclosure_pos: dest.space.pos
    });
    this.addRestartTurnButton();
  }
}

class ExpandZooFlow extends ZooFlow {
  constructor(g: ZoolorettoGame) { super(g); }

  override doStart() {
    this.initStatusBar(_('Expand zoo?'));
    let current = this.game.getCurrentExtensions(this.player_id);
    this.game.renderExtensions(this.player_id, current + 1);
    this.pushUndoAnim(() => this.game.renderExtensions(this.player_id, current));
    this.addConfirmActionButton('actExpandZoo', {});
    this.addRestartTurnButton();
  }
};

class DrawTileFlow extends ZooFlow<boolean> {
  constructor(g: ZoolorettoGame) { super(g); }

  override doStart(lastround: boolean) {
    this.initStatusBar(_('Draw a tile? (cannot undo)'));
    this.markSelected(Elements.drawnTile(lastround));
    this.addConfirmActionButton('actDrawTile', {});
    this.addRestartTurnButton();
  }
};

class PlaceDrawnTileFlow extends ZooFlow<PlaceDrawnTileArgs> {
  constructor(g: ZoolorettoGame) { super(g); }

  override doStart(args: PlaceDrawnTileArgs) {
    console.log("starting PlaceDrawnTile flow", args);
    this.initStatusBar(_('Place the drawn tile'));
    let elem = Elements.tile(args.tile)!; // was: Elements.drawnTile(args.drawn_from_endgame_pile);
    this.markSelected(elem);
    args.available_spaces.forEach((truckLoc: TruckLocation) =>
        this.addSelectableOnclick(Elements.truckSpace(truckLoc.truck_id, truckLoc.truck_pos),
          () => this.placeDrawnTile(elem, args.tile, truckLoc)));
  }

  private placeDrawnTile(tileElem: HTMLElement, tile: Tile, truckLoc: TruckLocation) {
    let space = Elements.truckSpace(truckLoc.truck_id, truckLoc.truck_pos);
    this.slide(tileElem, space).then(() => this.confirmPlaceDrawnTile(tile, truckLoc));
  }

  private confirmPlaceDrawnTile(tile: Tile, tl: TruckLocation) {
    this.initStatusBar(_('Place tile ${tile} in truck ${truck_id} space ${truck_pos}?'),
        { tile: tile.type, truck_id: tl.truck_id, truck_pos: tl.truck_pos });
    this.addConfirmActionButton('actPlaceDrawnTileInTruck', tl);
    this.addRestartTurnButton();
  }
};


type PlacedTile = {
  // truck_id is implicit
  truck_pos: number;
  enclosure_id: number;
  enclosure_pos: number;
};

class TakeTruckFlow extends ZooFlow<AvailableTruck[]> {
  private placedTiles : PlacedTile[] = [];
  private truck_id: number;
  constructor(g : ZoolorettoGame) { super(g); }

  override doStart(availableTrucks: AvailableTruck[]) {
    this.initStatusBar(_('Select a truck'));
    this.addRestartTurnButton(/* () => this.cleanup() */);
    this.placedTiles = [];
    this.truck_id = 0;
    availableTrucks.forEach((truck: AvailableTruck) => {
      this.addSelectableOnclick($(IDS.truck(truck.truck_id)),
        async () => {
          await this.playParallel(truck.coin_positions.map(pos =>
            () => this.slideOutAndDestroy(
              Elements.truckTile(truck.truck_id, pos)!,
              this.getPlayerPanelElement(this.player_id))))
            .then(() => {
              this.updateMoneyDelta(truck.money_delta);
              this.chooseTruckTileToPlace(truck.truck_id, truck.playable, availableTrucks);
            });
        });
    });
  }

  private chooseTruckTileToPlace(truck_id: number, pps: PossiblePlacement[], availableTrucks: AvailableTruck[]) {
    this.truck_id = truck_id;
    if (!pps || pps.length == 0) {
      this.initStatusBar(_('Confirm your truck tile placements'));
      this.addConfirmActionButton(
        'actTakeTruckAndPlaceTiles', {
          truck_id: truck_id,
          placed_tiles: JSON.stringify(this.placedTiles),
        }
      );
    }
    else {
      this.initStatusBar(_('Choose a tile to place from the selected truck'));
      pps.forEach((pp: PossiblePlacement) => {
        let elem = Elements.truckSpace(truck_id, pp.truck_pos);
        this.addSelectableOnclick(elem, () => {
          elem.classList.add(CSS.SELECTED);
          this.chooseDestination(truck_id, pp, availableTrucks);
        });
      });
    }
    this.addRestartTurnButton();
  }

  private chooseDestination(truck_id: number, pp: PossiblePlacement, availableTrucks: AvailableTruck[]) {
    this.initStatusBar(_('Choose a destination for the selected tile'));
    this.addRestartTurnButton();
    pp.encs.forEach((pep: PossibleEnclosurePlacement) => {
      let encElem = Elements.enclosureSpace(this.player_id, pep.space);
      encElem.classList.add(CSS.TARGETABLE);
      this.addSelectableOnclick(encElem, (evt:MouseEvent) => {
        let tileElem = Elements.truckSpace(truck_id, pp.truck_pos).firstElementChild as HTMLElement;
        this.slide(tileElem,encElem).then(() => {
          return this.offspringSlide(pep.offspring).then( () => {
            this.updateMoneyDelta(pep.money_delta);
            this.placedTiles.push({ truck_pos: pp.truck_pos, enclosure_id: pep.space.enclosure_id, enclosure_pos: pep.space.pos});
            this.chooseTruckTileToPlace(truck_id, pep.next, availableTrucks);
          });
        });
      });
    });
  }
};

class DiscardTileFlow extends ZooFlow<Destination[]> {
  constructor(g : ZoolorettoGame) { super(g); }

  override doStart(discardables: Destination[]) {
    this.initStatusBar(_('Select a tile in your barn to discard'));
    this.addRestartTurnButton();

    discardables.forEach((dest: Destination) => {
      let space = dest.space;
      this.addSelectableOnclick(
        Elements.enclosureSpace(this.player_id, space),
        async () => {
          await this.slideOutAndDestroy(
            Elements.enclosureTile(this.player_id, space)!,
            $(IDS.OFF_BOARD)).then(() => {
              this.updateMoneyDelta(dest.money_delta);
              this.confirmDiscard(dest);
            })
        });
    });
  }

  private confirmDiscard(dest: Destination) {
    this.initStatusBar(_('Confirm discard'));
    this.addConfirmActionButton('actDiscardTile', { barn_pos: dest.space.pos });
    this.addRestartTurnButton();
  }
}

class MoveTileFlow extends ZooFlow<PossibleMove[]> {
  constructor(g : ZoolorettoGame) { super(g); }

  override doStart(possibleMoves: PossibleMove[]) {
    this.initStatusBar(_('Select a tile to move'));
    this.addRestartTurnButton();
    possibleMoves.forEach((m: PossibleMove) => {
      this.addSelectableOnclick(
        Elements.enclosureSpace(this.player_id, m.src),
        () => this.chooseDest(m)
      )
    });
  }

  private chooseDest(pm: PossibleMove) {
    this.updateMoneyDelta(pm.money_delta);
    this.initStatusBar(_('Select a destination space'));
    this.addRestartTurnButton();
    pm.dests.forEach((dest: Destination) => {
      let elem = Elements.enclosureTile(this.player_id, pm.src);
      let destElem = Elements.enclosureSpace(this.player_id, dest.space)
      this.addSelectableOnclick(destElem,
        () => this.slide(elem!, destElem)
          .then(() => {
            destElem.classList.remove(CSS.SELECTED);
            destElem.classList.add(CSS.MOVED);
            this.confirmMove(pm.src, dest);
          })
      )
    });
  }

  private async confirmMove(src: Space, dest: Destination) {
    await this.offspringSlide(dest.offspring).then(() => this.updateMoneyDelta(dest.money_delta));
    this.initStatusBar(_('Confirm move'));
    this.addConfirmActionButton('actMoveTile', {
      src_id: src.enclosure_id, src_pos: src.pos, dest_id: dest.space.enclosure_id, dest_pos: dest.space.pos
    });
    this.addRestartTurnButton();
  }
}

class ZoolorettoHtml {
  constructor(gamedatas: ZGamedatas, player_id: number) {
    this.gamedatas = gamedatas;
    this.player_id = player_id;
    this.twoPlayer = Object.keys(gamedatas.players).length == 2;
  }
  private readonly gamedatas: ZGamedatas;
  private readonly player_id: number;
  private readonly twoPlayer: boolean;

  public static range(start: number, end: number) {
    return Array.from({length: (end - start + 1)}, (v, k) => k + start);
  }

  private playerBoardDiv(player?: ZPlayer): HTMLElement | undefined {
    if (!player) { return undefined; }

    let enclosure = (e: number, n: number): HTMLElement => {
      let elem = Html.div({id: IDS.enclosure(player.player_id, e) },
        ... ZoolorettoHtml.range(1, n).map(i => Html.div({ id: IDS.enclosureSpace(player.player_id, e, i), classes: "zoo-cell"} ))
      );
      elem.setAttribute(Attrs.ENCLOSURE, ""+e);
      return elem;
    };

    let elem = Html
      .div({ id: IDS.boardId(player.player_id), classes: [ 'zoo-board' ]},
        enclosure(0, 20), // the barn
        enclosure(1, 6),
        enclosure(2, 6),
        enclosure(3, 7),
        enclosure(4, 6),
        this.twoPlayer ? enclosure(5, 6) : undefined
      );
    elem.setAttribute(Attrs.EXTENSIONS, ""+player.purchased_extensions);

    return Html
      .div({ id: `zoo-playerboard-${player.player_id}`, classes: [ "zoo-playerboard"] },
        Html.div({ id: `zoo-playername-${player.player_id}`},
          Html.span({ text: player.name, style: `color: #${player.color}`, classes: ["player-name","whiteblock","zoo-playername"]})
        ),
        elem
      );
  }

  baseStructure(): HTMLElement {
    let currentPlayer = this.gamedatas.players[this.player_id];
    let otherplayers = Object.values(this.gamedatas.players).filter((p) => p != currentPlayer);

    return Html
      .div({id: IDS.GAME, classes: this.twoPlayer ? 'zoo-2p' : ''},
        Html.div({ id: 'zoo-shared-container' },
          Html.div({ id: 'zoo-stock-and-bank' },
            Html.div({ id: 'zoo-primary-pile' },
              Html.div({ id: IDS.PRIMARY_PILE_COUNT, text: "??" }),
              Html.div({ id: IDS.PRIMARY_PILE_TILES, classes: CSS.PILE }),
            ),
            Html.div({ id: 'zoo-endgame-pile' },
              Html.div({ id: IDS.ENDGAME_PILE_COUNT }),
              Html.div({ id: IDS.ENDGAME_PILE_TILES, classes: CSS.PILE }),
            ),
            Html.div({ id: 'zoo-bank' },
              Html.div({ id: IDS.BANK_MONEY, text: '27' })
            )
          ),
          ... this.gamedatas.trucks.map(truck =>
            Html.div({id: IDS.depotSpace(truck.truck_id), classes: CSS.DEPOT_SPACE },
              Html.div({ id: IDS.truck(truck.truck_id), classes: CSS.TRUCK },
                ... truck.contents.map((contents, i) =>
                Html.div({ id: IDS.truckSpace(truck.truck_id, contents.pos) })
                )
              )
            )
          )
        ),
        Html.div({id: 'zoo-boards' },
          this.playerBoardDiv(currentPlayer),
          ... otherplayers.map((p) => this.playerBoardDiv(p))
        ),
        // Html.div({id: 'zoo-playeraid' }),
        Html.div({id: IDS.SCORE_SHEET})
    );
  }

  playerPanel(player: ZPlayer): HTMLElement {
    const playerId = player.player_id;
    console.log('Setting up panel for player ' + player.player_id);
    return Html
      .div({ classes: 'zoo-player-panel-ext'},
        Html.span({ classes: 'zoo-money'},
          Html.span({classes: 'zoo-money-label'}),
          Html.span({text: ': '}),
          Html.span({id: IDS.money(playerId)})),
        Html.div({ classes: CSS.DEPOT_SPACE, id: IDS.takenTruck(playerId)}),
      );
  }
}

/** Game class */
class ZoolorettoGame extends BaseGame<ZGamedatas> {
  constructor() {
    super([]);
  }

  private async renderTileDraw(elem: HTMLElement, tile: Tile) {
    const delay = ms => new Promise(res => setTimeout(res, ms));
    let back = Html.span({classes: 'zoo-flippee' });
    let front = Html.span({classes: 'zoo-flippee' });
    back.classList.add('zoo-flippee');
    back.setAttribute(Attrs.TILE, 'back');
    front.setAttribute(Attrs.TILE, tile.type);
    elem.removeAttribute(Attrs.TILE);
    elem.id = IDS.tile(tile);
    elem.appendChild(front);
    elem.appendChild(back);
    let fixup = () => {
        elem.setAttribute(Attrs.TILE, tile.type);
        back.remove();
        front.remove();
    }
    // need to add to both events in case not visible, end not fired.
    front.addEventListener('transitionend', fixup);
    front.addEventListener('transitioncancel', fixup);
    // FIXME: unclear why this delay is needed.
    return delay(10).then(() => {
      back.classList.add('zoo-flipping');
      front.classList.add('zoo-flipping');
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

  private addStockTile(pile: 'primary' | 'endgame') {
    let elemId = pile == 'primary' ? IDS.PRIMARY_PILE_TILES : IDS.ENDGAME_PILE_TILES;
    $(elemId).insertAdjacentElement('afterbegin', this.makeTileBackSpan() );
  }

  private renderStock() : void {
    for (let i = Math.min(this.gamedatas.primary_pile_size, 5); i > 0; i--) {
      this.addStockTile('primary');
    }
    for (let i = Math.min(this.gamedatas.endgame_pile_size, 5); i > 0; i--) {
      this.addStockTile('endgame');
    }
    if (this.gamedatas.drawntile) {
      let top = Elements.drawnTile(this.gamedatas.lastround);
      if (top) {
        // FIXME: might be nicer to create this properly ...
        top.setAttribute(Attrs.TILE, this.gamedatas.drawntile.type);
        top.id = IDS.tile(this.gamedatas.drawntile);
      }
    }
    if (!this.gamedatas.lastround) {
      $(IDS.ENDGAME_PILE_TILES).appendChild(Html.span({ id: IDS.DISK, classes: 'zoo-disk' }));
    }
  }

  private renderTrucks(): void {
    for (let truck of this.gamedatas.trucks) {
      truck.contents.forEach(contents => {
        if (contents.tile) {
          Elements.truckSpace(truck.truck_id, contents.pos).append(this.makeTileSpan(contents.tile));
        }
      });

      if (truck.taken_by_player_id) {
        // move it to player panel
        let tElem = $(IDS.depotSpace(truck.truck_id)).firstElementChild as HTMLElement;
        $(IDS.takenTruck(truck.taken_by_player_id)).appendChild(tElem);
      }
    }
  }

  private renderEnclosures(): void {
    for (let player_id in this.gamedatas.enclosures) {
      this.gamedatas.enclosures[player_id]!.forEach(es => {
        if (es.tile) {
          Elements.enclosureSpace(Number(player_id), es.space).append(this.makeTileSpan(es.tile));
        }
      })
    }
  }

  private bankCounter: Counter;
  private moneyCounter: Counter[] = [];
  private primaryStockCounter: Counter;
  private endgameStockCounter: Counter;

  private setupHtml(twoPlayer: boolean): void {
    let zhtml = new ZoolorettoHtml(this.gamedatas, this.player_id);
    this.getGameAreaElement().appendChild(zhtml.baseStructure());
    for (const player of Object.values(this.gamedatas.players)) {
      this.getPlayerPanelElement(player.player_id).appendChild(zhtml.playerPanel(player));
      let counter = new ebg.counter();
      counter.create(IDS.money(player.player_id), { value: player.money });
      this.moneyCounter[player.player_id] = counter;
    }
    this.bankCounter = new ebg.counter();
    this.bankCounter.create(IDS.BANK_MONEY, { value: this.gamedatas.bank_money });
    this.primaryStockCounter = new ebg.counter();
    this.primaryStockCounter.create(IDS.PRIMARY_PILE_COUNT, { value: this.gamedatas.primary_pile_size });
    this.endgameStockCounter = new ebg.counter();
    this.endgameStockCounter.create(IDS.ENDGAME_PILE_COUNT, { value: this.gamedatas.endgame_pile_size });
    this.renderStock();
    this.renderTrucks();
    this.renderEnclosures();
  }

  override setup(gamedatas: ZGamedatas) {
    super.setup(gamedatas);

    const twoPlayer = Object.keys(gamedatas.players).length == 2;
    this.setupHtml(twoPlayer);
    this.setupNotifications();
    this.setupScoreSheet();
    if (gamedatas.lastround) {
      (this as any).addLastTurnBanner(_('This is the last round!'));
    }

    console.log('Game setup done');
  }

  private setupNotifications(): void {
    this.bgaSetupPromiseNotifications({ logger: console.log, onEnd: this.addTooltipsToLog.bind(this) });
    // Notifications to ignore
    this.notifqueue.setIgnoreNotificationCheck(
      'NOTINUSE',
      (notif: any) => (notif.args.player_id == this.player_id));
  }

  private addTooltipsToLog() {
    /*
      const elements = document.querySelectorAll(`[${Attrs.ZTYPE}]:not([${Attrs.TT_PROCESSED}])`);
      elements.forEach(ele => {
        ele.setAttribute(Attrs.TT_PROCESSED, '');  // prevents tooltips being re-added to previous log entries
        this.addTooltip(ele.id, this.zcardTooltips[ele.attribute(Attrs.ZTYPE)!], '');
      });
      */
  }

  public updateMoneyDelta(delta: Moneys): void {
    if (delta.bank) {
      this.bankCounter.incValue(delta.bank);
    }
    for (let player_id in delta.players) {
      this.moneyCounter[player_id]!.incValue(delta.players[player_id]!);
    }
  }

  private updateMoneys(moneys: Moneys): void {
    this.bankCounter.toValue(moneys.bank);
    Object.entries(moneys.players).forEach(pv => this.moneyCounter[pv[0]].toValue(pv[1]));
  }

  private addMoney(player_id: number, delta: number): void {
    this.moneyCounter[player_id]!.incValue(delta);
  }

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

  private onUpdateActionButtons_PlayerTurn(playState: PlayState): void {
    this.statusBar.addActionButton(_('Draw tile'),
      () => new DrawTileFlow(this).start(playState.lastround),
      { disabled: !playState.can_draw });
    this.statusBar.addActionButton(_('Take truck'),
      () => new TakeTruckFlow(this).start(playState.available_trucks),
      { disabled: playState.available_trucks.length == 0});
    this.statusBar.addActionButton(_('Move tile'),
      () => new MoveTileFlow(this).start(playState.possible_moves),
      { disabled: playState.possible_moves.length == 0 });
    this.statusBar.addActionButton(_('Exchange animals'),
      () => new ExchangeFlow(this).start(playState.possible_exchanges),
      { disabled: playState.possible_exchanges.length == 0});
    this.statusBar.addActionButton(_('Purchase tile'),
      () => new PurchaseTileFlow(this).start(playState.possible_purchases),
      { disabled: playState.possible_purchases.length == 0 });
    this.statusBar.addActionButton(_('Discard tile'),
      () => new DiscardTileFlow(this).start(playState.possible_discards),
      { disabled: playState.possible_discards.length == 0 });
    this.statusBar.addActionButton(_('Expand zoo'),
      () => new ExpandZooFlow(this).start(),
      { disabled: !playState.can_expand} );
  }

  private onUpdateActionButtons_PlaceDrawnTile(args: PlaceDrawnTileArgs): void {
    new PlaceDrawnTileFlow(this).start(args);
  }

  private async notif_DrawTile(
    args: {
      tile: Tile,
      drawn_from_endgame_pile: boolean,
    }
  ): Promise<void> {
    this.gamedatas.lastround = args.drawn_from_endgame_pile;
    let disk = $(IDS.DISK);
    if (args.drawn_from_endgame_pile && disk) {
      await this.slideOutAndDestroy(disk, $(IDS.OFF_BOARD))
        .then(() => {
          // FIXME: want to use renderTileDraw or similar
          $(IDS.ENDGAME_PILE_TILES).appendChild(this.makeTileSpan(args.tile));
          (this as any).addLastTurnBanner(_('This is the last round!'));
        });
    }
    else {
      await this.renderTileDraw(Elements.drawnTile(args.drawn_from_endgame_pile), args.tile);
    }
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
    this.primaryStockCounter.toValue(args.primary_pile_size);
    this.endgameStockCounter.toValue(args.endgame_pile_size);
  }

  private async notif_DebugPlace(
    args: {
      drawn_from_endgame_pile: boolean,
      primary_pile_size: number,
      endgame_pile_size: number,
    }
  ): Promise<void> {
    await this.slideOutAndDestroy(
      Elements.drawnTile(args.drawn_from_endgame_pile),
      $(IDS.OFF_BOARD))
        .then(() => this.replenishPilesAndUpdateCounters(args));
  }

  private async notif_PlaceDrawnTileInTruck(args: {
    player_id: number,
    truck_id: number,
    truck_pos: number,
    // FIXME: should we figure this out based on where tile is?
    drawn_from_endgame_pile: boolean,
    tile: Tile,
    primary_pile_size: number,
    endgame_pile_size: number }) {
      await this.slideAndAttach(
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

  private async notif_TakeTruckAndPlaceTiles(args: {
    player_id: number,
    truck_id: number,
    moneys: Moneys,
    deliveries: Delivery[]
  }) {
    let anims : AnimationList = [];
    args.deliveries.forEach(delivery => {
      let dest = delivery.dest;
      if (!dest) {
        // FIXME: if (dest.tile.type != 'Coin') log error ...
        anims.push(() => this.slideOutAndDestroy(
          Elements.tile(delivery.tile),
          this.getPlayerPanelElement(args.player_id),
          {}
        ).then(() => this.addMoney(args.player_id, 1)));
      } else {
        anims.push(() => this.slideAndAttach(
          Elements.tile(delivery.tile)!,
          Elements.enclosureSpace(args.player_id, dest.space))
        );
        if (dest.offspring) {
          anims.push(() => {
            let elem = this.makeTileSpan(dest.offspring!.tile);
            let parent = Elements.enclosureSpace(args.player_id, dest.offspring!.space);
            parent.appendChild(elem);
            return this.animationManager.slideIn(elem, $(IDS.OFF_BOARD));
          });
        }
      }
    });
    anims.push(() => this.slideAndAttach(
        Elements.truck(args.truck_id),
        $(IDS.takenTruck(args.player_id))
      ));
    await this.animationManager.playSequentially(anims)
      .then(() => this.updateMoneys(args.moneys));
  }

  private async notif_MoveTile(args: {
    player_id: number,
    tile: Tile,
    dest: Space,
    moneys: Moneys,
  }) {
    this.updateMoneys(args.moneys);
    await this.slideAndAttach(
      Elements.tile(args.tile)!,
      Elements.enclosureSpace(args.player_id, args.dest)
    );
  }

  private async notif_DiscardTile(args: {
    moneys: Moneys,
    tile: Tile,
  }) {
    this.updateMoneys(args.moneys);
    await this.slideOutAndDestroy(Elements.tile(args.tile), $(IDS.OFF_BOARD));
  }

  private async notif_PurchaseTile(args: {
			player_id: number,
      tile: Tile,
      dest: Space,
			moneys: Moneys,
    }) {
    this.updateMoneys(args.moneys);
    await this.slideAndAttach(
      Elements.tile(args.tile)!,
      Elements.enclosureSpace(args.player_id, args.dest)
    );
  }

  private async notif_EndRound(args: {
    truck_ids_returned: number[],
    truck_dumped_pos: { truck_id: number, dumped_pos: number[] }[],
    last_round: boolean }
  ) {

    let anims: AnimationList = [];
    args.truck_dumped_pos.forEach(t =>
      t.dumped_pos.forEach(p =>
        anims.push(() =>
          this.slideOutAndDestroy(Elements.truckTile(t.truck_id, p)!, $(IDS.OFF_BOARD))
        )
      )
    );
    args.truck_ids_returned.forEach(tid =>
      anims.push(() => this.slideAndAttach(Elements.truck(tid), $(IDS.depotSpace(tid))))
    );

    await this.animationManager.playSequentially(anims).then( () => {
      if (args.last_round) {
        (this as any).addLastTurnBanner(_('This is the last round!'));
      }
    })
  }

  private async notif_ExchangeEnclosureAnimals(args: {
    player_id: number,
		src_enclosure_id: number,
		src_spaces: Space[],
		dest_enclosure_id: number,
		dest_spaces: Space[],
    moneys: Moneys,
  }) {
    if (args.player_id == this.player_id) {
      return;
    }
    // FIXME: unify this with the body of ExchangeFlow::confirm
    let anims: AnimationList = [];
    for (let i = 0; i < args.src_spaces.length; ++i) {
      let s = args.src_spaces[i]!;
      let d = args.dest_spaces[i]!;
      let srcElem = Elements.enclosureTile(args.player_id, s);
      if (srcElem) {
        anims.push(() => this.slideAndAttach(srcElem, Elements.enclosureSpace(args.player_id, d)));
      }
      let destElem = Elements.enclosureTile(args.player_id, d);
      if (destElem) {
        anims.push(() => this.slideAndAttach(destElem, Elements.enclosureSpace(args.player_id, s)));
      }
    }
    await this.animationManager.playParallel(anims).then(()=>this.updateMoneys(args.moneys));
  }

  private scoreSheet: ScoreSheet;
  private setupScoreSheet(): void {
    this.scoreSheet = new BgaScoreSheet.ScoreSheet(
    document.getElementById(IDS.SCORE_SHEET)!, // an empty div on your template to place the score sheet on
    {
      animationsActive: () => this.bgaAnimationsActive(), // so the animation doesn't trigger on replay fast mode
      // playerNameWidth: 80,
      // playerNameHeight: 30,
      // entryLabelWidth: 120,
      // entryLabelHeight: 20,
      classes: 'zoo-score-sheet',
      players: this.gamedatas.players,
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
      scores: this.gamedatas.endScores,
      onScoreDisplayed: (property: string, playerId: number, score: number) => {
        if (property === 'total') {
          this.scoreCtrl[playerId]!.setValue(score);
        }
      },
    }
  );      }

  private async notif_GameEnded(args: { endScores: any, }): Promise<void> {
    await this.scoreSheet.setScores(args.endScores, {
            startBy: this.player_id,
        });
  }

  ///////
  // private zcardSalt: number = 0;
  readonly special_log_args = {
    /*
      piece: (args: any) => `<span ${Attrs.PIECE}='${this.pieceVal(args.piece, args.player_id)}'></span>`,
      city: (args: any) => `<span ${Attrs.PIECE}='${this.pieceVal(args.city, 0)}'></span>`,
      zcard: (args: any) => `<span id='logzcard_${this.zcardSalt++}' ${Attrs.ZTYPE}='${args.zcard}'></span>`,
      original_piece: (args: any) => `<span ${Attrs.PIECE}='${this.pieceVal(args.original_piece, args.player_id)}'></span>`,
      */
  };

  override bgaFormatText(log: string, args: any): { log: string, args: any } {
    try {
      if (log && args && !args.processed) {
        args.processed = true;
        for (const key in this.special_log_args) {
          if (key in args) {
            args[key] = this.special_log_args[key](args);
          }
        }
      }
    } catch (e: any) {
      console.error(log, args, 'Exception thrown', e.stack);
    }
    return { log, args };
  }

}
