type AnimationList = (() => Promise<any>)[];

interface ZPlayer extends Player {
  player_id: number;
  player_no: number;
  money: number;
  purchased_extensions: number;
}

interface Placement {
  truck_pos: number;
  placement: EnclosurePlacement | 'coin';
}

interface EnclosurePlacement {
  enclosure_id: number;
  enclosure_pos: number;
}

interface TruckLocation{ truck_id: number, truck_pos: number };
interface PlaceDrawnTileArgs { available_spaces: TruckLocation[] };

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
  static enclosure(player_no: number, enclosure_id: number): string { return `enclosure-${player_no}-${enclosure_id}`; }
  static enclosureSpace(player_no: number, enclosure_id: number, pos: number): string { return `enclosure-${player_no}-${enclosure_id}-${pos}`; }
  static takenTruck(player_id: number): string { return `zoo-taken-truck-${player_id}`; }
  static moneyCounter(player_id: number): string { return `playermoney-counter-${player_id}` };

}

class CSS {
  static readonly TRUCK = 'zoo-truck';
  static readonly TARGETABLE = 'zoo-targetable';
  static readonly SELECTABLE = 'zoo-selectable';
  static readonly SELECTED = 'zoo-selected';
  static readonly MOVED = 'moved';
  static readonly DEPOT_SPACE = 'zoo-depot-space';
  static readonly DISK = 'zoo-disk';
}

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
  // Needs to include enclosure contents, including barns.
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

interface PlayState {
  can_draw: boolean;
  can_purchase: boolean;
  can_take_truck: boolean;
  can_buy: boolean;
  can_swap: boolean;
  can_move: boolean;
  available_trucks: AvailableTruck[];
  discardables: number[];
  possible_moves: PossibleMove[];
}

abstract class PlayFlow {
    protected game: ZoolorettoGame;
    constructor(g : ZoolorettoGame) { this.game = g; }
    private onClickAbortController : AbortController = new AbortController();


    // FIXME: note the moves/slide/rollback here -- that's reusable!
    protected moves: {origin: HTMLElement, dest: HTMLElement, elem: HTMLElement }[] = [];

    protected slide(elem: HTMLElement, newParent: HTMLElement): Promise<any> {
      this.moves.push({origin: elem.parentElement as HTMLElement, dest: newParent, elem: elem });
      return this.game.animationManager.slideAndAttach(elem, newParent, {});
    }

    protected async rollback(): Promise<any> {
      let anims: (() => Promise<any>)[] = [];
      while (this.moves.length > 0) {
        let move = this.moves.pop()!;
        anims.push(() =>
          this.game.animationManager.slideAndAttach(move.elem, move.origin, {}));
      }
      await this.game.animationManager.playSequentially(anims);
    }

    protected initStatusBar(title: string) {
      this.game.statusBar.removeActionButtons();
      this.game.statusBar.setTitle(title);
    }

    protected addCancelButton(onCancel?: CallableFunction): void {
      this.game.statusBar.addActionButton(_('Restart turn'),
          () => {
            this.rollback();
            this.game.statusBar.removeActionButtons();
            onCancel && onCancel();
            this.game.restoreServerGameState();
          },
        { color: "secondary"});
    }
    private clearOnclicks(): void {
      this.onClickAbortController.abort();
      this.onClickAbortController = new AbortController();
      document.querySelectorAll(`#${IDS.GAME} .${CSS.TARGETABLE}`).forEach((elem) => elem.classList.remove(CSS.TARGETABLE));
    }

    protected addSelectableOnclick(elem: HTMLElement, onclick: (evt: MouseEvent) => any ) {
      elem.classList.add(CSS.TARGETABLE);
      elem.addEventListener(
        "click",
        (ev: MouseEvent) => { this.clearOnclicks(); onclick(ev); },
        { signal: this.onClickAbortController.signal });
    }
  }

