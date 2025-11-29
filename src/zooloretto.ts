interface ZPlayer extends Player {
  player_id: number;
  player_no: number;
  money: number;
  purchased_extensions: number;
}

interface Delivery {
  truck_pos: number;
  placement: EnclosurePlacement | 'coin';
}

interface EnclosurePlacement {
  enclosure_id: number;
  enclosure_pos: number;
  child_enclosure_id: number;
  child_enclosure_pos: number;
  child_type: string | undefined;
}

interface TruckLocation{ truck_id: number, truck_pos: number };
interface PlaceDrawnTileArgs { tile: string, available_spaces: TruckLocation[] };

type PlacedTile = {
  // truck_id is implicit
  truck_pos: number;
  enclosure_id: number;
  enclosure_pos: number;
};

interface TruckSpace {
  pos: number;

  // Empty string for empty. FIXME: use null?
  tile_type: string;
}

interface Truck {
  taken_by_player_id: number;
  truck_id: number;
  // Should always be 3. null means empty.
  // FIXME: Need to be careful about 0- and 1- based; probably best to be consistent
  //   and use "null" for "nothing" and 0-based.
  contents: TruckSpace[];
}

interface EnclosureSpace {
  pos: number;
  tile_type: string;
}

interface Enclosure {
  enclosure_id: number;
  spaces: EnclosureSpace[];
}

interface PlayerEnclosure {
  player_id: number;
  enclosures: Enclosure[];
}

interface ZGamedatas extends Gamedatas<ZPlayer> {
  primary_stocksize: number;
  endgame_stocksize: number;
  lastround: boolean;
  drawntile: string;
  // Should always be 3.
  trucks: Truck[];
  player_enclosures: PlayerEnclosure[];
}

interface PossibleEnclosurePlacement {
  enclosure_id: number;
  enclosure_pos: number;
  next: PossiblePlacement[];
}
interface PossiblePlacement {
  truck_pos: number;
  tile_type: string;
  encs: PossibleEnclosurePlacement[];
}

interface AvailableTruck {
  truck_id: number;
  playable: PossiblePlacement[];
}

interface Space {
  enclosure_id: number;
  pos: number;
}

interface PossibleMove {
  src: Space;
  dests: Space[];
}

interface PossiblePurchase {
  player_id: number;
  barn_pos: number;
  dests: Space[];
}


interface ExchangeGroup {
	enclosure_id: number;
  positions: number[];
}

interface PossibleExchange {
  src: ExchangeGroup;
  dest: ExchangeGroup;
}

interface PlayState {
  can_draw: boolean;
  can_expand: boolean;
  available_trucks: AvailableTruck[];
  possible_discards: number[];
  possible_moves: PossibleMove[];
  possible_exchanges: PossibleExchange[];
  possible_purchases: PossiblePurchase[];
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
  static readonly DEPOT = 'zoo-depot';
  static readonly PILE = 'zoo-pile';
  static readonly STOCK = 'zoo-stock';
  static readonly OFF_BOARD = 'overall-footer';

  static depotSpace(truck_id: number) { return `zoo-depot-space-${truck_id}`}
  static truck(id : number) { return `truck-${id}`; }
  static truckSpace(truck_id : number, pos: number) { return `truckspace-${truck_id}-${pos}`; }
  static enclosure(player_id: number, enclosure_id: number): string { return `enclosure-${player_id}-${enclosure_id}`; }
  static enclosureSpace(player_id: number, enclosure_id: number, pos: number): string { return `enclosure-${player_id}-${enclosure_id}-${pos}`; }
  static takenTruck(player_id: number): string { return `zoo-taken-truck-${player_id}`; }
  static money(player_id: number): string { return `playermoney-counter-${player_id}` };
  static boardId(player_id: number): string { return `zoo-board-${player_id}`; }
}

class CSS {
  static readonly TRUCK = 'zoo-truck';
  static readonly TARGETABLE = 'zoo-targetable';
  static readonly SELECTABLE = 'zoo-selectable';
  static readonly SELECTED = 'zoo-selected';
  static readonly MOVED = 'zoo-moved';
  static readonly DEPOT_SPACE = 'zoo-depot-space';
  static readonly DISK = 'zoo-disk';
}

