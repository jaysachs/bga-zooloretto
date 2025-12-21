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

  // name is translated/able
  tile_translations: { type: string, name: string }[];
}

//
// interfaces for args & notifs
//

// general use

interface Space {
  enclosure_id: number;
  pos: number;
}

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
  encs: PossibleEnclosurePlacement[];
}

interface AvailableTruck {
  truck_id: number;
  coin_positions: number[];
  money_delta: Moneys;
  playable: PossiblePlacement[];
}

interface PossibleMove {
  src_player_id: number;
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
  possible_discards: PlacedTile[];
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
  constructor(g: ZoolorettoGame, undoStack: UndoStack) { super(g, undoStack); }

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
          this.addSelectableOnclick(
            Elements.enclosureSpace(this.player_id, p),
            () => this.callUndoably("selectExchangeDest", async () => this.selectDestinationForExchange(pes)));
        }
      })
    });
    this.addRestartAndUndoButtons();
  }

  private selectDestinationForExchange(pes: PossibleExchange[]) {
    this.initStatusBar(_("Select the destination enclosure for the exchange"));
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
            this.playParallel(anims)
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

class PurchaseTileFlow extends ZooFlow<PossibleMove[]> {
  constructor(g: ZoolorettoGame, undoStack: UndoStack) { super(g, undoStack); }

  protected override doStart(possible_purchases: PossibleMove[]) {
    this.initStatusBar(_("Select a tile to purchase from another player's barn"));
    possible_purchases.forEach((pp: PossibleMove) => {
        this.addSelectableOnclick(
          Elements.enclosureSpace(pp.src_player_id, pp.src),
          () => this.callUndoably("selectPurcaseDest", async () => this.selectDestinationForPurchase(pp))
        );
      });
    this.addRestartAndUndoButtons();
  }

  private selectDestinationForPurchase(pp: PossibleMove) {
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
  constructor(g: ZoolorettoGame, undoStack: UndoStack) { super(g, undoStack); }

  override doStart() {
    this.initStatusBar(_('Expand zoo?'));
    let current = this.game.getCurrentExtensions(this.player_id);
    this.game.renderExtensions(this.player_id, current + 1);
    this.pushUndoOp('expandZoo', async () => this.game.renderExtensions(this.player_id, current));
    this.addConfirmAndRestartActionButtons('actExpandZoo', {});
  }
};

class DrawTileFlow extends ZooFlow<boolean> {
  constructor(g: ZoolorettoGame, undoStack: UndoStack) { super(g, undoStack); }

  override doStart(lastround: boolean) {
    this.initStatusBar(_('Draw a tile? (cannot undo)'));
    this.markSelected(Elements.drawnTile(lastround));
    this.addConfirmAndRestartActionButtons('actDrawTile', {});
  }
};

class PlaceDrawnTileFlow extends ZooFlow<PlaceDrawnTileArgs> {
  constructor(g: ZoolorettoGame, undoStack: UndoStack) { super(g, undoStack); }

  override doStart(args: PlaceDrawnTileArgs) {
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
    this.slide(tileElem, space).then(() => this.confirmPlaceDrawnTile(tile, truckLoc));
  }

  private confirmPlaceDrawnTile(tile: Tile, tl: TruckLocation) {
    this.game.gamedatas.tile_translations;
    console.log(this.game.tileTranslations);
    this.initStatusBar(_('Place ${tile_type} in truck ${truck_id}?'),
        { tile_type: tile.type,
          tile_description: this.game.tileTranslations.get(tile.type),
          truck_id: tl.truck_id });
    // FIXME: restart doesn't re-highlight the truck spaces.
    this.addConfirmAndRestartActionButtons('actPlaceDrawnTileInTruck', tl);
  }
};


type TruckPlacement = {
  // truck_id is implicit
  truck_pos: number;
  enclosure_id: number;
  enclosure_pos: number;
};

var globalUndoStack: any;

class TakeTruckFlow extends ZooFlow<AvailableTruck[]> {

  constructor(g: ZoolorettoGame, undoStack: UndoStack) { super(g, undoStack); }

  override doStart(availableTrucks: AvailableTruck[]) {
    console.log("starting TakeTruckFlow", this);
    this.initStatusBar(_('Select a truck'));

    availableTrucks.forEach((truck: AvailableTruck) => {
      this.addSelectableOnclick($(IDS.truck(truck.truck_id)),
        async () => {
          await this.playParallel(truck.coin_positions.map(pos =>
            () => this.slideOutAndDestroy(
              Elements.truckTile(truck.truck_id, pos)!,
              this.getPlayerPanelElement(this.player_id))))
            .then(() => {
              this.updateMoneyDelta(truck.money_delta);
              this.callUndoably("chooseTiletoPlace", () => this.chooseTruckTileToPlace(truck.truck_id, truck.playable, availableTrucks, []));
            });
        });
    });
    this.addRestartAndUndoButtons();
  }

  private async chooseTruckTileToPlace(truck_id: number, pps: PossiblePlacement[], availableTrucks: AvailableTruck[],   placedTiles : TruckPlacement[]) {
    if (!pps || pps.length == 0) {
      this.initStatusBar(_('Confirm your truck tile placements'));
      this.addConfirmAndRestartActionButtons(
        'actTakeTruckAndPlaceTiles', {
          truck_id: truck_id,
          delivery_requests: JSON.stringify(placedTiles),
        }
      );
    }
    else {
      this.initStatusBar(_('Choose a tile to place from the selected truck'));
      pps.forEach((pp: PossiblePlacement) => {
        let elem = Elements.truckSpace(truck_id, pp.truck_pos);
        this.addSelectableOnclick(elem, async () => {
          this.callUndoably("chooseDest", () => this.chooseDestination(truck_id, pp, availableTrucks, placedTiles));
        });
      });
      this.addRestartAndUndoButtons();
    }
  }

  private async chooseDestination(truck_id: number, pp: PossiblePlacement, availableTrucks: AvailableTruck[], placedTiles : TruckPlacement[]) {
    this.initStatusBar(_('Choose a destination for the selected tile'));

    pp.encs.forEach((pep: PossibleEnclosurePlacement) => {
      let encElem = Elements.enclosureSpace(this.player_id, pep.space);
      // this.markTargetable(encElem);
      this.addSelectableOnclick(encElem, async (evt:MouseEvent) => {
        let tileElem = Elements.truckSpace(truck_id, pp.truck_pos).firstElementChild as HTMLElement;
        this.slide(tileElem,encElem).then(() => {
          return this.offspringSlide(pep.offspring).then( () => {
            this.updateMoneyDelta(pep.money_delta);
            placedTiles = Array.prototype.concat(placedTiles, { truck_pos: pp.truck_pos, enclosure_id: pep.space.enclosure_id, enclosure_pos: pep.space.pos});
            this.callUndoably("chooseTileToPlace2", () => this.chooseTruckTileToPlace(truck_id, pep.next, availableTrucks, placedTiles));
          });
        });
      });
    });
    this.addRestartAndUndoButtons();
  }
};

class DiscardTileFlow extends ZooFlow<PlacedTile[]> {
  constructor(g: ZoolorettoGame, undoStack: UndoStack) { super(g, undoStack); }

  override doStart(discardables: PlacedTile[]) {
    this.initStatusBar(_('Select a tile in your barn to discard'));
    this.addRestartAndUndoButtons();

    discardables.forEach((dest: PlacedTile) => {
      let space = dest.space;
      this.addSelectableOnclick(
        Elements.enclosureSpace(this.player_id, space),
        async () => {
          await this.slideOutAndDestroy(
            Elements.enclosureTile(this.player_id, space)!,
            $(IDS.OFF_BOARD)).then(() => {
              // should always have money delta
              this.updateMoneyDelta(dest.money_delta!);
              this.callUndoably("confirmDiscard", async () => this.confirmDiscard(dest));
            })
        });
    });
  }

  private confirmDiscard(dest: PlacedTile) {
    this.initStatusBar(_('Confirm discard'));
    this.addConfirmAndRestartActionButtons('actDiscardTile', { barn_pos: dest.space.pos });
  }
}

class MoveTileFlow extends ZooFlow<PossibleMove[]> {
  constructor(g: ZoolorettoGame, undoStack: UndoStack) { super(g, undoStack); }

  override doStart(possibleMoves: PossibleMove[]) {
    this.initStatusBar(_('Select a tile to move'));
    this.addRestartAndUndoButtons();
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

class MainFlow extends ZooFlow<PlayState> {
  constructor(g: ZoolorettoGame, undoStack: UndoStack) { super(g, undoStack); }

  protected override doStart(playState: PlayState) {
    this.initStatusBar(_("You must take an action"));
    if (playState.can_draw) {
      this.game.bga.statusBar.addActionButton(_('Draw tile'),
        () => new DrawTileFlow(this.game, this.undoStack).start(playState.lastround));
    }
    if (playState.available_trucks.length > 0) {
      this.game.bga.statusBar.addActionButton(_('Take truck'),
        () => new TakeTruckFlow(this.game, this.undoStack).start(playState.available_trucks));
    }
    if (playState.possible_moves.length > 0) {
      this.game.bga.statusBar.addActionButton(_('Move tile'),
        () => new MoveTileFlow(this.game, this.undoStack).start(playState.possible_moves));
    }
    if (playState.possible_exchanges.length > 0) {
      this.game.bga.statusBar.addActionButton(_('Exchange animals'),
        () => new ExchangeFlow(this.game, this.undoStack).start(playState.possible_exchanges));
    }
    if (playState.possible_purchases.length > 0) {
      this.game.bga.statusBar.addActionButton(_('Purchase tile'),
        () => new PurchaseTileFlow(this.game, this.undoStack).start(playState.possible_purchases));
    }
    if (playState.possible_discards.length > 0) {
      this.game.bga.statusBar.addActionButton(_('Discard tile'),
        () => new DiscardTileFlow(this.game, this.undoStack).start(playState.possible_discards));
    }
    if (playState.can_expand) {
      this.game.bga.statusBar.addActionButton(_('Expand zoo'),
        () => new ExpandZooFlow(this.game, this.undoStack).start());
    }
  }
}

/** Game class */
class ZoolorettoGame extends BaseGame<ZGamedatas> {
  animations: Animations;
  constructor(bga: Bga<ZGamedatas>) {
    super(bga, []);
  }

  flashParents(offspring: Offspring) : Promise<any> {
    return this.animations.flash(CSS.PARENT, [Elements.tile(offspring.mother), Elements.tile(offspring.father)]);
  }

  private async renderTileDraw(elem: HTMLElement, tile: Tile) {
    // Create the front and back of the tile to flip
    let back = this.makeTileBackSpan();
    let front = this.makeTileSpan(tile);
    // so they're "above" the actual tile
    front.style.position = 'absolute';
    back.style.position = 'absolute';
    // "hide" the original tile
    elem.removeAttribute(Attrs.TILE);
    // Need them in the document
    elem.appendChild(front);
    elem.appendChild(back);

    await this.animations.flip(front, back).then(_ => {
      elem.id = IDS.tile(tile);
      elem.setAttribute(Attrs.TILE, tile.type);
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
    this.bga.gameArea.getElement().appendChild(zhtml.baseStructure());
    for (const player of Object.values(this.gamedatas.players)) {
      this.bga.playerPanels.getElement(player.player_id).appendChild(zhtml.playerPanel(player));
      let counter = new ebg.counter();
      counter.create(IDS.money(player.player_id), { value: player.money });
      this.moneyCounter[player.player_id] = counter;
    }
    this.bankCounter = new ebg.counter();
    this.bankCounter.create(IDS.BANK_MONEY, { value: this.gamedatas.bank_money });
    this.primaryStockCounter = new ebg.counter();
    this.primaryStockCounter.create(IDS.PRIMARY_PILE_COUNT, { value: this.gamedatas.primary_pile_size == 1000 ? null : this.gamedatas.primary_pile_size });
    this.endgameStockCounter = new ebg.counter();
    this.endgameStockCounter.create(IDS.ENDGAME_PILE_COUNT, { value: this.gamedatas.endgame_pile_size });
    this.renderStock();
    this.renderTrucks();
    this.renderEnclosures();
  }

  tileTranslations = new Map<string, string>();;
  private setupTranslations(): void {
    this.gamedatas.tile_translations.forEach(v => this.tileTranslations.set(v.type, v.name));
  }

  override setup(gamedatas: ZGamedatas) {
    super.setup(gamedatas);
    this.animations = new Animations(this.animationManager);
    const twoPlayer = Object.keys(gamedatas.players).length == 2;
    this.setupHtml(twoPlayer);
    this.setupNotifications();
    this.setupScoreSheet();
    this.setupTranslations();
    if (gamedatas.lastround) {
      (this as any).addLastTurnBanner(_('This is the last round!'));
    }

    console.log('Game setup done');
  }

  private setupNotifications(): void {
    this.bga.notifications.setupPromiseNotifications({ logger: console.log });
  }

  public updateMoneyDelta(delta: Moneys): void {
    if (delta.bank) {
      this.bankCounter.incValue(delta.bank);
    }
    for (let player_id in delta.players) {
      this.moneyCounter[player_id]!.incValue(delta.players[player_id]!);
    }
  }

  // FIXME: consider making async to permit animations
  private updateMoneys(moneys: Moneys): void {
    this.bankCounter.toValue(moneys.bank);
    Object.entries(moneys.players).forEach(pv => this.moneyCounter[pv[0]].toValue(pv[1]));
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


  private onUpdateActionButtons_PlayerTurn(playState: PlayState): void {
    globalUndoStack = new UndoStack(this.animationManager.playSequentially);
    new MainFlow(this, globalUndoStack).start(playState);
  }

  private onUpdateActionButtons_PlaceDrawnTile(args: PlaceDrawnTileArgs): void {
    new PlaceDrawnTileFlow(this, new UndoStack(this.animationManager.playSequentially)).start(args);
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
      await this.animations.slideOutAndDestroy(disk, $(IDS.OFF_BOARD))
        .then(() => {
          this.bga.gameArea.addLastTurnBanner(_('This is the last round!'));
        });
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

  private async notif_PlaceDrawnTileInTruck(args: {
    player_id: number,
    truck_id: number,
    truck_pos: number,
    // FIXME: should we figure this out based on where tile is?
    drawn_from_endgame_pile: boolean,
    tile: Tile,
    primary_pile_size: number,
    endgame_pile_size: number }) {
      await this.animations.slideAndAttach(
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

  private async notif_PlaceTruckTile(args: {
      player_id: number,
      truck_id: number,
      delivery: Delivery,
  }) {
    let dest = args.delivery.dest;
    if (!dest) {
      // coin
      await this.animations.slideOutAndDestroy(
        Elements.tile(args.delivery.tile),
          this.bga.playerPanels.getElement(args.player_id),
          {}
        ).then(() => this.addMoney(args.player_id, 1));
    }
    else {
      let anims : AnimationList = [];
      anims.push(() => this.animations.slideAndAttach(
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

  private async notif_TakeTruck(args: {
    player_id: number,
    truck_id: number,
    moneys: Moneys,
  }) {
    await this.animations.slideAndAttach(
        Elements.truck(args.truck_id),
        $(IDS.takenTruck(args.player_id))
      ).then(() => this.updateMoneys(args.moneys));
  }

  private async notif_MoveTile(args: {
    player_id: number,
    tile: Tile,
    dest: Space,
    moneys: Moneys,
  }) {
    this.updateMoneys(args.moneys);
    await this.animations.slideAndAttach(
      Elements.tile(args.tile)!,
      Elements.enclosureSpace(args.player_id, args.dest)
    );
  }

  private async notif_DiscardTile(args: {
    moneys: Moneys,
    tile: Tile,
  }) {
    this.updateMoneys(args.moneys);
    await this.animations.slideOutAndDestroy(Elements.tile(args.tile), $(IDS.OFF_BOARD));
  }

  private async notif_PurchaseTile(args: {
			player_id: number,
      placed_tiles: PlacedTile[],
			moneys: Moneys,
    }) {
    this.updateMoneys(args.moneys);
    await this.animationManager.playSequentially(
      args.placed_tiles.map(pt =>
        () => this.animations.slideAndAttach(Elements.tile(pt.tile)!, Elements.enclosureSpace(args.player_id, pt.space))
      )
    );
  }

  private async notif_EndRound(args: {
    truck_ids_returned: number[],
    dumped_tiles: Tile[],
    last_round: boolean }
  ) {

    let anims: AnimationList = [];
    args.dumped_tiles.forEach(tile =>
      anims.push(() => this.animations.slideOutAndDestroy(Elements.tile(tile), $(IDS.OFF_BOARD)))
    );
    args.truck_ids_returned.forEach(tid =>
      anims.push(() => this.animations.slideAndAttach(Elements.truck(tid), $(IDS.depotSpace(tid))))
    );

    await this.animationManager.playSequentially(anims).then( () => {
      if (args.last_round) {
        (this as any).addLastTurnBanner(_('This is the last round!'));
      }
    })
  }

  private async notif_ExchangeEnclosureAnimals(args: {
    player_id: number,
    placed_tiles: PlacedTile[],
    moneys: Moneys,
  }) {
    this.updateMoneys(args.moneys);
    let anims: AnimationList = [];
    args.placed_tiles.forEach(pt =>  {
      let elem = Elements.tile(pt.tile);
      if (elem) {
        anims.push(() => this.animations.slideAndAttach(elem, Elements.enclosureSpace(args.player_id, pt.space)));
      } else {
        let elem = this.makeTileSpan(pt.tile);
        // FIXME: needed?
        elem.style.transform = 'rotate(0deg)';
        // a created offspring, create and slide it in
        anims.push(() => this.animationManager.slideIn(elem, $(IDS.OFF_BOARD), {}));
      }
    });
    await this.animationManager.playParallel(anims);
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
  readonly special_log_args = {
    tile_type: (args: any) => Html.span({attrs: Attrs.tile(args.tile_type), title: _(args.tile_description)}),
    src_tile_type: (args: any) => Html.span({attrs: Attrs.tile(args.src_tile_type), title: _(args.src_tile_description)}),
    dest_tile_type: (args: any) => Html.span({attrs: Attrs.tile(args.dest_tile_type), title: _(args.dest_tile_description)}),
    coins: (args: any) => Html.span({text: ""+args.coins},
        Html.span({classes: 'zoo-money-label', title: _("coins")}))
  };

  override bgaFormatText(log: string, args: any): { log: string, args: any } {
    try {
      let shadowParent = Html.span({});
      if (log && args && !args.processed) {
        args.processed = true;
        for (const key in this.special_log_args) {
          if (key in args) {
            let e = this.special_log_args[key](args);
            shadowParent.appendChild(e);
            args[key] = shadowParent.getHTML();
            e.remove();
          }
        }
      }
    } catch (e: any) {
      console.error(log, args, 'Exception thrown', e.stack);
    }
    return { log, args };
  }

}
