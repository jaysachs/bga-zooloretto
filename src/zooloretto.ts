type AnimationList = (() => Promise<any>)[];

interface ZPlayer extends Player {
  player_id: number;
  player_no: number;
  money: number;
  purchased_extensions: number;
}

interface TruckLocation{ truck_id: number, truck_pos: number };
interface PlaceDrawnTileArgs { available_spaces: TruckLocation[] };

class Attrs {
  // FIXME: rename value to have zoo- prefix.
  static readonly ENCLOSURE : string = 'zoo-enclosure';
  static readonly EXTENSIONS : string = 'zoo-extensions';
  static readonly TILE : string = 'zoo-tile';
}

class IDS {
  static readonly GAME = 'zoo-game'; // top-level element
  static readonly DEPOT = 'zoo-depot';
  static readonly PRIMARY_PILE = 'zoo-primary-pile';
  static readonly ENDGAME_PILE = 'zoo-endgame-pile';
  static readonly DRAWN = 'zoo-drawn-tile';

  static depotSpace(truck_id: number) { return `zoo-depot-space-${truck_id}`}
  static truck(id : number) { return `truck-${id}`; }
  static truckSpace(truck_id : number, pos: number) { return `truckspace-${truck_id}-${pos}`; }
  static enclosure(player_no: number, enclosure_id: number): string { return `enclosure-${player_no}-${enclosure_id}`; }
  static enclosureSpace(player_no: number, enclosure_id: number, pos: number): string { return `enclosure-${player_no}-${enclosure_id}-${pos}`; }
}

class CSS {
  static readonly BACK = 'zoo-tile-back';
  static readonly TILE = 'zoo-tile';
  static readonly TRUCK = 'zoo-truck';
  static readonly TARGETABLE = 'zoo-targetable';
  static readonly SELECTABLE = 'zoo-selectable';
  static readonly SELECTED = 'zoo-selected';
  static readonly MOVED = 'moved';
  static readonly DEPOT_SPACE = 'zoo-depot-space';
  static tile(tile_type: string) : string {
    return `zoo-tile-${tile_type}`;
  }
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
  truck_id: number;
  // Should always be 3. null means empty.
  // FIXME: Need to be careful about 0- and 1- based; probably best to be consistent
  //   and use "null" for "nothing" and 0-based.
  contents: TruckSpace[];
}

interface ZGamedatas extends Gamedatas<ZPlayer> {
  primary_stocksize: number;
  endgame_stocksize: number;
  lastround: boolean;
  drawntile: string;
  // Should always be 3.
  trucks: Truck[];
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

interface PlayState {
  can_draw: boolean;
  can_purchase: boolean;
  can_take_truck: boolean;
  can_buy: boolean;
  can_swap: boolean;
  can_move: boolean;
  available_trucks: AvailableTruck[];
}

/** Game class */
class ZoolorettoGame extends BaseGame<ZGamedatas> {
  private playerIdToColorIndex: Record<number, number> = {};

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

