import { AnimationList } from "./more-animations";
import { ZooFlow } from "./zflow";
import { Delivery, EnclosureSummary, Moneys, Offspring, PlacedTile, Tile } from "./zgametypes";
import { Elements, encOf, IDS, CSS, posOf, toSpace, Attrs } from "./zhtml";
import { GameView } from "./zview";


// PlayerTurn state

interface PossibleMoves {
  moves: PossibleMove[];
  money_delta: Moneys;
}

interface PossiblePurchase {
  src_player_id: number;
  src: number;
  dests: PlacedTile[];
  money_delta: Moneys;
}

interface PossibleMove {
  src: number;
  dests: PlacedTile[];
}

interface BarnExchange {
  // the positions in the barn
  positions: number[];
  offspring: Offspring | null;
}

interface Exchanges {
  // key is src enclosure ID, value is positions with animals
  animal_positions: Record<number, number[]>;
  // No keys are barn (0).
  // key is src enclosure ID, value is possible dest enclosure IDs excluding barn
  enclosures: Record<number, number[]>;
  // key is enc ID, value is the possible exchanges with the barn
  barn: Record<number, BarnExchange[]>;
}

interface PossibleExchanges {
  exchanges: Exchanges;
  money_delta: Moneys | null;
}

interface PossibleDiscards {
  spaces: number[];
  money_delta: Moneys;
}

interface Placement {
  truck_pos:number;
  enclosure_id:number;
  enclosure_pos:number;
}

interface PossibleDelivery {
  truck_pos: number;
  dests: PlacedTile[];
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

export class PlayerTurnFlow extends ZooFlow<PlayState> {
  constructor(gameView: GameView) { super(gameView); }

  protected override start(playState: PlayState) {
    this.initStatusBar(_("You must click on a tile to take an action"));

    // All actions are independent to other actions, in terms of what to click on to
    // initiate that action ...
    this.wireUpDraw(playState.can_draw, playState.lastround);
    this.wireUpDeliveries(playState.available_trucks);
    this.wireUpExpansions(playState.extension_available);
    this.wireUpPurchases(playState.possible_purchases);
    this.wireUpExchanges(playState.possible_exchanges);

    // ... except moves and discards, which both start by clicking a tile in the barn
    this.wireUpMovesOrDiscards(playState.possible_moves, playState.possible_discards);
  }

  private wireUpDraw(canDraw: boolean, lastRound: boolean) {
    if (canDraw) {
      let topTile = Elements.drawnTile(lastRound);
      if (!topTile && !lastRound) {
        topTile = Elements.drawnTile(true);
      }
      this.addSelectableOnclick(
        topTile,
        () => this.drawTile(lastRound),
        _('Draw tile')
      );
    }
  }

  // Draw tile

  private drawTile(lastround: boolean) {
    this.initStatusBar(_('Draw a tile?'));
    this.markSelected(Elements.drawnTile(lastround));
    this.addConfirmAndRestartActionButtons('actDrawTile', {});
  }

  // move/discard