/** Game class */
class ZoolorettoGame extends BaseGame<ZGamedatas> {
  constructor() {
    super([]);
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

  private playerNumber(player_id : number): number {
    return this.gamedatas.players[player_id]!.player_no;
  }

  private playerDiv(player?: ZPlayer): HTMLElement | undefined {
    if (!player) { return undefined; }

    const pno = player.player_no;

    let enclosure = (e: number, n: number): HTMLElement => {
      let elem = this.div({id: IDS.enclosure(pno, e) },
        ... this.range(1, n).map(i => this.div({ id: IDS.enclosureSpace(pno, e, i), classes: "zoo-cell"} ))
      );
      elem.setAttribute(Attrs.ENCLOSURE, ""+e);
      return elem;
    };

    const board_id = player.player_id == this.player_id ? "zoo-main-board" : ("zoo-board-" + pno);
    const zoomClass = player.player_id != this.player_id ? "zoo-zoom" : "";
    let elem = this
      .div({ id: board_id, classes: [ 'zoo-board', zoomClass ]},
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
    return this
      .div({ id: `zoo-playerboard-${player.player_no}`, classes: [ "zoo-playerboard", "whiteblock" ] },
        this.div({ id: `zoo-playername-${player.player_no}`, classes: "zoo-playername"},
          this.span({ text: player.name })
        ),
        this.playerDiv(player)
      );
  }

  private baseHtml(): HTMLElement {
    let currentPlayer = this.gamedatas.players[this.player_id];
    let otherplayers = Object.values(this.gamedatas.players).filter((p) => p != currentPlayer);

    return this
      .div({id: IDS.GAME, classes: this.twoPlayer ? 'zoo-2p' : ''},
        this.div({ id: 'zoo-upper-container' },
          this.div({ id: IDS.DEPOT }),
          this.div({ id: IDS.STOCK },
            this.div({ id: IDS.PILE })
          )
        ),
        this.div({id: 'zoo-boards' },
          this.playerDiv(currentPlayer),
          this.div({id: 'zoo-other-playerboards'},
            ... otherplayers.map((p) => this.otherPlayerDiv(p)
          )
        )
      ),
      // this.div({id: 'zoo-playeraid' })
    );
  }

  private setSpanToTile(elem: HTMLElement, tile_type?: string) {
    if (!tile_type) {
      elem.removeAttribute(Attrs.TILE);
    } else {
      elem.setAttribute(Attrs.TILE, tile_type);
    }
  }

  private makeTileSpan(tile_type?: string): HTMLElement | undefined {
    return tile_type ? this.span({ attrs: Attrs.tile(tile_type) }) : undefined;
  }

  private makeTruckDiv(truck: Truck): HTMLElement {
    return this.div({ id: IDS.truck(truck.truck_id), classes: CSS.TRUCK },
          ... truck.contents.map((contents, i) =>
            this.div({ id: IDS.truckSpace(truck.truck_id, contents.pos) },
              this.makeTileSpan(contents.tile_type)
            )
          )
        );
  }

  private addStockTile(tile_type: string = 'back') {
    this.pileElem.appendChild(this.makeTileSpan(tile_type)!);
  }

  private pileElem : HTMLElement;
  private depotElem : HTMLElement;
  private twoPlayer: boolean;

  private setupStock() : void {
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

  private setupGameHtml(): void {
    this.getGameAreaElement().appendChild(this.baseHtml());
    this.pileElem = $(IDS.PILE);
    this.depotElem = $(IDS.DEPOT);
  }

  private setupPlayerPanel(player: ZPlayer): void {
    const playerId = player.player_id;
    console.log('Setting up panel for player ' + player.player_id);
    this.getPlayerPanelElement(playerId).append(
      this.div({ classes: 'zoo-player-panel-ext'},
        this.span({ classes: 'zoo-money'},
          this.span({classes: 'zoo-money-label'}),
          this.span({text: ': '}),
          this.span({id: IDS.moneyCounter(playerId), text: `${player.money}`})),
        this.div({ classes: CSS.DEPOT_SPACE, id: IDS.takenTruck(playerId)}),
      )
    );
    /*
      FIXME: need to manually update money, and thread changes in notifs.
    const counter = new ebg.counter();
    counter.create(
        moneyid,
        { value: player.money, playerCounter: 'playermoney', playerId: playerId }
    );
    */
  }

  private setupTrucks(): void {
    for (let truck of this.gamedatas.trucks) {
      let depotSpaceDiv = this.div({id: IDS.depotSpace(truck.truck_id), classes: CSS.DEPOT_SPACE });
      this.depotElem.append(depotSpaceDiv);
      let truckDiv = this.makeTruckDiv(truck);
      if (truck.taken_by_player_id) {
        $(IDS.takenTruck(truck.taken_by_player_id)).appendChild(truckDiv);
      } else {
        depotSpaceDiv.append(truckDiv);
      }
    }
  }

  private setupEnclosures(): void {
    for (let penc of this.gamedatas.player_enclosures) {
      penc.player_id;
      for (let enc of penc.enclosures) {
        for (let es of enc.spaces) {
          let span = this.makeTileSpan(es.tile_type);
          if (span) {
            this.enclosureSpaceElem({player_id: penc.player_id, enclosure_id: enc.enclosure_id, enclosure_pos: es.pos})
              .append(span);
          }
        }
      }
    }
  }

  override setup(gamedatas: ZGamedatas) {
    super.setup(gamedatas);
    this.twoPlayer = Object.keys(gamedatas.players).length == 2;
    this.setupGameHtml();

    console.log('setting up player boards', gamedatas.players);
    for (const player of Object.values(gamedatas.players)) {
      this.setupPlayerPanel(player);
    }

    this.setupStock();
    this.setupTrucks();
    this.setupEnclosures();

    this.bgaSetupPromiseNotifications({ logger: console.log, onEnd: this.addTooltipsToLog.bind(this) });

        // Active player gets their own undo notification with private data,
        //   so ignore the generic undo notification.
        /*
        this.notifqueue.setIgnoreNotificationCheck(
          'undoMove',
          (notif: any) => (notif.args.player_id == this.player_id));
*/
    console.log('Game setup done');
  }

  /**
   *
   * Need to write 1 or 2 more of these for the pattern(s) to become apparent.
   *
   * Consider these conceptual things:
   *   1) "targetables" -- destinations for moves
   *   2) "selectables" -- things that can be moved
   *   3) "moved" -- things that were just moved
   * Use these for CSS class names, etc, instead of the generic "highlight"
   *

   */

  private onClickAbortController : AbortController = new AbortController();

  private markMoved(elem: HTMLElement): void {
    elem.classList.add(CSS.MOVED);
  }

  private unmarkMoved(elem: HTMLElement): void {
    elem.classList.remove(CSS.MOVED);
  }

  private topPileElem(): HTMLElement | undefined {
      return this.pileElem.lastElementChild as (HTMLElement | undefined);
  }

  private truckElem(truck_id: number) : HTMLElement {
    return $(IDS.truck(truck_id));
  }

  private truckSpaceElem(args: { truck_id: number, truck_pos: number }) : HTMLElement{
    return $(IDS.truckSpace(args.truck_id, args.truck_pos))
  }

  private truckSpaceTile(args: { truck_id: number, truck_pos: number }) : (HTMLElement | undefined) {
    return this.truckSpaceElem(args).firstChild as (HTMLElement | undefined);
  }

  private enclosureSpaceElem(args: { player_id: number, enclosure_id: number, enclosure_pos: number }) : HTMLElement {
    return $(IDS.enclosureSpace(this.playerNumber(args.player_id), args.enclosure_id, args.enclosure_pos));
  }

  private enclosureSpaceTileElem(args: { player_id: number, enclosure_id: number, enclosure_pos: number }) : HTMLElement {
    return this.enclosureSpaceElem(args).firstElementChild as HTMLElement;
  }

  private truckLocFromId(id: string): TruckLocation {
    let parts = id.split('-');
    return {
      truck_id: Number(parts[1]),
      truck_pos: Number(parts[2]),
    }
  }

  private clearOnclicks(): void {
    this.onClickAbortController.abort();
    this.onClickAbortController = new AbortController();
    document.querySelectorAll(`#${IDS.GAME} .${CSS.TARGETABLE}`).forEach((elem) => elem.classList.remove(CSS.TARGETABLE));
  }

  addSelectableOnclick(elem: HTMLElement, onclick: (evt: MouseEvent) => any ) {
    elem.classList.add(CSS.TARGETABLE);
    elem.addEventListener(
      "click",
      (ev: MouseEvent) => { this.clearOnclicks(); onclick(ev); },
      { signal: this.onClickAbortController.signal });
  }

  // FIXME: separate "cancel" from "restart turn"
  addCancelButton(onCancel?: CallableFunction): void {
      this.statusBar.addActionButton(_('Restart turn'),
          () => {
            this.statusBar.removeActionButtons();
            onCancel && onCancel();
            this.restoreServerGameState();
          },
        { color: "secondary"});
  }

  //
  // Entry point for player turn
  //

  private onUpdateActionButtons_PlayerTurn(playState: PlayState): void {
    this.statusBar.removeActionButtons();
    if (playState.can_draw) {
      this.statusBar.addActionButton(_('Draw tile'), () => this.client_ConfirmDrawTile());
    }
    if (playState.can_purchase) {
      this.statusBar.addActionButton(_('Purchase extension'), () => this.client_PurchaseExtension());
    }
    if (playState.available_trucks.length > 0) {
      this.statusBar.addActionButton(_('Take truck'), () => this.TakeTruck.takeTruck(playState));
    }
    if (playState.discardables.length > 0) {
      this.statusBar.addActionButton(_('Discard a tile'), () => this.DiscardTile.discardTile(playState.discardables));
    }
    if (playState.possible_moves.length > 0) {
      this.statusBar.addActionButton(_('Move a tile'), () => this.MoveTile.moveTile(playState.possible_moves));
    }
  }


  //
  // Draw a tile
  //

  private client_ConfirmDrawTile(): void {
    this.statusBar.removeActionButtons();
    this.statusBar.setTitle(_('Draw a tile? (cannot undo)'));
    this.statusBar.addActionButton(
      _('Confirm'),
      () => this.bgaPerformAction('actDrawTile'),
      { autoclick: false }
    );
    this.statusBar.addActionButton(
      _('Cancel'),
      () => {
        this.statusBar.removeActionButtons();
        this.restoreServerGameState();
      },
      { color: "secondary"}
    );
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
    // FIXME: "flip" the top tile?
    this.setSpanToTile(this.topPileElem()!, args.tile_type);
  }

  //
  // Place a drawn tile
  //    note this is a separate server state
  //

  private onUpdateActionButtons_PlaceDrawnTile(args: PlaceDrawnTileArgs): void {
    this.statusBar.removeActionButtons();
    args.available_spaces.forEach(
      (truckLoc: TruckLocation) =>
        this.addSelectableOnclick(this.truckSpaceElem(truckLoc), this.onclick_PlaceDrawnTile.bind(this)));
  }

  private onclick_PlaceDrawnTile(evt: MouseEvent) {
    let space = evt.target as HTMLElement;
    let tile = this.topPileElem()!;
    this.animationManager.slideAndAttach(tile, space, {})
      .then(() => {
        this.markMoved(space);
        this.client_ConfirmPlaceDrawnTile(space, tile);
      });
    return true;
  }

  private client_ConfirmPlaceDrawnTile(space: HTMLElement, tile: HTMLElement): void {
    let tl = this.truckLocFromId(space.id);
    this.statusBar.setTitle(
      _('Place tile ${tile} in truck ${truck_id} space ${truck_pos}?'),
      { tile: tile.getAttribute(Attrs.TILE), truck_id: tl.truck_id, truck_pos: tl.truck_pos }
    );
    this.statusBar.addActionButton(
      _('Confirm'),
      () => {
        this.statusBar.removeActionButtons();
        this.unmarkMoved(space);
        this.bgaPerformAction('actPlaceDrawnTileInTruck', this.truckLocFromId(space.id))
      },
      { autoclick: true }
    );
    this.statusBar.addActionButton(
      _('Cancel'),
      () => {
        this.statusBar.removeActionButtons();
        this.unmarkMoved(space);
        this.restoreServerGameState();
        this.animationManager.slideAndAttach(tile, this.pileElem, {});
      },
      { color: "secondary"}
    );
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
      await this.animationManager.slideAndAttach(this.topPileElem()!, this.truckSpaceElem(args), {})
        .then(() => this.addStockTile());
    } else {
      this.addStockTile();
    }
  }

  //
  // Purchase an extension
  //

  private renderExtensions(extensions: number, player_id? : number): void {
    let elem = $(player_id ? ('zoo-board-' + this.gamedatas.players[player_id]?.player_no) : 'zoo-main-board');
    elem.setAttribute(Attrs.EXTENSIONS, String(extensions));
  }

  private getCurrentExtensions(player_id? : number): number {
    let elem = $(player_id ? ('zoo-board-' + this.gamedatas.players[player_id]?.player_no) : 'zoo-main-board');
    return Number(elem.getAttribute(Attrs.EXTENSIONS) || 0);
  }

  private client_PurchaseExtension(): void {
    this.statusBar.removeActionButtons();
    let current = this.getCurrentExtensions();
    this.renderExtensions(current + 1);
    this.statusBar.addActionButton(_('Confirm purchase'), () => this.bgaPerformAction('actPurchaseExtension'), { autoclick: true });
    this.addCancelButton(() => { this.renderExtensions(current); });
  }

  private updateMoney(player_id: number, money: number): void {
    $(IDS.moneyCounter(player_id)).innerText = `${money}`;
  }

  private async notif_PurchaseExtension(args: {
      player_id: number, purchased_extensions: number, money: number
    }) {
    if (this.player_id != args.player_id) {
      this.renderExtensions(args.purchased_extensions, args.player_id);
    }
    this.updateMoney(args.player_id, args.money);
  }

  //
  // Take a truck
  //

  private TakeTruck = new class {
    private game: ZoolorettoGame;
    private placedTiles : PlacedTile[] = [];
    private truck_id: number;
    constructor(g : ZoolorettoGame) {
      this.game = g;
    }

    cleanup() {
      this.truck_id = 0;
      this.placedTiles = [];
      document.querySelectorAll(`#${IDS.GAME} .${CSS.TARGETABLE}`).forEach((elem) => elem.classList.remove(CSS.TARGETABLE));
      document.querySelectorAll(`#${IDS.GAME} .${CSS.SELECTED}`).forEach((elem) => elem.classList.remove(CSS.SELECTED));
      document.querySelectorAll(`#${IDS.GAME} .${CSS.SELECTABLE}`).forEach((elem) => elem.classList.remove(CSS.SELECTABLE));
    }

    addCancelButton() {
      this.game.addCancelButton(() => {
        if (this.truck_id == 0) {
          this.cleanup();
          return;
        }
        this.game.animationManager.playParallel(
          this.placedTiles.map((pt:PlacedTile) => () => {
              let tileElem = this.game.enclosureSpaceElem({
                player_id: this.game.player_id,
                enclosure_id: pt.enclosure_id,
                enclosure_pos: pt.enclosure_pos,
              } ).firstElementChild as HTMLElement;
              let truckSpaceElem = this.game.truckSpaceElem({truck_id: this.truck_id, truck_pos: pt.truck_pos});
              return this.game.animationManager.slideAndAttach(tileElem, truckSpaceElem);
            }
          )
        ).then(() => this.cleanup())
      });
    }

    takeTruck(playState: PlayState) {
      this.game.statusBar.removeActionButtons();
      this.game.statusBar.setTitle(_('Select a truck'));
      this.addCancelButton();
      this.placedTiles = [];
      playState.available_trucks.forEach((truck: AvailableTruck) => {
        this.game.addSelectableOnclick($(IDS.truck(truck.truck_id)), (evt) => this.chooseTruckTileToPlace(truck.truck_id, truck.playable, playState));
      });
    }

    private chooseTruckTileToPlace(truck_id: number, pps: PossiblePlacement[], playState: PlayState) {
      this.truck_id = truck_id;
      this.game.statusBar.removeActionButtons();
      if (pps.length == 0) {
        this.game.statusBar.setTitle(_('Confirm your truck tile placements'));
        this.game.statusBar.addActionButton(
          _('Confirm'),
          () => {
            this.game.bgaPerformAction('actTakeTruckAndPlaceTiles', {
              truck_id: truck_id,
              placed_tiles: JSON.stringify(this.placedTiles),
            }).then(() => this.cleanup())
              // // FIXME:
              // // .then(() => slide coins off )
              // .then(() => this.game.animationManager.slideAndAttach(
              //   this.game.truckElem(truck_id),
              //   $(IDS.takenTruck(this.game.player_id)), {}))
              // .then(() => this.cleanup())
          }
        )
      }
      else {
        this.game.statusBar.setTitle(_('Choose a tile to place from the selected truck'));
        pps.forEach((pp: PossiblePlacement) => {
          let elem = this.game.truckSpaceElem({ truck_id: truck_id, truck_pos: pp.truck_pos});
          this.game.addSelectableOnclick(elem, (evt) => {
            elem.classList.add(CSS.SELECTED);
            this.client_PlaceTruckTile(truck_id, pp, playState);
          });
        });
      }
      this.addCancelButton();
    }

    private client_PlaceTruckTile(truck_id: number, pp: PossiblePlacement, playState: PlayState) {
      this.game.statusBar.removeActionButtons();
      this.game.statusBar.setTitle(_('Choose a destination for the selected tile'));
      this.addCancelButton();
      pp.encs.forEach((pep: PossibleEnclosurePlacement) => {
        let encElem = this.game.enclosureSpaceElem({ player_id: this.game.player_id, enclosure_id: pep.enclosure_id, enclosure_pos: pep.enclosure_pos});
        encElem.classList.add(CSS.TARGETABLE);
        this.game.addSelectableOnclick(encElem, (evt:MouseEvent) => {
          let tileElem = this.game.truckSpaceElem({ truck_id: truck_id, truck_pos: pp.truck_pos}).firstElementChild as HTMLElement;
          this.game.animationManager.slideAndAttach(tileElem,encElem, {}).then( () => {
            this.placedTiles.push({ truck_pos: pp.truck_pos, enclosure_id: pep.enclosure_id, enclosure_pos: pep.enclosure_pos});
            this.chooseTruckTileToPlace(truck_id, pep.next, playState);
          });
        });
      });
    }
  }(this);

  private async notif_TakeTruckAndPlaceTiles(args: {
    player_id: number,
    truck_id: number,
    placements: Placement[]
  }) {
    let anims : (() => Promise<any>)[] = [];
    args.placements.forEach( (p) => {
      let pl = p.placement;
      if (pl == 'coin') {
        anims.push(() => this.animationManager.slideOutAndDestroy(
          this.truckSpaceTile({truck_id: args.truck_id, truck_pos: p.truck_pos})!,
          this.getPlayerPanelElement(args.player_id),
          {}
        ));
      } else if (args.player_id != this.player_id) {
        anims.push(() => this.animationManager.slideAndAttach(
          this.truckSpaceTile({truck_id: args.truck_id, truck_pos: p.truck_pos})!,
          this.enclosureSpaceElem({player_id: args.player_id, enclosure_id: pl.enclosure_id, enclosure_pos: pl.enclosure_pos }),
          {})
        );
      }
    });
    anims.push(() => this.animationManager.slideAndAttach(
        this.truckElem(args.truck_id),
        $(IDS.takenTruck(args.player_id)), {}));
    await this.animationManager.playSequentially(anims);
  }

  private MoveTile = new class extends PlayFlow {
    constructor(g : ZoolorettoGame) { super(g); }

    public moveTile(possibleMoves: PossibleMove[]) {
      this.initStatusBar(_('Select a tile to move'));
      this.addCancelButton();
      possibleMoves.forEach((m: PossibleMove) => {
        this.addSelectableOnclick(
          this.game.enclosureSpaceElem({player_id: this.game.player_id, enclosure_id: m.src.enclosure_id, enclosure_pos: m.src.pos}),
          (evt) => this.chooseDest(m)
        )
      });
    }

    private chooseDest(pm: PossibleMove) {
      this.initStatusBar(_('Select a destination space'));
      this.addCancelButton();
      pm.dests.forEach((dest: Space) => {
        let elem = this.game.enclosureSpaceTileElem({player_id: this.game.player_id, enclosure_id: pm.src.enclosure_id, enclosure_pos: pm.src.pos});
        let destElem = this.game.enclosureSpaceElem({player_id: this.game.player_id, enclosure_id: dest.enclosure_id, enclosure_pos: dest.pos})
        this.addSelectableOnclick(destElem,
          (evt) => this.slide(elem, destElem)
            .then(() => this.confirmMove(pm.src, dest))
        )
      });
    }

    private confirmMove(src: Space, dest: Space) {
      this.initStatusBar(_('Confirm move'));
      this.game.statusBar.addActionButton(_('Confirm'), () => {
        this.game.bgaPerformAction('actMoveTile', { src_id: src.enclosure_id, src_pos: src.pos, dest_id: dest.enclosure_id, dest_pos: dest.pos });
      }, {});
      this.addCancelButton();
    }
  }(this);

  private async notif_MoveTile(args: {player_id: number, src: Space, dest: Space, money: number}) {
    if (args.player_id != this.player_id) {
      this.animationManager.slideAndAttach(
        this.enclosureSpaceTileElem({player_id: args.player_id, enclosure_id: args.src.enclosure_id, enclosure_pos: args.src.pos}),
        this.enclosureSpaceElem({player_id: args.player_id, enclosure_id: args.dest.enclosure_id, enclosure_pos: args.dest.pos}),
        {});
    }
    this.updateMoney(args.player_id, args.money);
  }

  private DiscardTile = new class extends PlayFlow {
    constructor(g : ZoolorettoGame) { super(g); }

    public discardTile(discardables: number[]) {
      this.initStatusBar(_('Select a truck'));
      this.addCancelButton();

      discardables.forEach((pos: number) => {
        this.addSelectableOnclick(
          this.game.enclosureSpaceElem({player_id: this.game.player_id, enclosure_id: 0, enclosure_pos: pos }),
          // FIXME: slide it offboard? need to adjust PlayFlow to "resuscitate" elements destroyed.
          (evt) => this.confirmDiscard(pos));
      });
    }

    private confirmDiscard(pos: number) {
      this.initStatusBar(_('Confirm discard'));
      this.game.statusBar.addActionButton(
        _('Confirm'),
        () => this.game.bgaPerformAction('actDiscardTile', { barn_pos: pos }),
        { autoclick: false }
      );
      this.addCancelButton();
    }
  }(this);

  private async notif_DiscardTile(args: { player_id: number, money: number, barn_pos: number }) {
    await this.animationManager.slideOutAndDestroy(
      this.enclosureSpaceTileElem({player_id: args.player_id, enclosure_id: 0, enclosure_pos: args.barn_pos}),
      $(IDS.OFF_BOARD),
      {});
    this.updateMoney(args.player_id, args.money);
  }

  private async notif_EndTurn(args: {
    truck_ids_returned: number[],
    truck_dumped_pos: { truck_id: number, dumped_pos: number[] }[],
    last_round: boolean }) {

    let anims: (() => Promise<any>)[] = [];
    args.truck_dumped_pos.forEach(t =>
      t.dumped_pos.forEach(p =>
        anims.push(() =>
          this.animationManager.slideOutAndDestroy(
            this.truckSpaceTile({truck_id: t.truck_id, truck_pos: p})!, $(IDS.OFF_BOARD), {} )
        )
      )
    );
    args.truck_ids_returned.forEach(tid =>
      anims.push(() => this.animationManager.slideAndAttach(this.truckElem(tid), $(IDS.depotSpace(tid)), {}))
    );

    await this.animationManager.playSequentially(anims).then( () => {
      if (args.last_round) {
        (this as any).addLastTurnBanner(_('This is the last turn!'));
      }
    })
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
