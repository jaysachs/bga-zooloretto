import { Html } from './html';
import { Tile, ZGamedatas, EnclosureSummary } from './zgametypes';
import { PlayFlow, FlowState } from './flow';
import { BaseGame } from './basegame';
import { CSS, IDS, Elements, ZoolorettoHtml, Attrs, encOf, posOf, toSpace } from './zhtml';
import { AnimationList, MoreAnimations } from './more-animations';
import { BgaScoreSheet, ScoreSheet } from './libs';

//
// interfaces for args & notifs
//

// general use

interface PlacedTile {
  tile: Tile;
  space: number;
  money_delta: Moneys | null;
}

interface Offspring {
  placed_tile: PlacedTile;
  mother: Tile;
  father: Tile;
}

type Moneys = { [playerId: number]: number };

interface Destination {
  space: number;
  offspring: Offspring;
  money_delta: Moneys | null;
}

// FIXME: is this useful?
interface TruckLocation {
  truck_id: number,
  truck_pos: number
};


// LoadDrawnTile state

interface LoadDrawnTileArgs {
  tile: Tile,
  drawn_from_endgame_pile: boolean,
  available_spaces: TruckLocation[]
};



// PlayerTurn state

interface PossibleMoves {
  moves: PossibleMove[];
  money_delta: Moneys;
}

interface PossiblePurchase {
  src_player_id: number;
  src: number;
  dests: Destination[];
  money_delta: Moneys;
}

interface PossibleMove {
  src: number;
  dests: Destination[];
}

interface BarnExchange {
  // the positions in the barn
  positions: number[];
  offspring: Offspring | null;
}

type EnclosureSwaps = Record<number, number[]>;

interface Exchanges {
  // key is src enclosure ID, value is positions with animals
  animal_positions: Record<number, number[]>;
  // No keys are barn (0).
  // key is src enclosure ID, value is possible dest enclosure IDs excluding barn
  enclosures: EnclosureSwaps;
  // key is enc ID, value is the possible exchanges with the barn
  barn: Record<number,BarnExchange[]>;
}

interface PossibleExchanges {
  exchanges: Exchanges;
  money_delta: Moneys | null;
}

interface PossibleDiscards {
  spaces: number[];
  money_delta: Moneys;
}

interface PlayState {
  lastround: boolean;
  can_draw: boolean;
  extension_available: number;
  available_trucks: number[];
  possible_discards: PossibleDiscards;
  possible_moves: PossibleMoves;
  possible_exchanges: PossibleExchanges;
  possible_purchases: PossiblePurchase[];
}

// notif_TakeTruckAndPlaceTiles

interface Delivery {
  truck_pos: number;
  tile: Tile;
  dest: {
    space: number;
    offspring: Offspring | undefined;
  } | undefined;
}

//
// UI flows
//
abstract class ZooFlow<T = undefined> extends PlayFlow<T> {

  protected readonly game: Game;
  protected readonly moreAnimations: MoreAnimations;
  constructor(g: Game, flowState: FlowState) {
    super(g.animationManager, g.bga, flowState);
    this.moreAnimations = new MoreAnimations(this.animationManager);
    this.game = g;
  }

  protected override confirmationsEnabled(): boolean {
    // FIXME: process gamepreferences.json and create constants/accessors/etc
    return this.bga.userPreferences.get(100) > 0;
  }

  protected override useAutoclick(): boolean {
    return this.bga.userPreferences.get(101) > 0;
  }

  override offboard(): HTMLElement {
    return $(IDS.OFF_BOARD);
  }

  private negate(moneyDelta: Moneys): Moneys {
    return Object.fromEntries(Object.entries(moneyDelta).map(kv => [kv[0], -kv[1]]));
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
        const offspringElem = this.game.makeTileSpan(offspring.placed_tile.tile);
        // FIXME: why needed?
        offspringElem.style.transform = 'rotate(0deg)';
        return this.game.flashParents(offspring)
          .then(() => this.slideIn(offspringElem, Elements.enclosureSpace(this.player_id, offspring.placed_tile.space)));
      }
    }
    return Promise.resolve();
  }

}

class LoadDrawnTileFlow extends ZooFlow<LoadDrawnTileArgs> {
  constructor(g: Game, flowState: FlowState) { super(g, flowState); }

