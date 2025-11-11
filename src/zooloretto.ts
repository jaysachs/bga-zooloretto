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
  /*
    static readonly ZTYPE : string = 'bbl_ztype';
    static readonly ZUSED : string = 'bbl_zused';
    static readonly PIECE : string = 'bbl_piece';
    static readonly TT_PROCESSED : string = 'bbl_tt_processed';
    */
}

class IDS {
  static readonly GAME = 'zoo-game'; // top-level
  static readonly DEPOT = 'zoo-depot';
  static readonly PRIMARY_PILE = 'zoo-primary-pile';
  static readonly ENDGAME_PILE = 'zoo-endgame-pile';
  static readonly DRAWN = 'zoo-drawn-tile';

  static depotSpace(truck_id: number) { return `zoo-depot-space-${truck_id}`}
  static truck(id : number) { return `truck_${id}`; }
  static truckSpace(truck_id : number, pos: number) { return `truckspace_${truck_id}_${pos}`; }
  static enclosure(player_no: number, enclosure_id: number): string { return `enclosure_${player_no}_${enclosure_id}`; }
  static enclosureSpace(player_no: number, enclosure_id: number, pos: number): string { return `enclosure_${player_no}_${enclosure_id}_${pos}`; }
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

// "cell" is a bad name. "container"? "spot"? "box"? "pen"? "cage"?
// The rules use "space".
interface TruckSpace {
  // Unclear if we need this. Can just use position.
  pos: number;

  // Unclear that we need this.
  // tile_id: number;

  // Empty string for empty. FIXME: use null?
  tile_type: string;

  // We'll need to know if it's placeable, i.e. if coin. Bool? Or just use special type checks?
  // We'll also need to know if it's animal or stall. Again, just special case certain types?
  // Or do we pass back "where can this be placed" information?
  // The latter works fine if we fully handle placement server-side.
}

interface Destination {
  barn: boolean;
  enclosure_id: number; // could use 0 for barn, or let this be 0-based
  stall: boolean; // true if going to stall
  space_id: number; // 1-based location
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
}

interface PlayState {
  can_draw: boolean;
  can_purchase: boolean;
  can_take_truck: boolean;
  can_buy: boolean;
  can_swap: boolean;
  can_move: boolean;
  trucks_available: number[];
}

/** Game class */
class ZoolorettoGame extends BaseGame<ZGamedatas> {
  private playerIdToColorIndex: Record<number, number> = {};

