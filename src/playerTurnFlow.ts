import { AnimationList } from "./more-animations";
import { ZooFlow } from "./zflow";
import { Destination, Moneys, Offspring } from "./zgametypes";
import { Elements, encOf, IDS, CSS, posOf, toSpace } from "./zhtml";
import { GameView } from "./zview";


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
    // FIXME: update the status bar title based on what's possible?
    this.initStatusBar(_("You must click on a tile to take an action"));

    // Drawing a tile is orthogonal to other actions.
    this.wireUpDraw(playState.can_draw, playState.lastround);

    // Truck delivery is orthogonal.
    this.wireUpDeliveries(playState.available_trucks);

    // Expanding is orthogonal to other actions.
    this.wireUpExpansions(playState.extension_available);

    // These can be separate since they exclusively are on other players' boards.
    this.wireUpPurchases(playState.possible_purchases);

    // Animals in enclosures can only be exchanged, so these are also fine to just do.
    this.wireUpExchanges(playState.possible_exchanges);

    // It's moves and discards that are non-orthogonal,
    //   i.e. a tile in the barn can be discarded and possibly moved.
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

  private drawTile(lastround: boolean) {
    this.initStatusBar(_('Draw a tile? (cannot undo)'));
    this.markSelected(Elements.drawnTile(lastround));
    this.addConfirmAndRestartActionButtons('actDrawTile', {});
  }

  private wireUpDeliveries(available_trucks: number[]) {
    available_trucks.forEach(
      truck_id => this.addSelectableOnclick(
        Elements.truck(truck_id),
        () => this.bga.actions.performAction('actTakeTruck', { truck_id: truck_id }),
        _('Take truck'))
    );
  }

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
            await this.slideOutAndDestroy(Elements.enclosureTile(this.player_id, space)!, $(IDS.OFF_BOARD))
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
          this.view.updateMoneyDelta(discardMoneyDelta);
          await this.slideOutAndDestroy(Elements.enclosureTile(this.player_id, pm.src)!, $(IDS.OFF_BOARD))
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

  private swappedSpaces(exchanges: Exchanges, srcid: number, destid: number): HTMLElement[] {
    const elems: HTMLElement[] = [];
    exchanges.animal_positions[srcid].forEach(pos =>
      elems.push(Elements.enclosureSpace(this.player_id, toSpace(destid, Number(pos)))));
    exchanges.animal_positions[destid].forEach(pos =>
      elems.push(Elements.enclosureSpace(this.player_id, toSpace(srcid, Number(pos)))));
    return elems;
  }

  private wireUpExchangeDests(
      swappedSpaces: HTMLElement[],
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
          await this.animationManager.playParallel(anims)
            .then(() => swappedSpaces.forEach(t => this.markMoved(t)))
            .then(() => this.callUndoably("confirmExchange", async () => this.confirmExchange(srcid, destid, positions)));
        });
    });
  }

  private selectExchangeDest(exchanges: Exchanges, srcid: number) {
    this.initStatusBar(_("Select the animals to exchange with"));
    const srcAnimalSpaces = this.animalSpaces(exchanges, srcid);
    exchanges.enclosures[srcid].forEach(destid => {
      this.wireUpExchangeDests(this.swappedSpaces(exchanges, srcid, destid),
          srcid, srcAnimalSpaces, destid, this.animalSpaces(exchanges, destid))
    });
    // Now handle barn as destination, also dealing with possible offspring in the non-barn
    // Note that there is no enclosure completion bonus for exchanges, so we don't
    //  need to apply money deltas in here.
    const barnDests = exchanges.barn[srcid] ?? [];
    barnDests.forEach(be => {
      const destSpaces = be.positions.map(p => Elements.enclosureSpace(this.player_id, toSpace(0, p)));
      const swappedSpaces = [];
      for (let i = 0; i < be.positions.length; ++i) {
        swappedSpaces.push(Elements.enclosureSpace(this.player_id, toSpace(srcid, i + 1)));
        swappedSpaces.push(Elements.enclosureSpace(this.player_id, toSpace(0, be.positions[i])))
      }
      console.log(swappedSpaces);
      this.wireUpExchangeDests(swappedSpaces, srcid, srcAnimalSpaces, 0, destSpaces, be.positions);
    });
    this.addRestartAndUndoButtons();
  }

  private confirmExchange(srcid: number, destid: number, barnPos?: number[]) {
    this.initStatusBar(_("Confirm exchange"));
    this.addConfirmAndRestartActionButtons("actExchangeEnclosureAnimals", {
      src_enclosure_id: srcid,
      dest_enclosure_id: destid,
      dest_positions: JSON.stringify(barnPos),
    });
  }

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

}
