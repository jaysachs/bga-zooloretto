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
  /*
    static readonly IN_NETWORK = 'bbl_in_network';
    static readonly SELECTED = 'bbl_selected';
    static readonly PLAYABLE = 'bbl_playable';
    static readonly UNPLAYABLE = 'bbl_unplayable';
    static readonly UNIMPORTANT = 'bbl_unimportant';
    */
}

interface ZGamedatas extends Gamedatas<ZPlayer> {
  primary_stocksize: number;
  endgame_stocksize: number;
  lastround: boolean;
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

  private playerHtml(playerNo: number): string {
    return ``;
  }

  private baseHtml(): string {
    const delta2p = 17.979577;
    const ratio2p = 0.82020423;

    let player_count = Object.keys(this.gamedatas.players).length;
    var othersHtml = '';
    var j = 0;
    const ratio = player_count == 2 ? ratio2p : 1.0;
    const delta = player_count == 2 ? delta2p : 0.0;
    const stallClass = player_count == 2 ? "stall2" : "stall"
    var currentPlayer: ZPlayer | null = null;
    for (let i in this.gamedatas.players) {
      let x = this.gamedatas.players[i]!;
      if (x.player_no == this.currentPlayerNo) {
        currentPlayer = x;
      } else {
        let pno = x.player_no;
        j++;
        let left = 0;
        let top = 13 + j * 35;
        let a = delta + ratio * 20;
        let b = 82;
        let boardClass = "board" + (player_count == 2 ? '2' : '') + x.purchased_extensions;
        othersHtml += `
              <div id="playercards_${pno}" class="playercards whiteblock" style="left: ${left}%; top: ${top}%;">
                <div id ="playername_${pno}" class="playernameclass"><p>${x.name}</p></div>
                <div id="board_${pno}" class="${boardClass} zoom">
`                 + this.playerHtml(x.player_no) + `
                  <div id="stall_${pno}" class="${stallClass}" style="left: ${a}%; top: ${b}%;"></div>
                </div>
              </div>`;
      }
    }
    let boardClass = "board" + (player_count == 2 ? '2' : '') + currentPlayer!.purchased_extensions;
    return `
      <div id = "container1">
        <div id = "container2">
          <div id = "wagons"></div>
          <div id = "tiles"></div>
          <div id = "stock">
            <div id="primary_pile"></div>
            <div id="endgame_pile"></div>
          </div>
        </div>

        <div class="container3" id = "container3">
          <div id="board_${this.currentPlayerNo}" class="${boardClass}">
`           + this.playerHtml(this.currentPlayerNo) + `
            <div id="stall_${this.currentPlayerNo}" class="${stallClass}" style="left: 20%; top: 82%;"></div>
          </div>
          <div id="leftpanel" class="leftpanel">
            <div id="playercards" class="playercards">
`             + othersHtml + `
            </div>
          </div>
        </div>
        <div class="playeraid" id = "playeraid"></div>
      </div>
` ;
  }

  private addStockTile(id: string, cls: string = 'back') {
    let stockdiv = $(id);
    let w = stockdiv.getBoundingClientRect().width;
    let div = document.createElement('span');
    stockdiv.appendChild(div);
    div.classList.add(cls);
    // let i = stockdiv.childNodes.length;
    // div.style = `left: ${i * 5}px; top: ${(i * 5)}px`;
  }

  private setupStock() : void {
    let addStock = (id: string, size: number) => {
      var n = size > 5 ? 5 : size;
      for (let i = 0; i < n; i++) {
        this.addStockTile(id);
      }
    };

    addStock('endgame_pile', this.gamedatas.endgame_stocksize);
    if (!this.gamedatas.lastround) {
      this.addStockTile('endgame_pile', 'disk');
    }
    addStock('primary_pile', this.gamedatas.primary_stocksize);
  }

  private currentPlayerNo: number;

  override setup(gamedatas: ZGamedatas) {
    console.log(gamedatas);
    super.setup(gamedatas);

    for (const playerId in gamedatas.players) {
      const pd = gamedatas.players[playerId]!;
      this.playerIdToColorIndex[playerId] = colorIndexMap[pd.color]!;
      if (this.player_id == pd.player_id) {
        this.currentPlayerNo = pd.player_no;
      }
    }

    this.setupGameHtml();

    console.log('setting up player boards', gamedatas.players);
    for (const playerId in gamedatas.players) {
      this.setupPlayerBoard(gamedatas.players[playerId]!);
    }

    this.setupStock();

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

  private setupGameHtml(): void {
    this.getGameAreaElement().insertAdjacentHTML('beforeend', this.baseHtml());
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

  private onUpdateActionButtons_PlayerTurn(playState: PlayState): void {
    if (playState.can_draw) {
      this.statusBar.addActionButton(_('Draw tile'), () => {
        this.statusBar.removeActionButtons();
        this.statusBar.addActionButton(_('Confirm draw'),
          () => this.bgaPerformAction('actDrawTile'),
          { autoclick: true });
        this.statusBar.addActionButton(_('Cancel'),
          () => {
            this.statusBar.removeActionButtons();
            this.onUpdateActionButtons_PlayerTurn(playState);
          });
      });
    }
  }


  private async notif_DrawTile(
    args: {
				tile_id: number,
				tile_type: string,
				primary_left: number,
				endgame_left: number,
    }
  ): Promise<void> {
    // (1) figure out whether it's primary or endgame
    // (2) "flip" top tile of pile
    // (3) slide that tile to to "drawn_tile" area
    // (4) replenish up to 5, if there are some.
    // (5) if "known", update counts for stock piles
    //
    // We may have to "fix sizes" for the slide to work right? Unclear.
    //
    // Need to handle empty piles -- leave outline?
    //
    // Seems like we want more specific info here in the notif
    //  * which pile
    //  * are there at least 5 in that pile
    //  * if option enabled, the exact number in each pile
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
