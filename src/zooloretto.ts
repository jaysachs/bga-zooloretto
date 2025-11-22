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
  static moneyCounter(player_id: number): string { return `playermoney-counter-${player_id}` };
  static boardId(player_id: number): string { return `zoo-board-${player_id}`; }
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

class PurchaseExtensionFlow extends PlayFlow<undefined, ZGamedatas, ZoolorettoGame> {
  constructor(g: ZoolorettoGame) { super(g); }

  override doStart() {
    this.initStatusBar(_('Purchase extension?'));
    let current = this.game.getCurrentExtensions(this.player_id);
    this.game.renderExtensions(this.player_id, current + 1);
    this.addConfirmActionButton('actPurchaseExtension', {}, true);
    this.addCancelButton(() => { this.game.renderExtensions(this.player_id, current); });
  }
};

class DrawTileFlow extends PlayFlow<undefined> {
  constructor(g: ZoolorettoGame) { super(g); }

  override doStart() {
    this.initStatusBar(_('Draw a tile? (cannot undo)'));
    this.addConfirmActionButton('actDrawTile');
    this.addCancelButton();
  }
};

class PlaceDrawnTile extends PlayFlow<TruckLocation[], ZGamedatas, ZoolorettoGame> {
  constructor(g: ZoolorettoGame) { super(g); }

  override doStart(available_spaces: TruckLocation[]) {
    this.initStatusBar(_('Place the drawn tile'));
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
    this.addCancelButton();
  }
};

class TakeTruckFlow extends PlayFlow<AvailableTruck[], ZGamedatas, ZoolorettoGame> {
  private placedTiles : PlacedTile[] = [];
  private truck_id: number;
  constructor(g : ZoolorettoGame) { super(g); }

  override doStart(availableTrucks: AvailableTruck[]) {
    this.initStatusBar(_('Select a truck'));
    this.addCancelButton(/* () => this.cleanup() */);
    this.placedTiles = [];
    this.truck_id = 0;
    availableTrucks.forEach((truck: AvailableTruck) => {
      this.addSelectableOnclick($(IDS.truck(truck.truck_id)), (evt) => this.chooseTruckTileToPlace(truck.truck_id, truck.playable, availableTrucks));
    });
  }

  private chooseTruckTileToPlace(truck_id: number, pps: PossiblePlacement[], availabeTrucks: AvailableTruck[]) {
    this.truck_id = truck_id;
    if (pps.length == 0) {
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
    this.addCancelButton();
  }

  private chooseDestination(truck_id: number, pp: PossiblePlacement, availabeTrucks: AvailableTruck[]) {
    this.initStatusBar(_('Choose a destination for the selected tile'));
    this.addCancelButton();
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

class DiscardTileFlow extends PlayFlow<number[], ZGamedatas, ZoolorettoGame> {
  constructor(g : ZoolorettoGame) { super(g); }

  override doStart(discardables: number[]) {
    this.initStatusBar(_('Select a truck'));
    this.addCancelButton();

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
    this.addCancelButton();
  }
}

class MoveTileFlow extends PlayFlow<PossibleMove[], ZGamedatas, ZoolorettoGame> {
  constructor(g : ZoolorettoGame) { super(g); }

  override doStart(possibleMoves: PossibleMove[]) {
    this.initStatusBar(_('Select a tile to move'));
    this.addCancelButton();
    possibleMoves.forEach((m: PossibleMove) => {
      this.addSelectableOnclick(
        Elements.enclosureSpace({player_id: this.player_id, enclosure_id: m.src.enclosure_id, enclosure_pos: m.src.pos}),
        (evt) => this.chooseDest(m)
      )
    });
  }

  private chooseDest(pm: PossibleMove) {
    this.initStatusBar(_('Select a destination space'));
    this.addCancelButton();
    pm.dests.forEach((dest: Space) => {
      let elem = Elements.enclosureSpaceTile({player_id: this.player_id, enclosure_id: pm.src.enclosure_id, enclosure_pos: pm.src.pos});
      let destElem = Elements.enclosureSpace({player_id: this.player_id, enclosure_id: dest.enclosure_id, enclosure_pos: dest.pos})
      this.addSelectableOnclick(destElem,
        (evt) => this.slide(elem, destElem)
          .then(() => this.confirmMove(pm.src, dest))
      )
    });
  }

  private confirmMove(src: Space, dest: Space) {
    this.initStatusBar(_('Confirm move'));
    this.addConfirmActionButton('actMoveTile', {
      src_id: src.enclosure_id, src_pos: src.pos, dest_id: dest.enclosure_id, dest_pos: dest.pos
    });
    this.addCancelButton();
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
          Html.span({id: IDS.moneyCounter(playerId), text: `${player.money}`})),
        Html.div({ classes: CSS.DEPOT_SPACE, id: IDS.takenTruck(playerId)}),
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

  private setupHtml(twoPlayer: boolean): void {
    let zhtml = new ZoolorettoHtml(this.gamedatas, this.player_id);
    this.getGameAreaElement().appendChild(zhtml.baseStructure());
    for (const player of Object.values(this.gamedatas.players)) {
      this.getPlayerPanelElement(player.player_id).appendChild(zhtml.playerPanel(player));
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
    $(IDS.moneyCounter(player_id)).innerText = `${money}`;
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
    if (playState.can_purchase) {
      this.statusBar.addActionButton(_('Purchase extension'), () => new PurchaseExtensionFlow(this).start());
    }
    if (playState.available_trucks.length > 0) {
      this.statusBar.addActionButton(_('Take truck'), () => new TakeTruckFlow(this).start(playState.available_trucks));
    }
    if (playState.discardables.length > 0) {
      this.statusBar.addActionButton(_('Discard a tile'), () => new DiscardTileFlow(this).start(playState.discardables));
    }
    if (playState.possible_moves.length > 0) {
      this.statusBar.addActionButton(_('Move a tile'), () => new MoveTileFlow(this).start(playState.possible_moves));
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

  private async notif_PurchaseExtension(args: {
      player_id: number,
      purchased_extensions: number,
      money: number
    }) {
    if (this.player_id != args.player_id) {
      this.renderExtensions(args.purchased_extensions, args.player_id);
    }
    this.updateMoney(args.player_id, args.money);
  }

  private async notif_TakeTruckAndPlaceTiles(args: {
    player_id: number,
    truck_id: number,
    placements: Placement[]
  }) {
    let anims : AnimationList = [];
    args.placements.forEach( (p) => {
      let pl = p.placement;
      if (pl == 'coin') {
        anims.push(() => this.animationManager.slideOutAndDestroy(
          Elements.truckSpaceTile({truck_id: args.truck_id, truck_pos: p.truck_pos})!,
          this.getPlayerPanelElement(args.player_id),
          {}
        ));
      } else if (args.player_id != this.player_id) {
        anims.push(() => this.animationManager.slideAndAttach(
          Elements.truckSpaceTile({truck_id: args.truck_id, truck_pos: p.truck_pos})!,
          Elements.enclosureSpace({player_id: args.player_id, enclosure_id: pl.enclosure_id, enclosure_pos: pl.enclosure_pos }),
          {})
        );
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