    const board_id = player.player_id == this.player_id ? "zoo-main-board" : "";
    const zoomClass = player.player_id != this.player_id ? "zoo-zoom" : "";
    let elem = this
      .div({ id: board_id, classes: [ 'zoo-board', zoomClass ]},
        this.div({ id: `zoo-barn-${pno}`, classes: 'zoo-barn' }),
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
          this.div({ id: 'zoo-drawn' },
            this.div({ id: IDS.DRAWN })
          ),
          this.div({ id: 'zoo-stock' },
            this.div({ id: IDS.PRIMARY_PILE }),
            this.div({ id: IDS.ENDGAME_PILE })
          )
        ),
        this.div({id: 'zoo-boards' },
          this.playerDiv(currentPlayer),
          this.div({id: 'zoo-other-playerboards'},
            ... otherplayers.map((p) => this.otherPlayerDiv(p)
          )
        ),
        this.div({id: 'playeraid' })
        )
      );
  }

  private setupTrucks(): void {
    this.gamedatas.trucks.forEach((truck) => this.addTruckDiv(truck));
  }

  private makeTileSpan(tile_type?: string): HTMLElement | undefined {
    return tile_type ? this.span({ classes: CSS.tile(tile_type), attrs: { 'zoo-tile': tile_type } }) : undefined;
  }

  private addTruckDiv(truck: Truck): void {
    this.depot.append(
      this.div({id: IDS.depotSpace(truck.truck_id), classes: CSS.DEPOT_SPACE },
        this.div({ id: IDS.truck(truck.truck_id), classes: CSS.TRUCK },
          ... truck.contents.map((contents, i) =>
            this.div({ id: IDS.truckSpace(truck.truck_id, contents.pos) },
              this.makeTileSpan(contents.tile_type)
            )
          )
        )
      )
    );
  }

  private addStockTile(pileElem: HTMLElement, cls: string = CSS.BACK) {
    pileElem.appendChild(this.span({classes: cls}));
  }

  private endgamePile : HTMLElement;
  private primaryPile : HTMLElement;
  private drawnTiles : HTMLElement;
  private depot : HTMLElement;

  private setupStock() : void {
    let addStock = (pileElem : HTMLElement, size: number) => {
      let n = size > 5 ? 5 : size;
      this.range(1, n-1).forEach(() => this.addStockTile(pileElem));
    };

    addStock(this.endgamePile, this.gamedatas.endgame_stocksize);
    if (!this.gamedatas.lastround) {
      this.addStockTile(this.endgamePile, 'zoo-disk');
    }
    addStock(this.primaryPile, this.gamedatas.primary_stocksize);
  }

  private twoPlayer: boolean;

  override setup(gamedatas: ZGamedatas) {
    super.setup(gamedatas);
    this.twoPlayer = Object.keys(gamedatas.players).length == 2;
    for (const playerId in gamedatas.players) {
      const pd = gamedatas.players[playerId]!;
      this.playerIdToColorIndex[playerId] = colorIndexMap[pd.color]!;
    }

    this.setupGameHtml();

    console.log('setting up player boards', gamedatas.players);
    for (const player of Object.values(gamedatas.players)) {
      this.setupPlayerPanel(player);
    }

    this.setupStock();
    this.setupTrucks();
    this.setupDrawn(gamedatas.drawntile);
    // this.setupEnclosures(...);

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

  private setupDrawn(tile_type? : string) {
    if (tile_type) {
      this.drawnTiles.appendChild(this.makeTileSpan(tile_type)!);
    }
  }

  private setupGameHtml(): void {
    this.getGameAreaElement().appendChild(this.baseHtml());
    this.primaryPile = $(IDS.PRIMARY_PILE);
    this.endgamePile = $(IDS.ENDGAME_PILE);
    this.drawnTiles = $(IDS.DRAWN);
    this.depot = $(IDS.DEPOT);
  }

  private setupPlayerPanel(player: ZPlayer): void {
    const playerId = player.player_id;
    console.log('Setting up panel for player ' + player.player_id);
    const moneyid = `playermoney-counter-${playerId}`;
    this.getPlayerPanelElement(playerId).append(
      this.div({ id: `zoo-taken-truck-${playerId}`}),
      this.div({},
        this.span({text: _("Money")}),
        this.span({id:moneyid}))
    );
    const counter = new ebg.counter();
    counter.create(
        moneyid,
        { value: player.money, playerCounter: 'playermoney', playerId: playerId }
    );
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

  private drawnTileElem(): HTMLElement | undefined {
      return $(IDS.DRAWN).firstElementChild as (HTMLElement | undefined);
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

  private addSelectableOnclick(elem: HTMLElement, onclick: (evt: MouseEvent) => any ) {
    elem.classList.add(CSS.TARGETABLE);
    elem.addEventListener(
      "click",
      (ev: MouseEvent) => { this.clearOnclicks(); onclick(ev); },
      { signal: this.onClickAbortController.signal });
  }

  // FIXME: separate "cancel" from "restart turn"
  private addCancelButton(onCancel?: CallableFunction): void {
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
    // get the tile on the right pile
    // FIXME: factor out common IDs and elemes
    let pileElem = args.drawn_from_endgame_pile ? this.endgamePile : this.primaryPile;
    // FIXME: need to handle disk removal
    pileElem.lastElementChild?.remove();
    let tile = this.makeTileSpan(args.tile_type)!;
    pileElem.appendChild(tile);
    // FIXME: "flip" the top tile?
    return this.animationManager.slideAndAttach(tile, this.drawnTiles, {})
        .then(() => {
          // FIXME: should not pass all this info forward. Just which pile needs refilling, if either.
          let count = args.drawn_from_endgame_pile ? args.primary_left : args.endgame_left;
          if (count >= 5) {
            this.addStockTile(pileElem);
          }
        });
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
    let tile = this.drawnTileElem()!;
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
        this.bgaPerformAction('actPlaceTileInTruck', this.truckLocFromId(space.id))
      },
      { autoclick: true }
    );
    this.statusBar.addActionButton(
      _('Cancel'),
      () => {
        this.statusBar.removeActionButtons();
        this.unmarkMoved(space);
        this.restoreServerGameState();
        this.animationManager.slideAndAttach(tile, $(IDS.DRAWN), {})
      },
      { color: "secondary"}
    );
  }

  private async notif_PlaceDrawnTile(args: { player_id: number, tile_id: number, val: string, truck_id: number, truck_pos: number }) {
    if (this.player_id != args.player_id) {
      return this.animationManager.slideAndAttach(this.drawnTileElem()!, this.truckSpaceElem(args), {});
    }
  }

  //
  // Purchase an extension
  //

  private renderNewExtension(): void {

  }

  private client_PurchaseExtension(): void {
    this.statusBar.removeActionButtons();
    this.renderNewExtension();
    this.statusBar.addActionButton(_('Confirm purchase'), () => this.bgaPerformAction('actPurchaseExtension'), { autoclick: true });
    this.addCancelButton(() => { this.renderNewExtension(); });
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
            this.game.bgaPerformAction('actPlaceTruckTiles', {
              truck_id: truck_id,
              placed_tiles: JSON.stringify(this.placedTiles),
              // FIXME: slide truck to player board / panel
            }).then(() => this.game.animationManager.slideAndAttach(this.game.truckElem(truck_id), $(`zoo-taken-truck-${this.game.player_id}`), {}))
            .then(() => this.cleanup())
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

  private async notif_PlaceTileInZoo(args: { player_id: number,
			truck_id: number,
			truck_pos: number,
			enclosure_id: number,
			enclosure_pos: number }) {
  }

  private async notif_PlaceTruckTiles(args: { player_id: number, truck_id: number, placements: {
    truck_pos: number;
    enclosure_id: number;
    enclosure_pos: number;
  }[] }) {
    console.log(args);
    this.animationManager.playParallel(
      args.placements.map((p) => () => this.animationManager.slideAndAttach(
        this.truckSpaceTile({truck_id: args.truck_id, truck_pos: p.truck_pos})!,
        this.enclosureSpaceElem({player_id: args.player_id, enclosure_id: p.enclosure_id, enclosure_pos: p.enclosure_pos }),
        {}),
        args.placements))
        .then(() => this.animationManager.slideAndAttach(this.truckElem(args.truck_id), $(`zoo-taken-truck-${this.player_id}`), {}))

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