  override doStart(args: LoadDrawnTileArgs) {
    this.initStatusBar(_('Place ${tile_type} in an available truck'),
        { tile_type: args.tile.type,
          tile_description: this.game.tileTranslations.get(args.tile.type) });
    const elem = Elements.tile(args.tile)!; // was: Elements.drawnTile(args.drawn_from_endgame_pile);
    this.markSelected(elem);
    args.available_spaces.forEach((truckLoc: TruckLocation) =>
        this.addSelectableOnclick(Elements.truckSpace(truckLoc.truck_id, truckLoc.truck_pos),
          () => this.callUndoably("chooseTiletoPlace",
                () => this.placeDrawnTile(elem, args.tile, truckLoc))));
  }

  private async placeDrawnTile(tileElem: HTMLElement, tile: Tile, truckLoc: TruckLocation) {
    const space = Elements.truckSpace(truckLoc.truck_id, truckLoc.truck_pos);
    this.slide(tileElem, space).then(() => this.confirmLoadDrawnTile(tile, truckLoc));
  }

  private confirmLoadDrawnTile(tile: Tile, tl: TruckLocation) {
    this.initStatusBar(_('Place ${tile_type} in truck ${truck_id}?'),
        { tile_type: tile.type,
          tile_description: this.game.tileTranslations.get(tile.type),
          truck_id: tl.truck_id });
    // FIXME: restart doesn't re-highlight the truck spaces.
    this.addConfirmAndRestartActionButtons('actLoadDrawnTile', tl);
  }
};

interface PossibleDelivery {
    truck_pos: number;
    /*
    tile: string;
    tile_id: number;
    */
    dests: Destination[];
}

interface DeliverTilesArgs {
  truck_id: number;
  possible_deliveries: PossibleDelivery[];
}

class DeliverTilesFlow extends ZooFlow<DeliverTilesArgs> {
  protected async doStart(args: DeliverTilesArgs) {
    const restart = {
      restart: async () => this.bga.actions.performAction('actUndo', {}),
      post: async () =>  Elements.truck(args.truck_id).removeAttribute(Attrs.MARK)
    };
    if (!args.possible_deliveries || args.possible_deliveries.length == 0) {
      this.initStatusBar(_('Confirm your truck tile deliveries'));
      // FIXME: clear marked on confirmation
      this.addConfirmAndRestartActionButtons('actConfirmDelivery', {}, restart);
    }
    else {
      this.initStatusBar(_('Choose a tile to deliver from the selected truck'));
      args.possible_deliveries.forEach((pp: PossibleDelivery) => {
        const elem = Elements.truckSpace(args.truck_id, pp.truck_pos);
        this.addSelectableOnclick(elem, async () => {
          this.callUndoably("chooseDest", () => this.chooseDestination(pp, args.truck_id));
        });
      });
      this.addRestartAndUndoButtons(restart);
    }
  }

  private async chooseDestination(pp: PossibleDelivery, truck_id: number) {
    this.initStatusBar(_('Choose a destination for the selected tile'));

    pp.dests.forEach((dest: Destination) => {
      const encElem = Elements.enclosureSpace(this.player_id, dest.space);
      this.addSelectableOnclick(encElem, async (evt:MouseEvent) => {
        const tileElem = Elements.truckSpace(truck_id, pp.truck_pos).firstElementChild as HTMLElement;
        await this.slide(tileElem,encElem).then(async () => {
          this.offspringSlide(dest.offspring).then(async () => {
            this.updateMoneyDelta(dest.money_delta);
            await this.bga.actions.performAction('actDeliverTile', { truck_pos: pp.truck_pos, enclosure_id: encOf(dest.space), enclosure_pos: posOf(dest.space), confirm_if_done: false })
          });
        });
      });
    });
    this.addRestartAndUndoButtons({
      restart: async () => this.bga.actions.performAction('actUndo', {})});
  }
}

class PlayerTurnFlow extends ZooFlow<PlayState> {
  constructor(g: Game) { super(g, new FlowState(g.animationManager.playSequentially)); }

