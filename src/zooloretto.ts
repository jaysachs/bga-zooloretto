import { Html } from './html';
import { Tile, ZGamedatas, ZPlayer } from './zgametypes';
import { BaseGame } from './basegame';
import { CSS, IDS, Elements, ZoolorettoHtml, Attrs } from './zhtml';
import { AnimationList } from './more-animations';
import { BgaScoreSheet, ScoreSheet } from './libs';
import { GameView } from './zview';
import { LoadDrawnTileFlow } from './loadDrawnTileFlow';
import { PlayerTurnFlow } from './playerTurnFlow';

/** Game class */
export class Game extends BaseGame<ZPlayer, ZGamedatas> {

  private moneyCounter = new Map<number, Counter>();
  private primaryStockCounter: Counter = new ebg.counter();
  private endgameStockCounter: Counter = new ebg.counter();
  private scoreSheet: ScoreSheet;
  private readonly view: GameView;

  constructor(bga: Bga<ZPlayer, ZGamedatas>) {
    super(bga, Game.special_log_args);
    this.view = new GameView(bga, this.animationManager, this.moneyCounter, this.primaryStockCounter, this.endgameStockCounter);
    this.bga.states.register('PlayerTurn', new PlayerTurnFlow(this.view));
    this.bga.states.register('LoadDrawnTile', new LoadDrawnTileFlow(this.view));
    if (window.location.host == "studio.boardgamearena.com") {
      (window as any).Zoo.game = this;
    }
  }

  private setupHtml(gamedatas: ZGamedatas): void {
    const zhtml = new ZoolorettoHtml(gamedatas, this.bga.gameui.player_id);
    this.bga.gameArea.getElement().append(zhtml.baseStructure(),Html.div({id: IDS.BOX}));
    for (const player of Object.values(gamedatas.players)) {
      this.bga.playerPanels.getElement(player.player_id).append(...zhtml.playerPanel(player));
      const counter = new ebg.counter();
      counter.create(IDS.money(player.player_id), { value: player.money });
      this.moneyCounter.set(player.player_id, counter);
    }
    this.primaryStockCounter.create(IDS.PRIMARY_PILE_COUNT, { value: gamedatas.primary_pile_size == 1000 ? null : gamedatas.primary_pile_size });
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
      this.view.showLastTurnBanner();
    }

    console.log('Game setup done');
  }

  private setupNotifications(): void {
    this.bga.notifications.setupPromiseNotifications({ handlers:[this, ...this.bga.states.getStateClasses()], logger: console.log });
  }

  private async notif_EndRound(args: {
    truck_ids_returned: number[],
    dumped_tiles: Tile[],
    last_round: boolean }
  ) {

    const anims: AnimationList = [];
    args.dumped_tiles.forEach(tile =>
      anims.push(() => this.moreAnimations.slideOutAndDestroy(Elements.tile(tile), $(IDS.BOX)))
    );
    args.truck_ids_returned.forEach(tid =>
      anims.push(() => this.moreAnimations.slideAndAttach(Elements.truck(tid), $(IDS.depotSpace(tid)),
      { bump: 1, toPlaceholder: 'grow', fromPlaceholder: 'off'}))
    );

    await this.animationManager.playSequentially(anims);
    this.bga.gameui.enableAllPlayerPanels();
    if (args.last_round) {
      this.view.showLastTurnBanner();
    }
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
        // FIXME: this isn't required, but works around a minor bug
        //   on the final situation page where a reload erases the scores.
        onScoreDisplayed: (property: string, playerId: number, score: number) => {
          if (property === 'total') {
            this.bga.playerPanels.getScoreCounter(playerId).setValue(score);
          }
        },
      }
    );
    this.scoreControl(gamedatas.endScores);
  }

  private scoreControl(scores: any) {
    let visible: boolean = scores;
    if (visible) {
      $(IDS.GAME).onclick = (e) => { visible = !visible; this.scoreSheet.setVisible(visible); }
    }

  }

  private async notif_ShowFinalScores(args: { endScores: any, }): Promise<void> {
    await this.scoreSheet.setScores( args.endScores, { startBy: this.bga.gameui.player_id } );
    // TODO: should we hook this in before displaying them?
    this.scoreControl(args.endScores);
    return this.bga.gameui.wait(this.bga.userPreferences.get(102));
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
