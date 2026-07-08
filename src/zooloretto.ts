/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * Zooloretto implementation : © Jay Sachs <vagabond@covariant.org>
 *
 * Copyright 2025 Jay Sachs <vagabond@covariant.org>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 */

import { Html } from './html';
import { Tile, ZGamedatas, ZPlayer } from './zgametypes';
import { BaseGame } from './basegame';
import { CSS, IDS, Elements, ZoolorettoHtml, Attrs } from './zhtml';
import { AnimationList } from './more-animations';
import { BgaScoreSheet, BgaZoom, ScoreSheet, ZoomManager } from './libs';
import { GameView } from './zview';
import { LoadDrawnTileFlow } from './loadDrawnTileFlow';
import { PlayerTurnFlow } from './playerTurnFlow';

/** Game class */
export class Game extends BaseGame<ZPlayer, ZGamedatas> {

  private moneyCounter = new Map<number, Counter>();
  private primaryStockCounter: Counter = new ebg.counter();
  private endgameStockCounter: Counter = new ebg.counter();
  private scoreSheet: ScoreSheet | undefined;
  private readonly view: GameView;
  private zoomManager: ZoomManager | undefined;

  constructor(bga: Bga<ZPlayer, ZGamedatas>) {
    super(bga);
    this.view = new GameView(bga, this.animationManager, this.moneyCounter, this.primaryStockCounter, this.endgameStockCounter);
    this.bga.userPreferences.onChange = this.handlePreferencesChange.bind(this);
    this.bga.states.register('PlayerTurn', new PlayerTurnFlow(this.view));
    this.bga.states.register('LoadDrawnTile', new LoadDrawnTileFlow(this.view));
    if (window.location.host == "studio.boardgamearena.com") {
      (window as any).Zoo.game = this;
    }
    this.registerLogArgs();
  }

  private handlePreferencesChange(pref_id: number, pref_value: number): void {
    switch (pref_id) {
      case 104:
        if (pref_value) {
          $(IDS.GAME).classList.add('zoo-indicators');
        } else {
          $(IDS.GAME).classList.remove('zoo-indicators');
        }
        break;
    }
  }

  private setupHtml(gamedatas: ZGamedatas): void {
    const zhtml = new ZoolorettoHtml(this.bga, gamedatas, this.bga.gameui.player_id);
    this.bga.gameArea.getElement().append(
      zhtml.baseStructure(),Html.div({id: IDS.BOX}),
      Html.div({id: IDS.SCORE_SHEET}));
    this.zoomManager = new BgaZoom.Manager({
            element: document.getElementById(IDS.GAME)!,
            localStorageZoomKey: 'zooloretto-zoom-' + Object.keys(gamedatas.players).length,
            zoomLevels: [0.25, 0.375, 0.5, 0.625, 0.75, 0.875, 1],
            onZoomChange: (zoom: number) => {
              const unzoom = 1 / zoom;
              document.getElementById(IDS.SHARED_CONTAINER)!.style.marginTop = (unzoom * 45) + 'px';
              document.querySelectorAll('.zoo-playername').forEach(
                e => (e as HTMLElement).style.zoom = String(unzoom)
              );
            }
        });
    try {
      if (this.zoomManager.zoom != 1) {
        const oldzoomTileSize = this.bga.userPreferences.get(104);
        switch (oldzoomTileSize) {
          case 78: break;
          case 66: this.zoomManager.setZoom(0.875); break;
          case 54: this.zoomManager.setZoom(0.75); break;
          case 42: this.zoomManager.setZoom(0.625); break;
          case 30: this.zoomManager.setZoom(0.5); break;
        }
      }
    } catch (e) {
      // ignore
    }

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
  }

  setup(gamedatas: ZGamedatas) {
    this.setupHtml(gamedatas);
    this.bga.userPreferences.onChange = this.handlePreferencesChange.bind(this);
    this.setupNotifications();
    this.setupScoreSheet(gamedatas);
    if (gamedatas.lastround) {
      this.view.showLastTurnBanner();
    }
    this.bga.gameui.onScreenWidthChange = () => this.handleScreenWidthChange();
  }

  handleScreenWidthChange() {
    const pageEl = document.getElementById('page-content');
    const pageRect = pageEl!.getBoundingClientRect();

    document.body.style.setProperty("--zoo-page-width",`${pageRect.width}px`);
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
        onScoreDisplayed: (property: string, playerId: number, score: string | number) => {
          if (property === 'total') {
            this.bga.playerPanels.getScoreCounter(playerId).setValue(Number(score));
          }
        },
      }
    );
    this.scoreControl(gamedatas.endScores);
  }

  private scoreControl(scores: any) {
    let visible: boolean = scores;
    if (visible) {
      $(IDS.GAME).onclick = (e) => { visible = !visible; this.scoreSheet!.setVisible(visible); }
    }

  }

  private async notif_ShowFinalScores(args: { endScores: any, }): Promise<void> {
    await this.scoreSheet!.setScores( args.endScores, { startBy: this.bga.gameui.player_id } );
    // TODO: should we hook this in before displaying them?
    this.scoreControl(args.endScores);
    await this.bga.gameui.wait(this.bga.userPreferences.get(102));
  }

  private registerLogArgs() {

    const tile = (t: string) =>
      this.registerLogArg(t, (args: any) => Html.span({attrs: Attrs.tile(args[t]), title: this.view.translatedTileDescription(args[t])}));
    tile('tile_type');
    tile('src_tile_type');
    tile('dest_tile_type');
    this.registerLogArg('coins',
      (args: any) => Html.span({text: ""+args.coins+" "}, Html.span({classes: 'zoo-money-label', title: _("coins")})));
  }
}

if (window.location.host == "studio.boardgamearena.com") {
  (window as any).Zoo = { Elements: Elements, IDS: IDS, CSS: CSS, Attrs: Attrs };
}