class Elements {
  static topPile(): HTMLElement | undefined {
      return $(IDS.PILE).lastElementChild as (HTMLElement | undefined);
  }

  static truck(truck_id: number) : HTMLElement {
    return $(IDS.truck(truck_id));
  }

  static truckSpace(args: { truck_id: number, truck_pos: number }) : HTMLElement{
    return $(IDS.truckSpace(args.truck_id, args.truck_pos))
  }

  static truckSpaceTile(args: { truck_id: number, truck_pos: number }) : (HTMLElement | undefined) {
    return this.truckSpace(args).firstChild as (HTMLElement | undefined);
  }

  static enclosureSpace(args: { player_id: number, enclosure_id: number, enclosure_pos: number }) : HTMLElement {
    return $(IDS.enclosureSpace(args.player_id, args.enclosure_id, args.enclosure_pos));
  }

  static enclosureSpaceTile(args: { player_id: number, enclosure_id: number, enclosure_pos: number }) : HTMLElement {
    return this.enclosureSpace(args).firstElementChild as HTMLElement;
  }

}

//
// UI flows
//

abstract class PlayFlow<T, U extends Gamedatas = Gamedatas, G extends BaseGame<U> = BaseGame<U>> {
  protected readonly game: G;
  protected readonly player_id: number;
  private onClickAbortController : AbortController = new AbortController();
  private moves: {origin: HTMLElement, dest: HTMLElement, elem: HTMLElement }[] = [];

  constructor(g : G) {
    this.game = g;
    this.player_id = g.player_id;
  }

  start(args?: T) {
    console.debug("Starting", this, args);
    this.moves = [];
    this.onClickAbortController = new AbortController();
    this.doStart(args);
  }

  protected abstract doStart(args?: T);

  protected playParallel(anims: AnimationList): Promise<any> {
    return this.game.animationManager.playParallel(anims);
  }

  protected slide(elem: HTMLElement, newParent: HTMLElement): Promise<any> {
    this.moves.push({origin: elem.parentElement as HTMLElement, dest: newParent, elem: elem });
    return this.game.animationManager.slideAndAttach(elem, newParent, {})
      .then(() => this.markMoved(newParent));
  }

  protected async rollback(): Promise<any> {
    let anims: AnimationList = [];
    while (this.moves.length > 0) {
      let move = this.moves.pop()!;
      anims.push(() => {
        return this.game.animationManager.slideAndAttach(move.elem, move.origin, {});
      });
    }
    this.clearMarked();
    await this.game.animationManager.playParallel(anims);
  }

  protected initStatusBar(title: string, args?: any) {
    this.game.statusBar.removeActionButtons();
    this.game.statusBar.setTitle(title, args);
  }

  protected addConfirmActionButton(bgaAction: string, args?: any, autoclick? : boolean) {
    this.game.statusBar.addActionButton(
      _('Confirm'),
      () => {
        this.clearMarked();
        this.game.statusBar.removeActionButtons();
        this.game.bgaPerformAction(bgaAction, args);
      },
      { autoclick: autoclick || false });
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
      (ev: MouseEvent) => { this.clearOnclicks(); this.markSelected(elem); onclick(ev); },
      { signal: this.onClickAbortController.signal });
  }
}

abstract class ZooFlow<T = undefined> extends PlayFlow<T, ZGamedatas, ZoolorettoGame> {

}

class ExchangeFlow extends ZooFlow<PossibleExchange[]> {
  constructor(g: ZoolorettoGame) { super(g); }

