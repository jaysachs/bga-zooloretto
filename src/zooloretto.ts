type AnimationList = (() => Promise<any>)[];

interface ZPlayer extends Player {
  player_id: number;
  player_no: number;
  money: number;
  purchased_extensions: number;

  /*
    hand_size: number;
    pool_size: number;
    captured_city_count: number;
    score: number;
    */
}

class Attrs {
  /*
    static readonly ZTYPE : string = 'bbl_ztype';
    static readonly ZUSED : string = 'bbl_zused';
    static readonly PIECE : string = 'bbl_piece';
    static readonly TT_PROCESSED : string = 'bbl_tt_processed';
    */
}

class IDS {
  static readonly TRUCKS = 'zoo-trucks';
  static readonly PRIMARY_PILE = 'zoo-primary-pile';
  static readonly ENDGAME_PILE = 'vl-endgame-pile';
  static readonly DRAWN = 'zoo-drawn-tile';

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
  static readonly MOVED = 'moved';
  static tile(tile_type: string) : string {
    return `zoo-tile-${tile_type}`;
  }
  /*
    static readonly IN_NETWORK = 'bbl_in_network';
    static readonly SELECTED = 'bbl_selected';
    static readonly PLAYABLE = 'bbl_playable';
    static readonly UNPLAYABLE = 'bbl_unplayable';
    static readonly UNIMPORTANT = 'bbl_unimportant';
    */
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

interface ArrangeState {
  truck_id: number;
  spaces: {
    pos: number;
    barn: boolean;
    enclosures: {
      enclosure_id: number;
      position: number;
    }[];
  }[];
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
        this.addTooltip(ele.id, this.zcardTooltips[ele.getAttribute(Attrs.ZTYPE)!], '');
      });
      */
  }

  private cellId(player_no: number, enclosure_id : number, cell : number) {
    return ;
  }

  private playerHtml(player?: ZPlayer ): string {
    if (!player) { return ''; }
    const barnClass = this.twoPlayer ? "zoo-barn-2p" : "zoo-barn"
    const boardClass = this.twoPlayer ? "zoo-board-2p" : "zoo-board";
    const zoomClass = player.player_id != this.player_id ? "zoo-zoom" : "";
    const cellClass = "cell";
    const enclosureClass = "enclosure";
    const pno = player.player_no;
    let enclosure = (e:number, n: number): string => {
      let html = `
                    <div id="${IDS.enclosure(pno, e)}" enclosure="${e}">`;
      for (let i = 0; i < n; ++i) {
        html += `
                      <div id="${IDS.enclosureSpace(pno, e, i+1)}" class="${cellClass}"></div>`;
      }
      html += `
                    </div>`;
      return html;
    };
    const board_id = player.player_id == this.player_id ? "zoo-main-board" : "";
    return `
                    <div id="${board_id}" class="${boardClass} ${zoomClass}" extensions="${player.purchased_extensions}">
                      <div id="barn_${pno}" class="${barnClass}"></div>`
                      + enclosure(1, 6)
                      + enclosure(2, 6)
                      + enclosure(3, 7)
                      + enclosure(4, 6)
                      + (this.twoPlayer ? enclosure(5, 6) : '') + `
                    </div>`;
  }

  private otherPlayerHtml(player: ZPlayer) : string {
    return `
              <div id="playercards_${player.player_no}" class="playercards whiteblock">
                <div id ="playername_${player.player_no}" class="playernameclass"><p>${player.name}</p></div>`
                + this.playerHtml(player) + `
              </div>`;
  }

  private baseHtml(): string {
    /*
    const delta2p = 17.979577;
    const ratio2p = 0.82020423;
    const ratio = twoPlayer ? ratio2p : 1.0;
    const delta = twoPlayer ? delta2p : 0.0;
*/
    let currentPlayer = this.gamedatas.players[this.player_id];
    let players = Object.values(this.gamedatas.players).filter((p) => p != currentPlayer);
    return `
      <div id = "zoo-game-container">
        <div id = "zoo-upper-container">
          <div id="${IDS.TRUCKS}"></div>
          <div id="zoo-drawn"><div id="${IDS.DRAWN}"></div></div>
          <div id="zoo-stock">
            <div id="${IDS.PRIMARY_PILE}"></div>
            <div id="${IDS.ENDGAME_PILE}"></div>
          </div>
        </div>
        <div id="zoo-boards">`
          + this.playerHtml(currentPlayer) + `
          <div id="leftpanel" class="leftpanel">
            <div id="playercards" class="playercards">`
            + players.map((p) => this.otherPlayerHtml(p)) + `
            </div>
          </div>
        </div>
        <div id="playeraid" class="playeraidEN"></div>
      </div>
` ;
  }

  private setupTrucks(): void {
    this.gamedatas.trucks.forEach((truck) => this.addTruckDiv(truck));
  }

  private addTruckDiv(truck: Truck): void {
    let div = document.createElement('div');
    div.id = IDS.truck(truck.truck_id);
    div.classList.add(CSS.TRUCK);
    this.trucks.appendChild(div);

    for (let i in truck.contents) {
      let contents = truck.contents[i]!;
      let spaceDiv = document.createElement('div');
      spaceDiv.id = IDS.truckSpace(truck.truck_id, contents.pos);
      div.appendChild(spaceDiv);
      if (contents.tile_type != '') {
        let typeDiv = document.createElement('div');
        typeDiv.classList.add(CSS.tile(contents.tile_type), CSS.TILE);
        spaceDiv.appendChild(typeDiv);
      }
    }
  }

  private addStockTile(pileElem: HTMLElement, cls: string = CSS.BACK) {
    let div = document.createElement('span');
    pileElem.appendChild(div);
    div.classList.add(cls);
    // let w = pileElem.getBoundingClientRect().width;
    // let i = stockdiv.childNodes.length;
    // div.style = `left: ${i * 5}px; top: ${(i * 5)}px`;
  }

  private endgamePile : HTMLElement;
  private primaryPile : HTMLElement;
  private drawnTiles : HTMLElement;
  private trucks : HTMLElement;

  private setupStock() : void {
    let addStock = (pileElem : HTMLElement, size: number) => {
      var n = size > 5 ? 5 : size;
      for (let i = 0; i < n; i++) {
        this.addStockTile(pileElem);
      }
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
    this.getGameAreaElement().insertAdjacentHTML('beforeend', this.baseHtml());
    this.primaryPile = $(IDS.PRIMARY_PILE);
    this.endgamePile = $(IDS.ENDGAME_PILE);
    this.drawnTiles = $(IDS.DRAWN);
    this.trucks = $(IDS.TRUCKS);
  }

  private setupPlayerBoard(player: ZPlayer): void {
    const playerId = player.player_id;
    console.log('Setting up board for player ' + player.player_id);
    const moneyid = `playermoney-counter-${playerId}`;
    this.getPlayerPanelElement(playerId).insertAdjacentHTML('beforeend',`
                    <div>!!! playerBoardExtension ${player.player_no} !!!</div>
                    <span>${_("Money")}: </span><span id="${moneyid}"></span>
 `);
    const counter = new ebg.counter();
    counter.create(
        moneyid,
        { value: (player as any).playermoney, playerCounter: 'playermoney', playerId: playerId }
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
  private onUpdateActionButtons_PlaceTile(args: { available_spaces: { player_id: number, truck_id: number; pos: number }[] }): void {
    // FIXME:
    //   (a) slid tile "shrinks" and grows only at end
    this.statusBar.removeActionButtons();
    args.available_spaces.forEach((s) => {
      let space = $(IDS.truckSpace(s.truck_id, s.pos));
      space.classList.add(CSS.TARGETABLE);
      space.onclick = (evt) => {
        space.classList.remove(CSS.TARGETABLE);
        let tile = $(IDS.DRAWN).firstElementChild as HTMLElement;
        let dest = $(IDS.truckSpace(s.truck_id, s.pos));
        this.animationManager.slideAndAttach(tile, dest, {})
          .then(() => {
            dest.classList.add(CSS.MOVED);
            this.statusBar.removeActionButtons();
            // mark as "targetable" and add onclick handlers
            args.available_spaces.forEach((s) => $(IDS.truckSpace(s.truck_id, s.pos)).onclick = null);
            this.statusBar.addActionButton(_('Confirm'),
              () => { this.bgaPerformAction('actPlaceTileInTruck', s).then(() => dest.classList.remove(CSS.MOVED)) }, { autoclick: true });
              // FIXME: can we automatically capture this "cancelable"/"undoable" animation?
              //   eg.    this.slideThing(from, to).then((undo) => ....  undo() ... )
              //     where slideThing returns the undo as part of the returned promise?
              //    also the notion of cancel has the "restart" sense
            this.statusBar.addActionButton(_('Cancel'),
              () => { dest.classList.remove(CSS.MOVED); this.animationManager.slideAndAttach(tile, $(IDS.DRAWN), {}).then(() => { this.onUpdateActionButtons_PlaceTile(args); }); });
          });
        return true;
      };
    });
  }

  private async notif_PlaceTile(args: { player_id: number, tile_id: number, val: string, truck_id: number, pos: number }) {
    if (this.player_id != args.player_id) {
      let tile = $(IDS.DRAWN).firstElementChild as HTMLElement;
      let dest = $(IDS.truckSpace(args.truck_id, args.pos));
      return this.animationManager.slideAndAttach(tile, dest, {});
    }
  }

  private onUpdateActionButtons_ArrangeZoo(arrangeState: ArrangeState) {
    let telem = $(IDS.truck(arrangeState.truck_id));
    let soc = (evt) => {

    }
    let selems: HTMLElement[] = [];
    for (let s of arrangeState.spaces) {
      if (s.barn) {
        // FIXME: highlight barn
      }
      let selem = $(IDS.truckSpace(arrangeState.truck_id, s.pos));
      selems.push(selem);
      selem.classList.add(CSS.SELECTABLE);
      selem.onclick = (evt) => {
        selems.forEach((e) => e.classList.remove(CSS.SELECTABLE));
        selem.classList.add(CSS.MOVED);
        for (let e of s.enclosures) {
          if (e.enclosure_id > 0) {
            let elem = $(IDS.enclosureSpace(this.player_no, e.enclosure_id, e.position));
            elem.classList.add(CSS.TARGETABLE);
            elem.onclick = (evt) => {
              selem.classList.remove(CSS.MOVED);
              elem.classList.remove(CSS.TARGETABLE);
              // this.animationManager.slideAndAttach(selem.firstElementChild as HTMLElement, elem, {});
              this.bgaPerformAction('actPlaceTileInZoo', { truck_id: arrangeState.truck_id, truck_pos: s.pos, enclosure_id: e.enclosure_id })
                .then(() => this.animationManager.slideAndAttach(selem.firstElementChild as HTMLElement, elem, {}));
            };
          }
        }
      };
    }
  }

  private notif_PlaceTileInTruck(args: any) {
    console.log("notif_PlaceTileInTruck", args);
  }

  private notif_PlaceTileInZoo(args: any) {
    console.log("notif_PlaceTileInZoo", args);
  }

  private addCancelButton(onCancel: CallableFunction): void {
      this.statusBar.addActionButton(_('Cancel'),
          () => {
            this.statusBar.removeActionButtons();
            onCancel();
          });
  }

  private onUpdateActionButtons_PlayerTurn(playState: PlayState): void {
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