  private setupHandlers(): void {
    /*
      $(IDS.HAND).addEventListener('click', this.onHandClicked.bind(this));
      $(IDS.BOARD).addEventListener('click', this.onBoardClicked.bind(this));
      $(IDS.AVAILABLE_ZCARDS).addEventListener('click', this.onZcardClicked.bind(this));
      */
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
    const p = (s: string) => {
      let h = document.createElement('p');
      // FIXME: escaped?
      h.innerText = s;
      return h;
    };

    return this
      .div({ id: `zoo-playerboard-${player.player_no}`, classes: [ "zoo-playerboard", "whiteblock" ] },
        this.div({ id: `zoo-playername-${player.player_no}`, classes: "zoo-playername"},
          p(player.name)
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

  private addTruckDiv(truck: Truck): void {
    this.depot.append(
      this.div({id: IDS.depotSpace(truck.truck_id), classes: CSS.DEPOT_SPACE },
        this.div({ id: IDS.truck(truck.truck_id), classes: CSS.TRUCK },
          ... truck.contents.map((contents, i) =>
            this.div({ id: IDS.truckSpace(truck.truck_id, contents.pos) },
              contents.tile_type
                   ? this.div({classes: [ CSS.tile(contents.tile_type), CSS.TILE ]})
                   : undefined
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
  private player_no: number = 0;

  override setup(gamedatas: ZGamedatas) {
    console.log(gamedatas);
    super.setup(gamedatas);
    this.twoPlayer = Object.keys(gamedatas.players).length == 2;
    for (const player of Object.values(gamedatas.players)) {
      if (player.player_id == this.player_id) {
        this.player_no = player.player_no;
      }
    }
    for (const playerId in gamedatas.players) {
      const pd = gamedatas.players[playerId]!;
      this.playerIdToColorIndex[playerId] = colorIndexMap[pd.color]!;
    }

    this.setupGameHtml();

    console.log('setting up player boards', gamedatas.players);
    for (const player of Object.values(gamedatas.players)) {
      this.setupPlayerBoard(player);
    }

    this.setupStock();
    this.setupTrucks();
    this.setupDrawn(gamedatas.drawntile);
    // this.setupEnclosures(...);

    /*
        console.log('setting the the game board');
        this.setupGameBoard(gamedatas.board);

        console.log('setting up player hand', gamedatas.hand);
        gamedatas.hand.forEach((piece, i) => {
          const hpd = this.handPosDiv(i);
          if (piece && piece != Piece.EMPTY) {
            hpd.appendChild(this.createPieceDiv(piece, this.player_id));
          }
        });

        console.log('Setting up ziggurat cards', gamedatas.ziggurat_cards);
        this.setupZcards(gamedatas.ziggurat_cards);
*/

        console.log('setting up handlers');
        this.setupHandlers();

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
      let elem = document.createElement('span');
      elem.classList.add(CSS.TILE, CSS.tile(tile_type));
      this.drawnTiles.appendChild(elem);
    }
  }

  private setupGameHtml(): void {
    this.getGameAreaElement().appendChild(this.baseHtml());
    this.primaryPile = $(IDS.PRIMARY_PILE);
    this.endgamePile = $(IDS.ENDGAME_PILE);
    this.drawnTiles = $(IDS.DRAWN);
    this.depot = $(IDS.DEPOT);
  }

  private setupPlayerBoard(player: ZPlayer): void {
    const playerId = player.player_id;
    console.log('Setting up board for player ' + player.player_id);
    const moneyid = `playermoney-counter-${playerId}`;
    this.getPlayerPanelElement(playerId).append(
      this.div({ text: `!!! playerBoardExtension ${player.player_no} !!!` }),
      this.span({text: _("Money")}),
      this.span({id:moneyid})
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

  private clearOnclicks(): void {
    document.querySelectorAll(`#${IDS.GAME} .${CSS.TARGETABLE}`)
      .forEach((elem: HTMLElement) => { elem.onclick = null; elem.classList.remove(CSS.TARGETABLE); });
  }

  private addSelectableOnclick(elem: HTMLElement, onclick: (evt: MouseEvent) => any ) {
    elem.classList.add(CSS.TARGETABLE);
    elem.onclick = onclick;
  }

  private onUpdateActionButtons_PlaceDrawnTile(args: PlaceDrawnTileArgs): void {
    this.statusBar.removeActionButtons();
    args.available_spaces.forEach(
      (truckLoc: TruckLocation) =>
        this.addSelectableOnclick(this.truckSpaceElem(truckLoc)!, (evt) => this.onclick_PlaceDrawnTile(truckLoc)));
  }

  private markMoved(elem: HTMLElement): void {
    elem.classList.add(CSS.MOVED);
  }

  private unmarkMoved(elem: HTMLElement): void {
    elem.classList.remove(CSS.MOVED);
  }

  private onclick_PlaceDrawnTile(truckLoc: TruckLocation) {
    this.clearOnclicks();
    this.statusBar.removeActionButtons();
    let space = this.truckSpaceElem(truckLoc);
    let tile = this.drawnTileElem()!;
    this.animationManager.slideAndAttach(tile, space, {})
      .then(() => {
        this.markMoved(space);
        this.statusBar.addActionButton(_('Confirm'),
          () => { this.bgaPerformAction('actPlaceTileInTruck', truckLoc).then(() => this.unmarkMoved(space)) }, { autoclick: true });
        this.statusBar.addActionButton(_('Cancel'),
          () => {
            this.unmarkMoved(space);
            this.animationManager.slideAndAttach(tile, $(IDS.DRAWN), {})
              .then(() => this.reenterCurrentState())
            });
      });
    return true;
  }

  private onUpdateActionButtons_PlaceTruckTiles(
    args: {
      truck_id: number;
      spaces: {
        truck_pos: number;
        barn: boolean;
        enclosures: {
          enclosure_id: number;
          enclosure_pos: number;
        }[];
      }[]
    }) {
    // FIXME: is this the ideal? better to thread through getAllDatas?
    $(IDS.truck(args.truck_id)).classList.add(CSS.SELECTED);

    this.statusBar.removeActionButtons();
    if (args.spaces.length == 0) {
      this.statusBar.addActionButton(_('Confirm'), () => this.bgaPerformAction('actConfirmTilePlacement', {}));
      this.statusBar.addActionButton(_('Reset turn'), () => this.bgaPerformAction('actUndoTilePlacement', {}), { color: "secondary" });
      return;
    }
    this.statusBar.addActionButton(_('Reset turn'), () => this.bgaPerformAction('actUndoTilePlacement', {}), { color: "secondary" });
    let selems: HTMLElement[] = [];
    for (let s of args.spaces) {
      if (s.barn) {
        // FIXME: highlight barn
      }
      let selem = $(IDS.truckSpace(args.truck_id, s.truck_pos));
      selems.push(selem);
      selem.classList.add(CSS.SELECTABLE);
      selem.onclick = (evt) => this.handleEnclosureClick(selem, selems, args.truck_id, s);
    }
  }

  private handleEnclosureClick(selem: HTMLElement, selems: HTMLElement[], truck_id: number, s: {
        truck_pos: number;
        barn: boolean;
        enclosures: {
          enclosure_id: number;
          enclosure_pos: number;
        }[]}) {
    selems.forEach((e) => e.classList.remove(CSS.SELECTABLE));
    selem.classList.add(CSS.MOVED);
    for (let e of s.enclosures) {
      if (e.enclosure_id > 0) { // FIXME: why not label barn as enclosure ID 0?
        let elem = $(IDS.enclosureSpace(this.player_no, e.enclosure_id, e.enclosure_pos));
        elem.classList.add(CSS.TARGETABLE);
        elem.onclick = (evt) => {
          selem.classList.remove(CSS.MOVED);
          let tgtElem = evt.target as HTMLElement;
          tgtElem.classList.remove(CSS.TARGETABLE);
          // this.animationManager.slideAndAttach(selem.firstElementChild as HTMLElement, elem, {});
          this.bgaPerformAction('actPlaceTileInZoo', { truck_id: truck_id, truck_pos: s.truck_pos, enclosure_id: e.enclosure_id })
            ;
        };
      }
    }
  }

  private addCancelButton(onCancel: CallableFunction): void {
      this.statusBar.addActionButton(_('Cancel'),
          () => {
            this.statusBar.removeActionButtons();
            onCancel();
          });
  }

  private onUpdateActionButtons_PlayerTurn(playState: PlayState): void {
    this.statusBar.removeActionButtons();
    let again = (c?: CallableFunction) =>
      () => {
        if (c) { c(); }
        this.onUpdateActionButtons_PlayerTurn(playState);
      };
    if (playState.can_draw) {
      this.statusBar.addActionButton(_('Draw tile'), () => {
        this.statusBar.removeActionButtons();
        this.statusBar.addActionButton(_('Confirm draw'),
          () => this.bgaPerformAction('actDrawTile'),
          { autoclick: true });
        this.addCancelButton(again());
      });
    }
    if (playState.can_purchase) {
      this.statusBar.addActionButton(_('Purchase extension'), () => {
        this.statusBar.removeActionButtons();
        this.renderNewExtension();
        this.statusBar.addActionButton(_('Confirm purchase'),
          () => this.bgaPerformAction('actPurchaseExtension'),
          { autoclick: true });
        this.addCancelButton(again(() => this.renderNewExtension()));
      });
    }
    if (playState.trucks_available.length > 0) {
      this.statusBar.addActionButton(_('Take truck'), () => this.handleTakeTruck(playState));
    }
  }

  private handleTakeTruck(playState: PlayState) {
      this.statusBar.removeActionButtons();
      // update message to "select a truck" / switch client state
      this.statusBar.setTitle(_('Select a truck'));
      let cleanup = () => playState.trucks_available.forEach((tid: number) => {
        let elem = $(IDS.truck(tid));
        elem.onclick = null;
        elem.classList.remove(CSS.TARGETABLE);
      });
      this.statusBar.addActionButton(_('Cancel'), () => {
        cleanup();
        this.onUpdateActionButtons_PlayerTurn(playState);
      });
      playState.trucks_available.forEach((tid: number) => {
        let elem = $(IDS.truck(tid));
        elem.classList.add(CSS.TARGETABLE);
        elem.onclick = (evt) => {
          cleanup();
          this.bgaPerformAction('actTakeTruck', { truck_id: tid }).then(() => {
            // render truck taken
          })
        };
      });
  }

  private drawnTileElem(): HTMLElement | undefined {
      return $(IDS.DRAWN).firstElementChild as (HTMLElement | undefined);
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

  private async notif_PlaceTileInZoo(args: { player_id: number,
			truck_id: number,
			truck_pos: number,
			enclosure_id: number,
			enclosure_pos: number }) {
    return this.animationManager.slideAndAttach(this.truckSpaceTile(args)!, this.enclosureSpaceElem(args), {});
  }

  private async notif_PlaceDrawnTile(args: { player_id: number, tile_id: number, val: string, truck_id: number, truck_pos: number }) {
    if (this.player_id != args.player_id) {
      return this.animationManager.slideAndAttach(this.drawnTileElem()!, this.truckSpaceElem(args), {});
    }
  }

  private async notif_TakeTruck(args : {
    player_id: number;
    truck_id: number
  }) {
    $(IDS.truck(args.truck_id)).classList.add(CSS.SELECTED);
  }

  private async notify_ConfirmTilePlacement(args: { player_id: number }) {
  }

  private renderNewExtension(): void {

  }


  private async notif_DrawTile(
    args: {
				tile_id: number,
				tile_type: string,
        drawn_from_endgame_pile: boolean,
				primary_left: number,
				endgame_left: number,
    }
  ): Promise<void> {
    // FIXME: the 2nd and subsequent draws from from the endgamePile

    // get the tile on the right pile
    // FIXME: factor out common IDs and elemes
    let pileElem = args.drawn_from_endgame_pile ? this.endgamePile : this.primaryPile;
    let tile = pileElem.lastElementChild as HTMLElement;
    // "flip" the top tile
    tile.classList.remove(CSS.BACK);
    tile.classList.add(CSS.TILE, 'backtransition', CSS.tile(args.tile_type));
    // FIXME: need to handle disk removal
    return this.animationManager.slideAndAttach(tile, this.drawnTiles, {})
        .then(() => {
          let count = args.drawn_from_endgame_pile ? args.primary_left : args.endgame_left;
          if (count >= 5) {
            this.addStockTile(pileElem);
          }
        });
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