  protected override doStart(playState: PlayState) {
    // FIXME: update the status bar title based on what's possible?
    this.initStatusBar(_("You must click on a tile to take an action"));

    // Drawing a tile is orthogonal to other actions.
    if (playState.can_draw) {
      let topTile = Elements.drawnTile(playState.lastround);
      if (!topTile && !playState.lastround) {
        topTile = Elements.drawnTile(true);
      }
      this.addSelectableOnclick(
        topTile,
        () => this.drawTile(playState.lastround),
        _('Draw tile')
      );
    }

    // Truck delivery is orthogonal.
    playState.available_trucks.forEach(
      truck_id => this.addSelectableOnclick(
        Elements.truck(truck_id),
        () => this.bga.actions.performAction('actTakeTruck', { truck_id: truck_id }),
        _('Take truck'))
      );

    // Expanding is orthogonal to other actions.
    if (playState.extension_available > 0) {
      this.addSelectableOnclick(
        $(IDS.extension(this.player_id, playState.extension_available)),
        () => this.expandZoo());
    }

    // These can be separate since they exclusively are on other players' boards.
    playState.possible_purchases.forEach((pp: PossiblePurchase) => {
      this.addSelectableOnclick(
        Elements.enclosureSpace(pp.src_player_id, pp.src),
        () => this.purchase(pp),
        _('Purchase tile')
      );
    });

    // Animals in enclosures can only be exchanged, so these are also fine to just do.
    this.exchanges(playState.possible_exchanges);

    // It's moves and discards that are non-orthogonal, i.e. a tile in the barn
    //   can be discarded and possibly moved.
    // Probably want:
    //   if can be moved, then highlight destinations but also give 'Discard' button.
    //   if can't be moved, just give 'Discard' button.
    this.movesOrDiscards(playState.possible_moves, playState.possible_discards);
  }

  private movesOrDiscards(possible_moves: PossibleMoves, possible_discards: PossibleDiscards) {
    const isMoveable = (s : number) => possible_moves.moves.findIndex((m : PossibleMove) => m.src == s) >= 0;
    const isDiscardable = (m: PossibleMove) => possible_discards.spaces.indexOf(m.src) >= 0;

    possible_moves.moves.forEach((m: PossibleMove) => {
      const alsoDiscardable = isDiscardable(m);
      this.addSelectableOnclick(
        Elements.enclosureSpace(this.player_id, m.src),
        () => this.callUndoably("chooseMoveDest" + m.src, async () =>
          this.chooseMoveDest(m, alsoDiscardable, possible_discards.money_delta, possible_moves.money_delta),
        ), alsoDiscardable ? _('Discard or move tile') : _('Move tile')
      )
    });
    possible_discards.spaces.forEach((space: number) => {
      if (!isMoveable(space)) {
        this.addSelectableOnclick(
          Elements.enclosureSpace(this.player_id, space),
          async () => {
            this.updateMoneyDelta(possible_discards.money_delta);
            await this.slideOutAndDestroy(Elements.enclosureTile(this.player_id, space)!,$(IDS.OFF_BOARD))
              .then(() => this.callUndoably("confirmDiscard", async () => this.confirmDiscard(space)))
          }, _('Discard tile'));
        }
    });
  }

  private confirmDiscard(space: number) {
    this.initStatusBar(_('Confirm discard'));
    this.addConfirmAndRestartActionButtons('actDiscardTile', { barn_pos: posOf(space) });
  }

  private chooseMoveDest(pm: PossibleMove, discardable: boolean, discardMoneyDelta: Moneys, moveMoneyDelta: Moneys) {
    this.initStatusBar(_('Select a destination space or'));
    if (discardable) {
      this.bga.statusBar.addActionButton(_('Discard the tile'),
        async () => {
          // FIXME: should this be exposed? better way to do this? wrap addActionButton?
          this.clearOnclicks();
          this.game.updateMoneyDelta(discardMoneyDelta);
          await this.slideOutAndDestroy(Elements.enclosureTile(this.player_id, pm.src)!,$(IDS.OFF_BOARD))
            .then(() => this.confirmDiscard(pm.src));
        });
    }
    this.addRestartAndUndoButtons();
    pm.dests.forEach((dest: Destination) => {
      const elem = Elements.enclosureTile(this.player_id, pm.src);
      const destElem = Elements.enclosureSpace(this.player_id, dest.space)
      this.addSelectableOnclick(destElem,
        async () => await this.slide(elem!, destElem)
          .then(() => {
            this.updateMoneyDelta(moveMoneyDelta);
            this.markMoved(destElem);
            this.callUndoably("confirmMove", async () => this.confirmMove(pm.src, dest));
          })
      )
    });
  }