  private wireUpMovesOrDiscards(possible_moves: PossibleMoves, possible_discards: PossibleDiscards) {
    const isMoveable = (s: number) => possible_moves.moves.findIndex((m: PossibleMove) => m.src == s) >= 0;
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
            const elem = Elements.enclosureTile(this.player_id, space)!;
            const tt = elem.getAttribute(Attrs.TILE);
            await this.slideOutAndDestroy(elem, $(IDS.BOX));
            this.callUndoably("confirmDiscard", async () => this.confirmDiscard(space, tt));
          }, _('Discard tile'));
      }
    });
  }

  // Discard

  private confirmDiscard(space: number, tt: string) {
    this.initStatusBar(_('Discard ${tile_type}?'), { tile_type: tt });
    this.addConfirmAndRestartActionButtons('actDiscardTile', { barn_pos: posOf(space) });
  }

  // Move

  private chooseMoveDest(pm: PossibleMove, discardable: boolean, discardMoneyDelta: Moneys, moveMoneyDelta: Moneys) {
    this.initStatusBar(_('Select a destination for ${tile_type}'), {tile_type: pm.dests[0].tile.type});
    if (discardable) {
      this.bga.statusBar.addActionButton(_('Discard it'),
        async () => {
          // FIXME: should this be exposed? better way to do this? wrap addActionButton?
          this.clearOnclicks();
          this.view.updateMoneyDelta(discardMoneyDelta);
          await this.slideOutAndDestroy(Elements.enclosureTile(this.player_id, pm.src)!, $(IDS.BOX));
          this.confirmDiscard(pm.src, 'FIXME');
        });
    }
    this.addRestartAndUndoButtons();
    pm.dests.forEach((dest: PlacedTile) => {
      const elem = Elements.enclosureTile(this.player_id, pm.src);
      const destElem = Elements.enclosureSpace(this.player_id, dest.space)
      this.addSelectableOnclick(destElem,
        async () => {
          await this.slide(elem!, destElem);
          this.updateMoneyDelta(moveMoneyDelta);
          this.callUndoably("confirmMove", async () => this.confirmMove(pm.src, dest));
        }
      )
    });
  }

  private async confirmMove(src: number, dest: PlacedTile) {
    await this.offspringSlide(dest.offspring);
    this.initStatusBar(_("Move ${tile_type}?"), // to ${enclosure_description}
      {  tile_type: dest.tile.type });
    this.addConfirmAndRestartActionButtons('actMoveTile', {
      src_id: encOf(src), src_pos: posOf(src), dest_id: encOf(dest.space), dest_pos: posOf(dest.space)
    });
  }

  // Expand

  private wireUpExpansions(extension_available: number) {
    if (extension_available > 0) {
      this.addSelectableOnclick(
        $(IDS.extension(this.player_id, extension_available)),
        () => this.expandZoo());
    }
  }

  private expandZoo() {
    this.initStatusBar(_('Expand zoo?'));
    const current = this.view.getCurrentExtensions(this.player_id);
    this.view.renderExtensions(this.player_id, current + 1);
    // FIXME: Need to undo that removal as well?
    this.pushUndoOp('expandZoo', async () => this.view.renderExtensions(this.player_id, current));
    this.addConfirmAndRestartActionButtons('actExpandZoo', {});
  }

  // Exchange

  private wireUpExchanges(possible_exchanges: PossibleExchanges) {
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

  private animalSpaces(exchanges: Exchanges, encid: number): HTMLElement[] {
    return exchanges.animal_positions[encid].map(
      pos => Elements.enclosureSpace(this.player_id, toSpace(encid, Number(pos))));
  }

  private wireUpExchangeDests(
      srcid: number, srcAnimalSpaces: HTMLElement[],
      destid: number, destAnimalSpaces: HTMLElement[],
      positions?: number[])
  {
    // the filter prevents empty barn spaces being clickable
    destAnimalSpaces.filter(s => s.firstElementChild).forEach(s => {
        this.addSelectableOnclick(s, async () => {
          // FIXME: make a "swapEnclosures" method on GameView and use it here and below ...
          const srcSpaces = Elements.enclosureSpaces(this.player_id, srcid);
          const destSpaces =
            positions
            ? positions.map(p => Elements.enclosureSpace(this.player_id, toSpace(destid, p)))
            : Elements.enclosureSpaces(this.player_id, destid);
          const anims: AnimationList = [];
          let len = Math.max(srcAnimalSpaces.length, destAnimalSpaces.length);
          for (let i = 0; i < len; ++i) {
            anims.push(() => this.view.moreAnimations.swapFirstChildren(
              srcSpaces[i],
              destSpaces[i])
            );
          }
          this.mark(s, 'none');
          this.pushUndoOp("exchange", () => this.animationManager.playParallel(anims));
          await this.animationManager.playParallel(anims);
          this.callUndoably("confirmExchange", async () => this.confirmExchange(srcid, destid, positions));
        });
    });
  }

  private selectExchangeDest(exchanges: Exchanges, srcid: number) {
    this.initStatusBar(_("Select the animals to exchange with"));
    const srcAnimalSpaces = this.animalSpaces(exchanges, srcid);
    exchanges.enclosures[srcid]?.forEach(destid => {
      this.wireUpExchangeDests(srcid, srcAnimalSpaces, destid, this.animalSpaces(exchanges, destid))
    });
    // Now handle barn as destination, also dealing with possible offspring in the non-barn
    // Note that there is no enclosure completion bonus for exchanges, so we don't
    //  need to apply money deltas in here.
    exchanges.barn[srcid]?.forEach(be => {
      const destSpaces = be.positions.map(p => Elements.enclosureSpace(this.player_id, toSpace(0, p)));
      this.wireUpExchangeDests(srcid, srcAnimalSpaces, 0, destSpaces, be.positions);
    });
    this.addRestartAndUndoButtons();
  }

  private confirmExchange(srcid: number, destid: number, barnPos?: number[]) {
    this.initStatusBar(_("Exchange animals?"), {});
    this.addConfirmAndRestartActionButtons("actExchangeEnclosureAnimals", {
      src_enclosure_id: srcid,
      dest_enclosure_id: destid,
      dest_positions: JSON.stringify(barnPos),
    });
  }

  // Purchase tile

  private wireUpPurchases(possible_purchases: PossiblePurchase[]) {
    possible_purchases.forEach((pp: PossiblePurchase) => {
      this.addSelectableOnclick(
        Elements.enclosureSpace(pp.src_player_id, pp.src),
        () => this.purchase(pp),
        _('Purchase tile')
      );
    });
  }

  private purchase(pp: PossiblePurchase) {
    this.updateMoneyDelta(pp.money_delta);
    this.initStatusBar(_("Select a destination for the purchased ${tile_type}"), { tile_type: pp.dests[0].tile.type });
    pp.dests.forEach((dest: PlacedTile) =>
      this.addSelectableOnclick(
        Elements.enclosureSpace(this.player_id, dest.space),
        async () => {
          await this.slide(Elements.enclosureTile(pp.src_player_id, pp.src)!,
            Elements.enclosureSpace(this.player_id, dest.space));
          if (dest.offspring) {
            await this.offspringSlide(dest.offspring);
          }
          this.callUndoably("confirmPurcase", async () => this.confirmPurchase(pp, dest))
        }
      ));
    this.addRestartAndUndoButtons();
  }

  private confirmPurchase(pp: PossiblePurchase, dest: PlacedTile) {
    this.initStatusBar(_("Purchase ${tile_type} from ${player_name}?"),
      {  tile_type: dest.tile.type, player_name: this.bga.players.getFormattedPlayerName(pp.src_player_id) });
    this.addConfirmAndRestartActionButtons('actPurchaseTile', {
      from_player_id: pp.src_player_id,
      barn_pos: posOf(pp.src),
      enclosure_id: encOf(dest.space),
      enclosure_pos: posOf(dest.space)
    });
  }

  // Truck delivery

  private wireUpDeliveries(available_trucks: number[]) {
    available_trucks.forEach(
      truck_id => this.addSelectableOnclick(
        Elements.truck(truck_id),
        () => this.doDelivery(truck_id, []),
        _('Take truck'))
    );
  }

  async notif_CompletionCoins(args: {
    player_id: number;
    coins: number;
  }) {
    // no need to update anything?
  }

  async notif_DeliverCoins(args: { player_id: number, truck_id: number, coin_tiles: Tile[] }) {
    const anims = args.coin_tiles.map(c =>
      () => this.view.moreAnimations.slideOutAndDestroy(Elements.tile(c),
        this.bga.playerPanels.getElement(args.player_id)));
    await this.view.animationManager.playSequentially(anims);
    this.view.addMoney(args.player_id, 1);
  }

  async notif_DeliverTruckTile(args: {
      player_id: number,
      truck_id: number,
      delivery: Delivery,
  }) {
    const dest = args.delivery.placed_tile;
    await this.view.moreAnimations.slideAndAttach(
      Elements.tile(args.delivery.placed_tile.tile)!,
      Elements.enclosureSpace(args.player_id, dest.space));
    if (dest.offspring) {
      // FIXME: it would be nice to move ZooFlow::offspringSlide
      //   but it's embedded in the flow, with the pushed undo op.
      const offspring = dest.offspring!;
      if (!$(IDS.tile(offspring.placed_tile.tile))) {
        await this.view.flashParents(offspring);
        const elem = this.view.tileSpan(offspring.placed_tile.tile);
        const parent = Elements.enclosureSpace(args.player_id, offspring.placed_tile.space);
        parent.appendChild(elem);
        await this.animationManager.slideIn(elem, $(IDS.BOX));
      }
    }
  }

  async notif_Offspring(args: {
    player_id: number;
    offspring: Offspring;
  }) {
    const elem = Elements.tile(args.offspring.placed_tile.tile);
    if (!elem) {
      $(IDS.BOX).appendChild(this.view.tileSpan(args.offspring.placed_tile.tile));
    }
  }

  notif_DeliverPendingTruckTiles(args: {
    truck_id: number;
    deliveries: Delivery[];
    possible_deliveries: PossibleDelivery[];
  }) {
    this.chooseTruckTileToPlace(args.truck_id, args.deliveries, args.possible_deliveries);
  }

  doDelivery(truck_id: number, deliveries: Delivery[], truck_pos?: number, dest?:PlacedTile): Promise<any> {
    let placements: Placement[] = deliveries.map(d => {
      return {
        truck_pos: d.truck_pos,
        enclosure_id: encOf(d.placed_tile.space),
        enclosure_pos: posOf(d.placed_tile.space)} });
    if (truck_pos) {
      placements.push({
        truck_pos: truck_pos,
        enclosure_id: encOf(dest.space),
        enclosure_pos: posOf(dest.space)
      })
    }
    return this.bga.actions.performAction('actDeliverPendingTiles', { truck_id: truck_id, placements: JSON.stringify(placements) });
  }

  private async chooseTruckTileToPlace(truck_id: number, deliveries: Delivery[], pps: PossibleDelivery[]) {
    if (!pps || pps.length == 0) {
      this.initStatusBar(_('Deliver tiles?'));
      let placements: Placement[] = deliveries.map(d => {
        return {
          truck_pos: d.truck_pos,
          enclosure_id: encOf(d.placed_tile.space),
          enclosure_pos: posOf(d.placed_tile.space)} });
      this.addConfirmAndRestartActionButtons(
        'actTakeTruckAndPlaceTiles', {
          truck_id: truck_id,
          placements: JSON.stringify(placements),
        }
      );
    }
    else {
      this.initStatusBar(_('Choose a tile to deliver from the selected truck'));
      pps.forEach((pp: PossibleDelivery) => {
        let elem = Elements.truckSpace(truck_id, pp.truck_pos);
        this.addSelectableOnclick(elem, async () => {
          this.callUndoably("chooseDest", () => this.chooseDestination(truck_id, pp, deliveries));
        });
      });
      this.addRestartAndUndoButtons();
    }
  }

  private pipelineDeliverySlide = true;

  private async chooseDestination(truck_id: number, pp: PossibleDelivery, deliveries: Delivery[]) {
    this.initStatusBar(_('Choose a destination for the selected tile'));

    pp.dests.forEach((dest: PlacedTile) => {
      let encElem = Elements.enclosureSpace(this.player_id, dest.space);
      this.addSelectableOnclick(encElem, async (evt:MouseEvent) => {
        let tileElem = Elements.truckSpace(truck_id, pp.truck_pos).firstElementChild as HTMLElement;
        if (this.pipelineDeliverySlide) {
          await Promise.all([
            this.slide(tileElem,encElem).then(() =>
              this.offspringSlide(dest.offspring)),
            this.doDelivery(truck_id, deliveries, pp.truck_pos, dest)
          ]);
        } else {
          await this.slide(tileElem,encElem);
          await this.offspringSlide(dest.offspring);
          this.doDelivery(truck_id, deliveries, pp.truck_pos, dest);
        }
      });
    });
    this.addRestartAndUndoButtons();
  }

  private async notif_DeliveryCompleted(args: {
    player_id: number,
    truck_id: number,
    moneys: Moneys,
    enclosure_summaries: EnclosureSummary[],
  }) {
    const elem = Elements.truck(args.truck_id);
    await this.view.moreAnimations.slideAndAttach(elem, $(IDS.takenTruck(args.player_id)), { bump: 1, toPlaceholder: 'off' });
    Elements.truck(args.truck_id).removeAttribute(Attrs.MARK);
    this.view.updateMoneys(args.moneys);
    this.view.updateEnclosureSummaries(args.enclosure_summaries);
    this.bga.gameui.disablePlayerPanel(args.player_id);
  }

  private async notif_MoveTile(args: {
    player_id: number,
    tile: Tile,
    dest: number,
    moneys: Moneys,
    enclosure_summaries: EnclosureSummary[],
  }) {
    this.view.updateMoneys(args.moneys);
    await this.view.moreAnimations.slideAndAttach(
      Elements.tile(args.tile)!,
      Elements.enclosureSpace(args.player_id, args.dest)
    );
    this.view.updateEnclosureSummaries(args.enclosure_summaries);
  }

  private async notif_DiscardTile(args: {
    moneys: Moneys,
    tile: Tile,
    enclosure_summaries: EnclosureSummary[],
  }) {
    this.view.updateMoneys(args.moneys);
    await this.view.moreAnimations.slideOutAndDestroy(Elements.tile(args.tile), $(IDS.BOX));
    this.view.updateEnclosureSummaries(args.enclosure_summaries);
  }

  private async notif_PurchaseTile(args: {
			player_id: number,
      placed_tile: PlacedTile,
			moneys: Moneys,
      enclosure_summaries: EnclosureSummary[],
    }) {
    this.view.updateMoneys(args.moneys);
    const tiles = [args.placed_tile];
    if (args.placed_tile.offspring) {
      tiles.push(args.placed_tile.offspring.placed_tile);
    }
    await this.animationManager.playSequentially(
      tiles.map(pt => () => this.view.moreAnimations.slideAndAttach(Elements.tile(pt.tile)!, Elements.enclosureSpace(args.player_id, pt.space)))
      );
    this.view.updateEnclosureSummaries(args.enclosure_summaries);
  }

  private async notif_ExpandZoo(args: {
      player_id: number,
      purchased_extensions: number,
      moneys: Moneys,
    }) {
    this.view.renderExtensions(args.player_id, args.purchased_extensions);
    this.view.updateMoneys(args.moneys);
  }

  private async notif_ExchangeEnclosureAnimals(args: {
    player_id: number,
    placed_tiles: PlacedTile[],
    moneys: Moneys,
    enclosure_summaries: EnclosureSummary[],
  }) {
    this.view.updateMoneys(args.moneys);
    const anims: AnimationList = [];
    args.placed_tiles.forEach(pt =>  {
      const elem = Elements.tile(pt.tile);
      if (elem) {
        anims.push(() => this.view.moreAnimations.slideAndAttach(elem, Elements.enclosureSpace(args.player_id, pt.space)));
      } else {
        const elem = this.view.tileSpan(pt.tile);
        // FIXME: needed?
        elem.style.transform = 'rotate(0deg)';
        // a created offspring, create and slide it in
        anims.push(() => this.animationManager.slideIn(elem, $(IDS.BOX), {}));
      }
    });
    await this.animationManager.playParallel(anims);
    this.view.updateEnclosureSummaries(args.enclosure_summaries);
  }

  private async notif_DrawTile(
    args: {
      tile: Tile,
      drawn_from_endgame_pile: boolean,
    }
  ): Promise<void> {
    const disk = $(IDS.DISK);
    if (args.drawn_from_endgame_pile) {
      this.view.showLastTurnBanner();
      if (disk) {
        await this.view.moreAnimations.slideOutAndDestroy(disk, $(IDS.BOX))
      }
    }
    await this.view.renderTileDraw(Elements.drawnTile(args.drawn_from_endgame_pile), args.tile);
  }

};
