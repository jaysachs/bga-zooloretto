import { Html } from './html';
import { Tile, ZGamedatas, EnclosureSummary, Moneys, PlacedTile, Delivery } from './zgametypes';
import { BaseGame } from './basegame';
import { CSS, IDS, Elements, ZoolorettoHtml, Attrs } from './zhtml';
import { AnimationList } from './more-animations';
import { BgaScoreSheet, ScoreSheet } from './libs';
import { GameView } from './zview';
import { LoadDrawnTileFlow } from './loadDrawnTileFlow';
import { DeliverTilesFlow } from './deliverTilesFlow';
import { PlayerTurnFlow } from './playerTurnFlow';

/** Game class */
export class Game extends BaseGame<ZGamedatas> {

  private moneyCounter = new Map<number, Counter>();
  private primaryStockCounter: Counter;
  private endgameStockCounter: Counter;
  private scoreSheet: ScoreSheet;
  private readonly view: GameView;

  constructor(bga: Bga<ZGamedatas>) {
    super(bga, Game.special_log_args);
    this.view = new GameView(bga, this.animationManager, this.moneyCounter);
    this.bga.states.register('PlayerTurn', new PlayerTurnFlow(this.view));
    this.bga.states.register('LoadDrawnTile', new LoadDrawnTileFlow(this.view));
    this.bga.states.register('DeliverTruckTiles', new DeliverTilesFlow(this.view));
    if (window.location.host == "studio.boardgamearena.com") {
      (window as any).Zoo.game = this;
    }
  }

  private setupHtml(gamedatas: ZGamedatas): void {
    const zhtml = new ZoolorettoHtml(gamedatas, this.bga.gameui.player_id);
    this.bga.gameArea.getElement().appendChild(zhtml.baseStructure());
    for (const player of Object.values(gamedatas.players)) {
      this.bga.playerPanels.getElement(player.player_id).append(...zhtml.playerPanel(player));
      const counter = new ebg.counter();
      counter.create(IDS.money(player.player_id), { value: player.money });
      this.moneyCounter.set(player.player_id, counter);
    }
    this.primaryStockCounter = new ebg.counter();
    this.primaryStockCounter.create(IDS.PRIMARY_PILE_COUNT, { value: gamedatas.primary_pile_size == 1000 ? null : gamedatas.primary_pile_size });
    this.endgameStockCounter = new ebg.counter();
    this.endgameStockCounter.create(IDS.ENDGAME_PILE_COUNT, { value: gamedatas.endgame_pile_size });
    this.view.renderStock(gamedatas);
    this.view.renderTrucks(gamedatas);
    this.view.renderEnclosures(gamedatas);
    this.view.updateEnclosureSummaries(gamedatas.enclosure_summaries);
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

  setup(gamedatas: ZGamedatas) {
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
    await this.view.renderTileDraw(Elements.drawnTile(args.drawn_from_endgame_pile), args.tile);
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
        $(IDS.ENDGAME_PILE_TILES).insertAdjacentElement('afterbegin', this.view.makeTileBackSpan());
      }
    } else {
      if (args.primary_pile_size >= 5) {
        $(IDS.PRIMARY_PILE_TILES).insertAdjacentElement('afterbegin', this.view.makeTileBackSpan());
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
    this.view.renderExtensions(args.player_id, args.purchased_extensions);
    this.view.updateMoneys(args.moneys);
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
        ).then(() => this.view.addMoney(args.player_id, 1));
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
          anims.push(() => this.view.flashParents(offspring));
          anims.push(() => {
            const elem = this.view.makeTileSpan(offspring.placed_tile.tile);
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
    Elements.truck(args.truck_id).setAttribute(Attrs.MARK, 'selected');
    const coinElems = args.coin_positions.map(pos => Elements.truckTile(args.truck_id, pos)).filter(e => e);
    await this.animationManager.playParallel(coinElems.map(elem =>
      () => this.animationManager.slideOutAndDestroy(
              elem!, this.bga.playerPanels.getElement(args.player_id),{})))
      .then(() => this.view.updateMoneys(args.moneys))
  }

  private async notif_DeliveryCompleted(args: {
    player_id: number,
    truck_id: number,
    moneys: Moneys,
    enclosure_summaries: EnclosureSummary[],
  }) {
    const elem = Elements.truck(args.truck_id);
    await this.moreAnimations.slideAndAttach(elem, $(IDS.takenTruck(args.player_id)), { bump: 1, toPlaceholder: 'off' })
      .then(() => {
        Elements.truck(args.truck_id).removeAttribute(Attrs.MARK);
        this.view.updateMoneys(args.moneys);
        this.view.updateEnclosureSummaries(args.enclosure_summaries);
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
    this.view.updateMoneys(args.moneys);
    await this.moreAnimations.slideAndAttach(
      Elements.tile(args.tile)!,
      Elements.enclosureSpace(args.player_id, args.dest)
    )
      .then(() => this.view.updateEnclosureSummaries(args.enclosure_summaries))
  }

  private async notif_DiscardTile(args: {
    moneys: Moneys,
    tile: Tile,
    enclosure_summaries: EnclosureSummary[],
  }) {
    this.view.updateMoneys(args.moneys);
    await this.moreAnimations.slideOutAndDestroy(Elements.tile(args.tile), $(IDS.OFF_BOARD))
      .then(() => this.view.updateEnclosureSummaries(args.enclosure_summaries))
  }

  private async notif_PurchaseTile(args: {
			player_id: number,
      placed_tiles: PlacedTile[],
			moneys: Moneys,
      enclosure_summaries: EnclosureSummary[],
    }) {
    this.view.updateMoneys(args.moneys);
    await this.animationManager.playSequentially(
      args.placed_tiles.map(pt =>
        () => this.moreAnimations.slideAndAttach(Elements.tile(pt.tile)!, Elements.enclosureSpace(args.player_id, pt.space))
      )
    )
      .then(() => this.view.updateEnclosureSummaries(args.enclosure_summaries))
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
      anims.push(() => this.moreAnimations.slideAndAttach(Elements.truck(tid), $(IDS.depotSpace(tid)), { bump: 1, fromPlaceholder: 'off'}))
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
    this.view.updateMoneys(args.moneys);
    const anims: AnimationList = [];
    args.placed_tiles.forEach(pt =>  {
      const elem = Elements.tile(pt.tile);
      if (elem) {
        anims.push(() => this.moreAnimations.slideAndAttach(elem, Elements.enclosureSpace(args.player_id, pt.space)));
      } else {
        const elem = this.view.makeTileSpan(pt.tile);
        // FIXME: needed?
        elem.style.transform = 'rotate(0deg)';
        // a created offspring, create and slide it in
        anims.push(() => this.animationManager.slideIn(elem, $(IDS.OFF_BOARD), {}));
      }
    });
    await this.animationManager.playParallel(anims)
      .then(() => this.view.updateEnclosureSummaries(args.enclosure_summaries))
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

if (window.location.host == "studio.boardgamearena.com") {
  (window as any).Zoo = { Elements: Elements, IDS: IDS, CSS: CSS, Attrs: Attrs };
}