  private async confirmMove(src: number, dest: Destination) {
    await this.offspringSlide(dest.offspring).then(() => this.updateMoneyDelta(dest.money_delta));
    this.initStatusBar(_('Confirm move'));
    this.addConfirmAndRestartActionButtons('actMoveTile', {
      src_id: encOf(src), src_pos: posOf(src), dest_id: encOf(dest.space), dest_pos: posOf(dest.space)
    });
  }

  private animalSpaces(exchanges: Exchanges, encid: number): HTMLElement[] {
    return exchanges.animal_positions[encid].map(
        pos => Elements.enclosureSpace(this.player_id, toSpace(encid, Number(pos))));
  }

  private exchanges(possible_exchanges: PossibleExchanges) {
    const exchanges = possible_exchanges.exchanges;
    const srcEncs: number[] = [];
    Object.keys(exchanges.enclosures).forEach(e => srcEncs[e] = 1);
    Object.keys(exchanges.barn).forEach(e => srcEncs[e] = 1);
    Object.keys(srcEncs).map(k => Number(k)).forEach((encid: number) => {
      const srcSpaceElems = this.animalSpaces(exchanges, encid);
      srcSpaceElems.forEach(spaceElem => {
        this.addSelectableOnclick(
          spaceElem,
          () => {
            this.callUndoably("selectExchangeDest", async () => {
              srcSpaceElems.forEach(s => this.markSelected(s));
              this.updateMoneyDelta(possible_exchanges.money_delta);
              this.selectExchangeDest(exchanges, encid);
            })
          });
      });
    });
  }

  private selectExchangeDest(exchanges: Exchanges, srcid: number) {
    this.initStatusBar(_("Select the animals to exchange with"));
    const barnDests = exchanges.barn[srcid] ?? [];
    const srcAnimalSpaces = this.animalSpaces(exchanges, srcid);
    exchanges.enclosures[srcid].forEach(destid => {
      const destAnimalSpaces = this.animalSpaces(exchanges, destid);
      destAnimalSpaces.forEach(s => {
        this.addSelectableOnclick(s, async () => {
            const srcSpaces = Elements.enclosureSpaces(this.player_id, srcid);
            const destSpaces = Elements.enclosureSpaces(this.player_id, destid);
            const anims: AnimationList = [];
            let len = Math.max(srcAnimalSpaces.length, destAnimalSpaces.length);
            for (let i = 0; i < len; ++i) {
              anims.push(() => this.moreAnimations.swapFirstChildren(
                srcSpaces[i],
                destSpaces[i])
              );
            }
            this.pushUndoOp("exchange", () => this.animationManager.playParallel(anims));
            await this.animationManager.playParallel(anims)
              .then(() => destAnimalSpaces.forEach(t => this.markMoved(t)))
              .then(() => this.callUndoably("confirmExchange", async () => this.confirmExchange(srcid, destid)));
        });
      });
    });
    // Now handle barn as destination, also dealing with possible offspring in the non-barn
    // Note that there is no enclosure completion bonus for exchanges, so we don't
    //  need to apply money deltas in here.
    barnDests.forEach(be => {
      be.positions.forEach(pos => {
        const barnSpace = Elements.enclosureSpace(this.player_id, toSpace(0, pos));
        this.addSelectableOnclick(barnSpace, async () => {
            const destTiles = be.positions.map(p => Elements.enclosureTile(this.player_id, p));
            const srcSpaces = Elements.enclosureSpaces(this.player_id, srcid);
            let len = Math.max(srcAnimalSpaces.length, destTiles.length);
            const anims: AnimationList = [];
            for (let i = 0; i < len; ++i) {
              anims.push(() => this.moreAnimations.swapFirstChildren(
                srcSpaces[i],
                Elements.enclosureSpace(this.player_id, toSpace(0, be.positions[i]))
              ));
            }
            this.pushUndoOp("exchange", () => this.animationManager.playParallel(anims));
            await this.animationManager.playParallel(anims)
              .then(() => destTiles.forEach(t => this.markMoved(t.parentElement)))
              .then(() => this.callUndoably("confirmExchange", async () => this.confirmExchange(srcid, 0, be.positions)));
        });
      })
    });
    this.addRestartAndUndoButtons();
  }

  private confirmExchange(srcid: number, destid: number, barnPos?: number[]) {
    this.initStatusBar(_("Confirm exchange"));
    this.addConfirmAndRestartActionButtons("actExchangeEnclosureAnimals", {
  		src_enclosure_id : srcid,
		  dest_enclosure_id: destid,
      dest_positions: JSON.stringify(barnPos),
    });
  }

