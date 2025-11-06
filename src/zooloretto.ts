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
  static readonly TRUCKS = 'trucks';
  static readonly PRIMARY_PILE = 'primary_pile';
  static readonly ENDGAME_PILE = 'endgame_pile';
  static readonly DRAWN = 'drawn';

  static truck(id : number) { return `truck_${id}`; }
  static truckSpace(truck_id : number, pos: number) { return `truckspace_${truck_id}_${pos}`; }
  /*
    static readonly AVAILABLE_ZCARDS: string = 'bbl_available_zcards';
    static readonly BOARD = 'bbl_board';
    static readonly HAND = 'bbl_hand';

    static handcount(playerId: number): string {
      return `bbl_handcount_${playerId}`;
    }

    static poolcount(playerId: number): string {
      return `bbl_poolcount_${playerId}`;
    }

    static citycount(playerId: number): string {
      return `bbl_citycount_${playerId}`;
    }

    static hexDiv(rc: RowCol): string {
      return `bbl_hex_${rc.row}_${rc.col}`;
    }

    static playerBoardZcards(playerId: number): string {
      return `bbl_zcards_${playerId}`;
    }

    static zcard(type: string): string {
      return `bbl_${type}`;
    }
    */
}

class CSS {
  static readonly BACK = 'back';
  static readonly TILE = 'tile';
  static readonly TRUCK = 'truck';
  static readonly TARGETABLE = 'targetable';
  static readonly SELECTABLE = 'selectable';
  static readonly MOVED = 'moved';
  static tile(tile_type: string) : string {
    return `tile${tile_type}`;
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

  /*
    canEndTurn: boolean;
    canUndo: boolean;
    allowedMoves: RowCol[];
    */
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

  private playerBoardExt(player_no: number): string {
    /*
      return `
        <div>
          <span class='bbl_pb_hand_label_${color_index}'></span>
          <span id='${IDS.handcount(player_id)}'>5</span>
        </div>
        <div>
          <span class='bbl_pb_pool_label_${color_index}'></span>
          <span id='${IDS.poolcount(player_id)}'>19</span>
        </div>
        <div>
          <span class='bbl_pb_citycount_label'></span>
          <span id='${IDS.citycount(player_id)}'>1</span>
        </div>
        <div id='${IDS.playerBoardZcards(player_id)}' class='bbl_pb_zcards'>
          <span class='bbl_pb_zcard_label'></span>
        </div>
  `;
  */
    return `<div>!!! playerBoardExtension ${player_no} !!!</div>`;
  }

  private playerHtml(player?: ZPlayer ): string {
    if (!player) { return ''; }
    const barnClass = this.twoPlayer ? "barn2p" : "barn"
    const boardClass = this.twoPlayer ? "board2p" : "board";
    const zoomClass = player.player_id != this.player_id ? "zoom" : "";
    return `
                    <div id="board_${player.player_no}" class="${boardClass} ${zoomClass}" extensions="${player.purchased_extensions}">
                      <div id="barn_${player.player_no}" class="${barnClass}"></div>
                    </div>
`;
  }

  private otherPlayerHtml(player: ZPlayer) : string {
    return `
              <div id="playercards_${player.player_no}" class="playercards whiteblock">
                <div id ="playername_${player.player_no}" class="playernameclass"><p>${player.name}</p></div>
`               + this.playerHtml(player) + `
              </div>
  `;
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
      <div id = "container1">
        <div id = "container2">
          <div id="${IDS.TRUCKS}"></div>
          <div id="${IDS.DRAWN}"></div>
          <div id="stock">
            <div id="${IDS.PRIMARY_PILE}"></div>
            <div id="${IDS.ENDGAME_PILE}"></div>
          </div>
        </div>
        <div class="container3" id = "container3">
`         + this.playerHtml(currentPlayer) + `
          <div id="leftpanel" class="leftpanel">
            <div id="playercards" class="playercards">
`             + players.map((p) => this.otherPlayerHtml(p)) + `
            </div>
          </div>
        </div>
        <div id="playeraid"></div>
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
      if (contents.tile_type != null) {
        let typeDiv = document.createElement('div');
        typeDiv.classList.add(`tile${contents.tile_type}`, CSS.TILE);
        spaceDiv.appendChild(typeDiv);
      }
    }
  }

  private addStockTile(pileElem: HTMLElement, cls: string = 'back') {
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
      this.addStockTile(this.endgamePile, 'disk');
    }
    addStock(this.primaryPile, this.gamedatas.primary_stocksize);
  }

  private twoPlayer: boolean;

  override setup(gamedatas: ZGamedatas) {
    console.log(gamedatas);
    super.setup(gamedatas);
    this.twoPlayer = Object.keys(gamedatas.players).length == 2;
    for (const playerId in gamedatas.players) {
      const pd = gamedatas.players[playerId]!;
      this.playerIdToColorIndex[playerId] = colorIndexMap[pd.color]!;
    }

    this.setupGameHtml();

    console.log('setting up player boards', gamedatas.players);
    for (const playerId in gamedatas.players) {
      this.setupPlayerBoard(gamedatas.players[playerId]!);
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
    console.log('Setting up board for player ' + playerId);
    this.getPlayerPanelElement(playerId)
      .insertAdjacentHTML('beforeend', this.playerBoardExt(player.player_no));

    /*
  //    create counters per player
  this.handCounters[playerId] = new ebg.counter();
  this.handCounters[playerId]!.create(IDS.handcount(playerId));
  this.poolCounters[playerId] = new ebg.counter();
  this.poolCounters[playerId]!.create(IDS.poolcount(playerId));
  this.cityCounters[playerId] = new ebg.counter();
  this.cityCounters[playerId]!.create(IDS.citycount(playerId));
  this.updateHandCount(player, false);
  this.updatePoolCount(player, false);
  this.updateCapturedCityCount(player, false);
  this.scoreCtrl[playerId]!.setValue(player.score);
  */
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
              () => { this.bgaPerformAction('actPlaceTile', s).then(() => dest.classList.remove(CSS.MOVED)) }, { autoclick: false });
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
    let pileElem = args.drawn_from_endgame_pile ? this.primaryPile : this.endgamePile;
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