  protected override doStart(possible_exchanges: PossibleExchange[]) {
    let exchangesBySrc : PossibleExchange[][] = [];
    for (let pe of possible_exchanges) {
      let p = exchangesBySrc[pe.src.enclosure_id];
      if (!p) {
        exchangesBySrc[pe.src.enclosure_id] = [];
      }
      exchangesBySrc[pe.src.enclosure_id]?.push(pe);
    }

    this.initStatusBar(_("Select the first enclosure to exchange"));
    exchangesBySrc.forEach((pes: PossibleExchange[]) => {
      let src = pes[0]!.src;
      src.positions.forEach((p) => {
        let esArg = {player_id: this.player_id, enclosure_id: src.enclosure_id, enclosure_pos: p};
        if (Elements.enclosureSpaceTile(esArg)) {
          this.addSelectableOnclick(Elements.enclosureSpace(esArg),
                                    (evt) => this.selectDestinationForExchange(pes));
        }
      });
    });
    this.addRestartTurnButton();
  }

  private selectDestinationForExchange(pes: PossibleExchange[]) {
    this.initStatusBar(_("Select the destingation enclosure for the exchange"));
    pes.forEach((pe : PossibleExchange) =>
      pe.dest.positions.forEach(d =>
        this.addSelectableOnclick(
          Elements.enclosureSpace({player_id: this.player_id, enclosure_id: pe.dest.enclosure_id, enclosure_pos: d}),
          (evt) =>  {
            let anims: AnimationList = [];
            for (let i = 0; i < pe.src.positions.length; ++i) {
              let srcArg = {player_id: this.player_id, enclosure_id: pe.src.enclosure_id, enclosure_pos: pe.src.positions[i]!};
              let destArg = {player_id: this.player_id, enclosure_id: pe.dest.enclosure_id, enclosure_pos: pe.dest.positions[i]!};
              let srcElem = Elements.enclosureSpaceTile(srcArg);
              if (srcElem) {
                anims.push(() => this.slide(
                  srcElem,
                  Elements.enclosureSpace(destArg)
                ));
              }
              let destElem = Elements.enclosureSpaceTile(destArg);
              if (destElem) {
                anims.push(() => this.slide(destElem, Elements.enclosureSpace(srcArg)));
              }
            }
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
  		src_enclosure_id : pe.src.enclosure_id,
		  src_positions: JSON.stringify(pe.src.positions),
		  dest_enclosure_id: pe.dest.enclosure_id,
      dest_positions: JSON.stringify(pe.dest.positions),
    });
    this.addRestartTurnButton();
  }
}

class PurchaseTileFlow extends ZooFlow<PossiblePurchase[]> {
  constructor(g: ZoolorettoGame) { super(g); }

  protected override doStart(possible_purchases: PossiblePurchase[]) {
    this.initStatusBar(_("Select a tile to purchase from another player's barn"));
    possible_purchases.forEach((pp: PossiblePurchase) =>
      this.addSelectableOnclick(
        Elements.enclosureSpace({player_id: pp.player_id, enclosure_id: 0, enclosure_pos: pp.barn_pos}),
        (evt) => this.selectDestinationForPurchase(pp)
    ));
    this.addRestartTurnButton();
  }

  private selectDestinationForPurchase(pp: PossiblePurchase) {
    this.initStatusBar(_("Select a destination for the purchased tile"));
    pp.dests.forEach((dest: Space) =>
      this.addSelectableOnclick(
        Elements.enclosureSpace({player_id: this.player_id, enclosure_id: dest.enclosure_id, enclosure_pos: dest.pos}),
        (evt) => {
          this.slide(
            Elements.enclosureSpaceTile({player_id: pp.player_id, enclosure_id: 0, enclosure_pos: pp.barn_pos}),
            Elements.enclosureSpace({player_id: this.player_id, enclosure_id: dest.enclosure_id, enclosure_pos: dest.pos}));
          this.confirmPurchase(pp, dest)
        }
      ));
    this.addRestartTurnButton();
  }

  private confirmPurchase(pp: PossiblePurchase, dest: Space) {
    this.initStatusBar(_("Confirm purchase"));
    this.addConfirmActionButton('actPurchaseTile', {
      from_player_id: pp.player_id,
      barn_pos: pp.barn_pos,
      enclosure_id: dest.enclosure_id,
      enclosure_pos: dest.pos
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
    this.addConfirmActionButton('actExpandZoo', {}, true);
    this.addRestartTurnButton(() => { this.game.renderExtensions(this.player_id, current); });
  }
};

class DrawTileFlow extends PlayFlow<undefined> {
  constructor(g: ZoolorettoGame) { super(g); }

  override doStart() {
    this.initStatusBar(_('Draw a tile? (cannot undo)'));
    this.markSelectable(Elements.topPile());
    this.addConfirmActionButton('actDrawTile');
    this.addRestartTurnButton();
  }
};

class PlaceDrawnTile extends ZooFlow<TruckLocation[]> {
  constructor(g: ZoolorettoGame) { super(g); }

  override doStart(available_spaces: TruckLocation[]) {
    console.log("starting PlaceDrawnTile flow", this);
    this.initStatusBar(_('Place the drawn tile'));
    this.markSelected(Elements.topPile());
    available_spaces.forEach(
      (truckLoc: TruckLocation) =>
        this.addSelectableOnclick(Elements.truckSpace(truckLoc), (evt) => this.placeDrawnTile(truckLoc)));
  }

  private placeDrawnTile(truckLoc: TruckLocation) {
    let tile = Elements.topPile()!;
    let space = Elements.truckSpace(truckLoc);
    this.slide(tile, space)
      .then(() => {
        // FIXME: can't we thread the tile into here from the args?
        this.confirmPlaceDrawnTile(tile.getAttribute(Attrs.TILE)!, truckLoc);
      });
  }

  private confirmPlaceDrawnTile(tile: string, tl: TruckLocation) {
    this.initStatusBar(_('Place tile ${tile} in truck ${truck_id} space ${truck_pos}?'),
        { tile: tile, truck_id: tl.truck_id, truck_pos: tl.truck_pos });
    this.addConfirmActionButton('actPlaceDrawnTileInTruck', tl, true);
    this.addRestartTurnButton();
  }
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
      this.addSelectableOnclick($(IDS.truck(truck.truck_id)), (evt) => this.chooseTruckTileToPlace(truck.truck_id, truck.playable, availableTrucks));
    });
  }

  private chooseTruckTileToPlace(truck_id: number, pps: PossiblePlacement[], availabeTrucks: AvailableTruck[]) {
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
        let elem = Elements.truckSpace({ truck_id: truck_id, truck_pos: pp.truck_pos});
        this.addSelectableOnclick(elem, (evt) => {
          elem.classList.add(CSS.SELECTED);
          this.chooseDestination(truck_id, pp, availabeTrucks);
        });
      });
    }
    this.addRestartTurnButton();
  }

  private chooseDestination(truck_id: number, pp: PossiblePlacement, availabeTrucks: AvailableTruck[]) {
    this.initStatusBar(_('Choose a destination for the selected tile'));
    this.addRestartTurnButton();
    pp.encs.forEach((pep: PossibleEnclosurePlacement) => {
      let encElem = Elements.enclosureSpace({ player_id: this.player_id, enclosure_id: pep.enclosure_id, enclosure_pos: pep.enclosure_pos});
      encElem.classList.add(CSS.TARGETABLE);
      this.addSelectableOnclick(encElem, (evt:MouseEvent) => {
        let tileElem = Elements.truckSpace({ truck_id: truck_id, truck_pos: pp.truck_pos}).firstElementChild as HTMLElement;
        this.slide(tileElem,encElem).then( () => {
          this.placedTiles.push({ truck_pos: pp.truck_pos, enclosure_id: pep.enclosure_id, enclosure_pos: pep.enclosure_pos});
          this.chooseTruckTileToPlace(truck_id, pep.next, availabeTrucks);
        });
      });
    });
  }
};