  private purchase(pp: PossiblePurchase) {
    this.updateMoneyDelta(pp.money_delta);
    this.initStatusBar(_("Select a destination for the purchased tile"));
    pp.dests.forEach((dest: Destination) =>
      this.addSelectableOnclick(
        Elements.enclosureSpace(this.player_id, dest.space),
        async () => {
          await this.slide(Elements.enclosureTile(pp.src_player_id, pp.src)!,
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

  private confirmPurchase(pp: PossiblePurchase, dest: Destination) {
    this.initStatusBar(_("Confirm purchase"));
    this.addConfirmAndRestartActionButtons('actPurchaseTile', {
      from_player_id: pp.src_player_id,
      barn_pos: posOf(pp.src),
      enclosure_id: encOf(dest.space),
      enclosure_pos: posOf(dest.space)
    });
  }

  private drawTile(lastround: boolean) {
    this.initStatusBar(_('Draw a tile? (cannot undo)'));
    this.markSelected(Elements.drawnTile(lastround));
    this.addConfirmAndRestartActionButtons('actDrawTile', {});
  }

  private expandZoo() {
    this.initStatusBar(_('Expand zoo?'));
    const current = this.game.getCurrentExtensions(this.player_id);
    this.game.renderExtensions(this.player_id, current + 1);
    $(IDS.extension(this.player_id, current+1)).classList.remove(CSS.SELECTED);
    // FIXME: Need to undo that removal as well?
    this.pushUndoOp('expandZoo', async () => this.game.renderExtensions(this.player_id, current));
    this.addConfirmAndRestartActionButtons('actExpandZoo', {});
  }

}

/** Game class */
export class Game extends BaseGame<ZGamedatas> {
  tileTranslations = new Map<string, string>();;

  private moneyCounter: Counter[] = [];
  private primaryStockCounter: Counter;
  private endgameStockCounter: Counter;
  private scoreSheet: ScoreSheet;

  constructor(bga: Bga<ZGamedatas>) {
    super(bga, Game.special_log_args);
    this.bga.states.register('PlayerTurn', new PlayerTurnFlow(this));
    this.bga.states.register('LoadDrawnTile', new LoadDrawnTileFlow(this, new FlowState(this.animationManager.playSequentially)));
    this.bga.states.register('DeliverTruckTiles', new DeliverTilesFlow(this, new FlowState(this.animationManager.playSequentially)));
  }

  flashParents(offspring: Offspring) : Promise<any> {
    return this.moreAnimations.flash(CSS.FLASH, [Elements.tile(offspring.mother), Elements.tile(offspring.father)]);
  }

  private async renderTileDraw(elem: HTMLElement, tile: Tile): Promise<any> {
    const setTile = () => {
      elem.id = IDS.tile(tile);
      elem.setAttribute(Attrs.TILE, tile.type);
    };
    if (!this.bgaAnimationsActive()) {
      setTile();
      return Promise.resolve(null);
    }
    // Create the front and back of the tile to flip
    const back = this.makeTileBackSpan();
    const front = this.makeTileSpan(tile);

    // "hide" the original tile
    elem.removeAttribute(Attrs.TILE);
    // Need them in the document
    elem.appendChild(front);
    elem.appendChild(back);

    await this.moreAnimations.flip(front, back).then(_ => {
      setTile();
      back.remove();
      front.remove();
    });
  }

  makeTileSpan(tile: Tile): HTMLElement {
    if (tile.type == 'block' || tile.type == '') {
      return this.makeTileBackSpan();
    }
    const id = IDS.tile(tile);
    const elem = $(id);
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

  private renderStock(gamedatas: ZGamedatas) : void {
    const addStockTile = (elemId: string) =>
      $(elemId).insertAdjacentElement('afterbegin', this.makeTileBackSpan() );

    for (let i = Math.min(gamedatas.primary_pile_size, 5); i > 0; i--) {
      addStockTile(IDS.PRIMARY_PILE_TILES);
    }
    for (let i = Math.min(gamedatas.endgame_pile_size, 5); i > 0; i--) {
      addStockTile(IDS.ENDGAME_PILE_TILES);
    }
    if (gamedatas.drawntile) {
      const top = Elements.drawnTile(gamedatas.lastround);
      if (top) {
        // FIXME: might be nicer to create this properly ...
        top.setAttribute(Attrs.TILE, gamedatas.drawntile.type);
        top.id = IDS.tile(gamedatas.drawntile);
      }
    }
    if (!gamedatas.lastround && gamedatas.primary_pile_size > 0) {
      $(IDS.ENDGAME_PILE_TILES).appendChild(Html.span({ id: IDS.DISK, classes: 'zoo-disk' }));
    }
  }

  private renderTrucks(gamedatas: ZGamedatas): void {
    for (const truck of gamedatas.trucks) {
      truck.contents.forEach(contents => {
        if (contents.tile) {
          Elements.truckSpace(truck.truck_id, contents.pos).append(this.makeTileSpan(contents.tile));
        }
      });

      if (truck.taken_by_player_id) {
        // move it to player panel
        const tElem = $(IDS.depotSpace(truck.truck_id)).firstElementChild as HTMLElement;
        $(IDS.takenTruck(truck.taken_by_player_id)).appendChild(tElem);
        this.bga.gameui.disablePlayerPanel(truck.taken_by_player_id);
      }
    }
  }

  private renderEnclosures(gamedatas: ZGamedatas): void {
    for (const player_id in gamedatas.enclosures) {
      gamedatas.enclosures[player_id]!.forEach(es => {
        if (es.tile) {
          Elements.enclosureSpace(Number(player_id), es.space).append(this.makeTileSpan(es.tile));
        }
      })
    }
  }

  private updateEnclosureSummaries(summaries: EnclosureSummary[]) {
    summaries.forEach(summary => {
      const elem = $(IDS.playerPanelBoardSummary(summary.player_id, summary.enclosure_id));
      const type = summary.animal_type;
      elem.setAttribute(Attrs.TILE, summary.animal_type);
      if (type) {
        elem.title = this.tileTranslations.get(type) ?? type;
        elem.firstElementChild!.textContent = `${summary.count}`;
      } else {
        elem.title = '';
        elem.firstElementChild!.textContent = '';
      }
    });
  }

  private setupHtml(gamedatas: ZGamedatas): void {
    const zhtml = new ZoolorettoHtml(gamedatas, this.bga.gameui.player_id);
    this.bga.gameArea.getElement().appendChild(zhtml.baseStructure());
    for (const player of Object.values(gamedatas.players)) {
      this.bga.playerPanels.getElement(player.player_id).append(...zhtml.playerPanel(player));
      const counter = new ebg.counter();
      counter.create(IDS.money(player.player_id), { value: player.money });
      this.moneyCounter[player.player_id] = counter;
    }
    this.primaryStockCounter = new ebg.counter();
    this.primaryStockCounter.create(IDS.PRIMARY_PILE_COUNT, { value: gamedatas.primary_pile_size == 1000 ? null : gamedatas.primary_pile_size });
    this.endgameStockCounter = new ebg.counter();
    this.endgameStockCounter.create(IDS.ENDGAME_PILE_COUNT, { value: gamedatas.endgame_pile_size });
    this.renderStock(gamedatas);
    this.renderTrucks(gamedatas);
    this.renderEnclosures(gamedatas);
    this.updateEnclosureSummaries(gamedatas.enclosure_summaries);
    this.bga.userPreferences.onChange = (prefId: number, value: number) => {
      switch (prefId) {
        case 104:
          if (value) {
            $(IDS.GAME).classList.add('zoo-indicators');
          } else {
            $(IDS.GAME).classList.remove('zoo-indicators');
          }
      }
    }
  }

  private setupTranslations(gamedatas: ZGamedatas): void {
    gamedatas.tile_translations.forEach(v => this.tileTranslations.set(v.type, v.name));
  }

  setup(gamedatas: ZGamedatas) {
    this.setupTranslations(gamedatas);
    this.setupHtml(gamedatas);
    this.setupNotifications();
    this.setupScoreSheet(gamedatas);
    if (gamedatas.lastround) {
      this.showLastTurnBanner();
    }

    console.log('Game setup done');
  }

  private showLastTurnBanner() {
    this.bga.gameArea.addLastTurnBanner(_('This is the last round!'));
  }

  private setupNotifications(): void {
    this.bga.notifications.setupPromiseNotifications({ logger: console.log });
  }

  public updateMoneyDelta(delta: Moneys): void {
    Object.entries(delta).forEach(pv => this.addMoney(Number(pv[0]), pv[1]));
  }

  // FIXME: consider making async to permit animations
  private updateMoneys(moneys: Moneys): void {
    Object.entries(moneys).forEach(pv => this.moneyCounter[pv[0]].toValue(pv[1]));
  }

  private addMoney(player_id: number, delta: number): void {
    this.moneyCounter[player_id]!.incValue(delta);
  }

  // FIXME: consider making this async to allow for animation
  renderExtensions(player_id : number, extensions: number): void {
    const elem = $(IDS.boardId(player_id));
    elem.setAttribute(Attrs.EXTENSIONS, String(extensions));
  }

  getCurrentExtensions(player_id : number): number {
    const elem = $(IDS.boardId(player_id));
    return Number(elem.getAttribute(Attrs.EXTENSIONS) || 0);
  }

  //
  // Entry point for player turn
  //

  private async notif_DrawTile(
    args: {
      tile: Tile,
      drawn_from_endgame_pile: boolean,
    }
  ): Promise<void> {
    const disk = $(IDS.DISK);
    if (args.drawn_from_endgame_pile) {
      this.showLastTurnBanner();
      if (disk) {
        await this.moreAnimations.slideOutAndDestroy(disk, $(IDS.OFF_BOARD))
      }
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

  private async notif_LoadDrawnTile(args: {
    player_id: number,
    truck_id: number,
    truck_pos: number,
    // FIXME: should we figure this out based on where tile is?
    drawn_from_endgame_pile: boolean,
    tile: Tile,
    primary_pile_size: number,
    endgame_pile_size: number }) {
      await this.moreAnimations.slideAndAttach(
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

  private async notif_DeliverTruckTile(args: {
      player_id: number,
      truck_id: number,
      delivery: Delivery,
  }) {
    const dest = args.delivery.dest;
    if (!dest) {
      // coin
      await this.moreAnimations.slideOutAndDestroy(
        Elements.tile(args.delivery.tile),
          this.bga.playerPanels.getElement(args.player_id),
        ).then(() => this.addMoney(args.player_id, 1));
    }
    else {
      const anims : AnimationList = [];
      anims.push(() => this.moreAnimations.slideAndAttach(
        Elements.tile(args.delivery.tile)!,
        Elements.enclosureSpace(args.player_id, dest.space))
      );
      if (dest.offspring) {
        const offspring = dest.offspring!;
        if (!$(IDS.tile(offspring.placed_tile.tile))) {
          anims.push(() => this.flashParents(offspring));
          anims.push(() => {
            const elem = this.makeTileSpan(offspring.placed_tile.tile);
            const parent = Elements.enclosureSpace(args.player_id, offspring.placed_tile.space);
            parent.appendChild(elem);
            return this.animationManager.slideIn(elem, $(IDS.OFF_BOARD));
          });
        }
      }
      await this.animationManager.playSequentially(anims);
    }
  }

  private async notif_StartDelivery(args: {
    player_id: number,
    truck_id: number,
    coin_positions: number[],
    moneys: Moneys,
  }) {
    Elements.truck(args.truck_id).classList.add(CSS.SELECTED);
    const coinElems = args.coin_positions.map(pos => Elements.truckTile(args.truck_id, pos)).filter(e => e);
    await this.animationManager.playParallel(coinElems.map(elem =>
      () => this.animationManager.slideOutAndDestroy(
              elem!, this.bga.playerPanels.getElement(args.player_id),{})))
      .then(() => this.updateMoneys(args.moneys))
  }

  private async notif_DeliveryCompleted(args: {
    player_id: number,
    truck_id: number,
    moneys: Moneys,
    enclosure_summaries: EnclosureSummary[],
  }) {
    const elem = Elements.truck(args.truck_id);
    await this.moreAnimations.slideAndAttach(elem, $(IDS.takenTruck(args.player_id)))
      .then(() => {
        elem.classList.remove(CSS.SELECTED);
        this.updateMoneys(args.moneys);
        this.updateEnclosureSummaries(args.enclosure_summaries);
        this.bga.gameui.disablePlayerPanel(args.player_id);
      })
  }

  private async notif_MoveTile(args: {
    player_id: number,
    tile: Tile,
    dest: number,
    moneys: Moneys,
    enclosure_summaries: EnclosureSummary[],
  }) {
    this.updateMoneys(args.moneys);
    await this.moreAnimations.slideAndAttach(
      Elements.tile(args.tile)!,
      Elements.enclosureSpace(args.player_id, args.dest)
    )
      .then(() => this.updateEnclosureSummaries(args.enclosure_summaries))
  }

  private async notif_DiscardTile(args: {
    moneys: Moneys,
    tile: Tile,
    enclosure_summaries: EnclosureSummary[],
  }) {
    this.updateMoneys(args.moneys);
    await this.moreAnimations.slideOutAndDestroy(Elements.tile(args.tile), $(IDS.OFF_BOARD))
      .then(() => this.updateEnclosureSummaries(args.enclosure_summaries))
  }

  private async notif_PurchaseTile(args: {
			player_id: number,
      placed_tiles: PlacedTile[],
			moneys: Moneys,
      enclosure_summaries: EnclosureSummary[],
    }) {
    this.updateMoneys(args.moneys);
    await this.animationManager.playSequentially(
      args.placed_tiles.map(pt =>
        () => this.moreAnimations.slideAndAttach(Elements.tile(pt.tile)!, Elements.enclosureSpace(args.player_id, pt.space))
      )
    )
      .then(() => this.updateEnclosureSummaries(args.enclosure_summaries))
  }

  private async notif_EndRound(args: {
    truck_ids_returned: number[],
    dumped_tiles: Tile[],
    last_round: boolean }
  ) {

    const anims: AnimationList = [];
    args.dumped_tiles.forEach(tile =>
      anims.push(() => this.moreAnimations.slideOutAndDestroy(Elements.tile(tile), $(IDS.OFF_BOARD)))
    );
    args.truck_ids_returned.forEach(tid =>
      anims.push(() => this.moreAnimations.slideAndAttach(Elements.truck(tid), $(IDS.depotSpace(tid))))
    );

    await this.animationManager.playSequentially(anims).then( () => {
      this.bga.gameui.enableAllPlayerPanels();
      if (args.last_round) {
        this.showLastTurnBanner();
      }
    })
  }

  private async notif_ExchangeEnclosureAnimals(args: {
    player_id: number,
    placed_tiles: PlacedTile[],
    moneys: Moneys,
    enclosure_summaries: EnclosureSummary[],
  }) {
    this.updateMoneys(args.moneys);
    const anims: AnimationList = [];
    args.placed_tiles.forEach(pt =>  {
      const elem = Elements.tile(pt.tile);
      if (elem) {
        anims.push(() => this.moreAnimations.slideAndAttach(elem, Elements.enclosureSpace(args.player_id, pt.space)));
      } else {
        const elem = this.makeTileSpan(pt.tile);
        // FIXME: needed?
        elem.style.transform = 'rotate(0deg)';
        // a created offspring, create and slide it in
        anims.push(() => this.animationManager.slideIn(elem, $(IDS.OFF_BOARD), {}));
      }
    });
    await this.animationManager.playParallel(anims)
      .then(() => this.updateEnclosureSummaries(args.enclosure_summaries))
  }

  private setupScoreSheet(gamedatas: ZGamedatas): void {
    this.scoreSheet = new BgaScoreSheet.ScoreSheet(
      $(IDS.SCORE_SHEET),
      {
        animationsActive: () => this.bgaAnimationsActive(),
        // playerNameWidth: 80,
        // playerNameHeight: 30,
        // entryLabelWidth: 120,
        // entryLabelHeight: 20,
        classes: 'zoo-score-sheet',
        players: gamedatas.players,
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
        scores: gamedatas.endScores,
        onScoreDisplayed: (property: string, playerId: number, score: number) => {
          if (property === 'total') {
            this.bga.playerPanels.getScoreCounter(playerId).setValue(score);
          }
        },
      }
    );
  }

  private async notif_GameEnded(args: { endScores: any, }): Promise<void> {
    await this.scoreSheet.setScores( args.endScores, { startBy: this.bga.gameui.player_id, } );
  }

  ///////
  private static readonly special_log_args : Record<string, (args: any) => HTMLElement> = {
    tile_type: (args: any) => Html.span({attrs: Attrs.tile(args.tile_type), title: _(args.tile_description)}),
    src_tile_type: (args: any) => Html.span({attrs: Attrs.tile(args.src_tile_type), title: _(args.src_tile_description)}),
    dest_tile_type: (args: any) => Html.span({attrs: Attrs.tile(args.dest_tile_type), title: _(args.dest_tile_description)}),
    coins: (args: any) => Html.span({text: ""+args.coins+" "},
        Html.span({classes: 'zoo-money-label', title: _("coins")}))
  };
}