class DiscardTileFlow extends ZooFlow<number[]> {
  constructor(g : ZoolorettoGame) { super(g); }

  override doStart(discardables: number[]) {
    this.initStatusBar(_('Select a tile in your barn to discard'));
    this.addRestartTurnButton();

    discardables.forEach((pos: number) => {
      this.addSelectableOnclick(
        Elements.enclosureSpace({player_id: this.player_id, enclosure_id: 0, enclosure_pos: pos }),
        // FIXME: slide it offboard? need to adjust PlayFlow to "resuscitate" elements destroyed.
        (evt) => this.confirmDiscard(pos));
    });
  }

  private confirmDiscard(pos: number) {
    this.initStatusBar(_('Confirm discard'));
    this.addConfirmActionButton('actDiscardTile', { barn_pos: pos });
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
        Elements.enclosureSpace({player_id: this.player_id, enclosure_id: m.src.enclosure_id, enclosure_pos: m.src.pos}),
        (evt) => this.chooseDest(m)
      )
    });
  }

  private chooseDest(pm: PossibleMove) {
    this.initStatusBar(_('Select a destination space'));
    this.addRestartTurnButton();
    pm.dests.forEach((dest: Space) => {
      let elem = Elements.enclosureSpaceTile({player_id: this.player_id, enclosure_id: pm.src.enclosure_id, enclosure_pos: pm.src.pos});
      let destElem = Elements.enclosureSpace({player_id: this.player_id, enclosure_id: dest.enclosure_id, enclosure_pos: dest.pos})
      this.addSelectableOnclick(destElem,
        (evt) => this.slide(elem, destElem)
          .then(() => { destElem.classList.remove(CSS.SELECTED); destElem.classList.add(CSS.MOVED); this.confirmMove(pm.src, dest); })
      )
    });
  }

  private confirmMove(src: Space, dest: Space) {
    this.initStatusBar(_('Confirm move'));
    this.addConfirmActionButton('actMoveTile', {
      src_id: src.enclosure_id, src_pos: src.pos, dest_id: dest.enclosure_id, dest_pos: dest.pos
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

  private playerDiv(player?: ZPlayer): HTMLElement | undefined {
    if (!player) { return undefined; }

    let enclosure = (e: number, n: number): HTMLElement => {
      let elem = Html.div({id: IDS.enclosure(player.player_id, e) },
        ... ZoolorettoHtml.range(1, n).map(i => Html.div({ id: IDS.enclosureSpace(player.player_id, e, i), classes: "zoo-cell"} ))
      );
      elem.setAttribute(Attrs.ENCLOSURE, ""+e);
      return elem;
    };

    // FIXME: probably don't need "zoo-main-board" -- absence of "zoo-zoom" is enough
    const zoomClass = player.player_id != this.player_id ? "zoo-zoom" : "zoo-main-board";
    let elem = Html
      .div({ id: IDS.boardId(player.player_id), classes: [ 'zoo-board', zoomClass ]},
        enclosure(0, 20), // the barn
        enclosure(1, 6),
        enclosure(2, 6),
        enclosure(3, 7),
        enclosure(4, 6),
        this.twoPlayer ? enclosure(5, 6) : undefined
      );
    elem.setAttribute(Attrs.EXTENSIONS, ""+player.purchased_extensions);
    return elem;
  }

  private otherPlayerDiv(player: ZPlayer): HTMLElement {
    return Html
      .div({ id: `zoo-playerboard-${player.player_no}`, classes: [ "zoo-playerboard", "whiteblock" ] },
        Html.div({ id: `zoo-playername-${player.player_no}`, classes: "zoo-playername"},
          Html.span({ text: player.name })
        ),
        this.playerDiv(player)
      );
  }

  baseStructure(): HTMLElement {
    let currentPlayer = this.gamedatas.players[this.player_id];
    let otherplayers = Object.values(this.gamedatas.players).filter((p) => p != currentPlayer);

    return Html
      .div({id: IDS.GAME, classes: this.twoPlayer ? 'zoo-2p' : ''},
        Html.div({ id: 'zoo-upper-container' },
          Html.div({ id: IDS.DEPOT },
            ... this.gamedatas.trucks.map(truck =>
              Html.div({id: IDS.depotSpace(truck.truck_id), classes: CSS.DEPOT_SPACE },
                Html.div({ id: IDS.truck(truck.truck_id), classes: CSS.TRUCK },
                          ... truck.contents.map((contents, i) =>
                  Html.div({ id: IDS.truckSpace(truck.truck_id, contents.pos) })
                )
              )
            ))
          ),
          Html.div({ id: IDS.STOCK },
            Html.div({ id: IDS.PILE })
          )
        ),
        Html.div({id: 'zoo-boards' },
          this.playerDiv(currentPlayer),
          Html.div({id: 'zoo-other-playerboards'},
            ... otherplayers.map((p) => this.otherPlayerDiv(p)
          )
        )
      ),
      // this.div({id: 'zoo-playeraid' })
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

  private setSpanToTile(elem: HTMLElement, tile_type?: string) {
    if (!tile_type) {
      elem.removeAttribute(Attrs.TILE);
    } else {
      elem.setAttribute(Attrs.TILE, tile_type);
    }
  }

  private makeTileSpan(tile_type?: string): HTMLElement | undefined {
    return tile_type ? Html.span({ attrs: Attrs.tile(tile_type) }) : undefined;
  }

  private addStockTile(tile_type: string = 'back') {
    $(IDS.PILE).appendChild(this.makeTileSpan(tile_type)!);
  }

  private renderStock() : void {
    var n = Math.min(this.gamedatas.primary_stocksize, 5);
    // FIXME: handle exhausted primary pile
    /*
    if (n < 5) {
      this.addStockTile(CSS.DISK);
    }
      */
    if (this.gamedatas.drawntile) {
      n--;
    }
    while  (n-- > 0) {
      this.addStockTile();
    }
    if (this.gamedatas.drawntile) {
      this.addStockTile(this.gamedatas.drawntile);
    }
  }

  private renderTrucks(): void {
    for (let truck of this.gamedatas.trucks) {
      truck.contents.forEach(contents => {
        let tileSpan = this.makeTileSpan(contents.tile_type);
        if (tileSpan) {
          Elements.truckSpace({truck_id: truck.truck_id, truck_pos: contents.pos}).append(tileSpan);
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
    for (let penc of this.gamedatas.player_enclosures) {
      penc.player_id;
      for (let enc of penc.enclosures) {
        for (let es of enc.spaces) {
          let span = this.makeTileSpan(es.tile_type);
          if (span) {
            Elements.enclosureSpace({player_id: penc.player_id, enclosure_id: enc.enclosure_id, enclosure_pos: es.pos})
              .append(span);
          }
        }
      }
    }
  }

  private moneyCounter: Counter[] = [];

  private setupHtml(twoPlayer: boolean): void {
    let zhtml = new ZoolorettoHtml(this.gamedatas, this.player_id);
    this.getGameAreaElement().appendChild(zhtml.baseStructure());
    for (const player of Object.values(this.gamedatas.players)) {
      this.getPlayerPanelElement(player.player_id).appendChild(zhtml.playerPanel(player));
      let counter = new ebg.counter();
      counter.create(IDS.money(player.player_id), { value: player.money });
      this.moneyCounter[player.player_id] = counter;
    }
    this.renderStock();
    this.renderTrucks();
    this.renderEnclosures();
  }

  override setup(gamedatas: ZGamedatas) {
    super.setup(gamedatas);

    const twoPlayer = Object.keys(gamedatas.players).length == 2;
    this.setupHtml(twoPlayer);
    this.setupNotifications();

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

  private updateMoney(player_id: number, money: number): void {
    this.moneyCounter[player_id]!.toValue(money);
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
    if (playState.can_draw) {
      this.statusBar.addActionButton(_('Draw tile'), () => new DrawTileFlow(this).start());
    }
    if (playState.available_trucks.length > 0) {
      this.statusBar.addActionButton(_('Take truck'), () => new TakeTruckFlow(this).start(playState.available_trucks));
    }
    if (playState.possible_moves.length > 0) {
      this.statusBar.addActionButton(_('Move tile'), () => new MoveTileFlow(this).start(playState.possible_moves));
    }
    if (playState.possible_exchanges.length > 0) {
      this.statusBar.addActionButton(_('Exchange animals'), () => new ExchangeFlow(this).start(playState.possible_exchanges));
    }
    if (playState.possible_purchases.length > 0) {
      this.statusBar.addActionButton(_('Purchase tile'), () => new PurchaseTileFlow(this).start(playState.possible_purchases));
    }
    if (playState.possible_discards.length > 0) {
      this.statusBar.addActionButton(_('Discard tile'), () => new DiscardTileFlow(this).start(playState.possible_discards));
    }
    if (playState.can_expand) {
      this.statusBar.addActionButton(_('Expand zoo'), () => new ExpandZooFlow(this).start());
    }
  }

  private onUpdateActionButtons_PlaceDrawnTile(args: PlaceDrawnTileArgs): void {
    new PlaceDrawnTile(this).start(args.available_spaces);
  }

  private async notif_DrawTile(
    args: {
      tile_type: string,
      drawn_from_endgame_pile: boolean,
      primary_left: number,
      endgame_left: number,
    }
  ): Promise<void> {
    // FIXME: need to handle disk removal / pile exhaustion
    // FIXME: animate "flip" the top tile?
    this.setSpanToTile(Elements.topPile()!, args.tile_type);
  }

  private async notif_PlaceDrawnTileInTruck(args: {
    player_id: number,
    tile_id: number,
    val: string,
    truck_id: number,
    truck_pos: number,
    primary_stock_size: number,
    endgame_stock_size: number }) {
    // FIXME: need to handle stock refresh -- need to send pile sizes in the notif args
    if (this.player_id != args.player_id) {
      await this.animationManager.slideAndAttach(Elements.topPile()!, Elements.truckSpace(args), {})
        .then(() => this.addStockTile());
    } else {
      this.addStockTile();
    }
  }

  private async notif_ExpandZoo(args: {
      player_id: number,
      purchased_extensions: number,
      money: number
    }) {
    if (this.player_id != args.player_id) {
      this.renderExtensions(args.player_id, args.purchased_extensions);
    }
    this.updateMoney(args.player_id, args.money);
  }

  private async notif_TakeTruckAndPlaceTiles(args: {
    player_id: number,
    truck_id: number,
    money: number,
    deliveries: Delivery[]
  }) {
    let anims : AnimationList = [];
    args.deliveries.forEach( (del) => {
      let pl = del.placement;
      if (pl == 'coin') {
        anims.push(() => this.animationManager.slideOutAndDestroy(
          Elements.truckSpaceTile({truck_id: args.truck_id, truck_pos: del.truck_pos})!,
          this.getPlayerPanelElement(args.player_id),
          {}
        ).then(() => this.addMoney(args.player_id, 1)));
      } else {
        // FIXME: figure out a more elegant way to not replay what the active player did.
        // Possibly do all of it (including money increase, offspring, etc) during
        //   the turn? Would need to send all of that forward in "possible moves".
        if (args.player_id != this.player_id) {
          anims.push(() => this.animationManager.slideAndAttach(
            Elements.truckSpaceTile({truck_id: args.truck_id, truck_pos: del.truck_pos})!,
            Elements.enclosureSpace({player_id: args.player_id, enclosure_id: pl.enclosure_id, enclosure_pos: pl.enclosure_pos }),
            {})
          );
        }
        // for now, we don't animate offspring appearing until committed.
        if (pl.child_type) {
          anims.push(() => {
            let elem = this.makeTileSpan(pl.child_type)!;
            let parent = Elements.enclosureSpace({player_id: args.player_id, enclosure_id: pl.child_enclosure_id, enclosure_pos: pl.child_enclosure_pos});
            parent.appendChild(elem);
            return this.animationManager.slideIn(elem, $(IDS.OFF_BOARD));
          });
        }
      }
    });
    anims.push(() => this.animationManager.slideAndAttach(
        Elements.truck(args.truck_id),
        $(IDS.takenTruck(args.player_id)), {}));
    await this.animationManager.playSequentially(anims);
  }

  private async notif_MoveTile(args: {player_id: number, src: Space, dest: Space, money: number}) {
    if (args.player_id != this.player_id) {
      this.animationManager.slideAndAttach(
        Elements.enclosureSpaceTile({player_id: args.player_id, enclosure_id: args.src.enclosure_id, enclosure_pos: args.src.pos}),
        Elements.enclosureSpace({player_id: args.player_id, enclosure_id: args.dest.enclosure_id, enclosure_pos: args.dest.pos}),
        {});
    }
    this.updateMoney(args.player_id, args.money);
  }

  private async notif_DiscardTile(args: { player_id: number, money: number, barn_pos: number }) {
    await this.animationManager.slideOutAndDestroy(
      Elements.enclosureSpaceTile({player_id: args.player_id, enclosure_id: 0, enclosure_pos: args.barn_pos}),
      $(IDS.OFF_BOARD),
      {});
    this.updateMoney(args.player_id, args.money);
  }

  private async notif_PurchaseTile(args: {
			player_id: number,
			from_player_id: number,
			barn_pos: number,
			enclosure_id: number,
			enclosure_pos: number,
			tile: string,
			money: number,
      from_player_money: number,
    }) {
      if (this.player_id != args.player_id) {
        await this.animationManager.slideAndAttach(
          Elements.enclosureSpaceTile({player_id: args.from_player_id, enclosure_id: 0, enclosure_pos: args.barn_pos}),
          Elements.enclosureSpace({player_id: args.player_id, enclosure_id: args.enclosure_id, enclosure_pos: args.enclosure_pos}),
          {});
      }
      this.updateMoney(args.player_id, args.money);
      this.updateMoney(args.from_player_id, args.from_player_money);
  }

  private async notif_EndTurn(args: {
    truck_ids_returned: number[],
    truck_dumped_pos: { truck_id: number, dumped_pos: number[] }[],
    last_round: boolean }) {

    let anims: AnimationList = [];
    args.truck_dumped_pos.forEach(t =>
      t.dumped_pos.forEach(p =>
        anims.push(() =>
          this.animationManager.slideOutAndDestroy(
            Elements.truckSpaceTile({truck_id: t.truck_id, truck_pos: p})!, $(IDS.OFF_BOARD), {} )
        )
      )
    );
    args.truck_ids_returned.forEach(tid =>
      anims.push(() => this.animationManager.slideAndAttach(Elements.truck(tid), $(IDS.depotSpace(tid)), {}))
    );

    await this.animationManager.playSequentially(anims).then( () => {
      if (args.last_round) {
        (this as any).addLastTurnBanner(_('This is the last turn!'));
      }
    })
  }

  private notif_ExchangeEnclosureAnimals(args: {
    player_id: number,
    player_name: number
		src_enclosure_id: number,
		src_positions: number[],
		dest_enclosure_id: number,
		dest_positions: number[],
		src_animal_type: string,
		dest_animal_type: string,
    money: number,
  }) {
    if (args.player_id == this.player_id) {
      this.updateMoney(args.player_id, args.money);
      return;
    }
    // FIXME: unify this with the body of ExchangeFlow::confirm
    let anims: AnimationList = [];
    for (let i = 0; i < args.src_positions.length; ++i) {
      let srcArg = {player_id: args.player_id, enclosure_id: args.src_enclosure_id, enclosure_pos: args.src_positions[i]!};
      let destArg = {player_id: args.player_id, enclosure_id: args.dest_enclosure_id, enclosure_pos: args.dest_positions[i]!};
      let srcElem = Elements.enclosureSpaceTile(srcArg);
      if (srcElem) {
        anims.push(() => this.animationManager.slideAndAttach(
          srcElem,
          Elements.enclosureSpace(destArg),
          {}
        ));
      }
      let destElem = Elements.enclosureSpaceTile(destArg);
      if (destElem) {
        anims.push(() => this.animationManager.slideAndAttach(destElem, Elements.enclosureSpace(srcArg),{}));
      }
    }
    this.animationManager.playParallel(anims).then(()=>this.updateMoney(args.player_id, args.money));
  }

  private async notif_debugReset(): Promise<void> {
    window.location.reload();
  }

  private async notif_LastRound(): Promise<void> {
    // (1) if first tile of endgame pile taken, remove disk (slide off board)
    // (2) add banner
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
