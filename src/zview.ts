import { Html } from './html';
import { Tile, ZGamedatas, EnclosureSummary, Offspring, Moneys, ZPlayer } from './zgametypes';
import { CSS, IDS, Elements, Attrs } from './zhtml';
import { MoreAnimations } from './more-animations';
import { AnimationManager } from './libs';

export class GameView {
  private readonly moneyCounter: Map<number, Counter>;
  private readonly primaryStockCounter: Counter;
  private readonly endgameStockCounter: Counter;

  // FIXME: try to find a way to make this private.
  //   Consider creating animations here for what these are used for
  readonly moreAnimations: MoreAnimations;
  readonly animationManager: AnimationManager;
  readonly bga: Bga<ZPlayer,ZGamedatas>;

  constructor(bga: Bga<ZPlayer,ZGamedatas>,
              animationManager: AnimationManager,
              moneyCounter: Map<number, Counter>,
              primaryStockCounter: Counter,
              endgameStockCounter: Counter) {
    this.bga = bga;
    this.moreAnimations = new MoreAnimations(animationManager);
    this.primaryStockCounter = primaryStockCounter;
    this.endgameStockCounter = endgameStockCounter;
    this.animationManager = animationManager;
    this.moneyCounter = moneyCounter;
  }

  tileSpan(tile: Tile): HTMLElement {
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

  public makeTileBackSpan(): HTMLElement {
    return Html.span({ attrs: Attrs.tile('back') });
  }

  flashParents(offspring: Offspring) : Promise<any> {
    return this.moreAnimations.flash(CSS.FLASH, [Elements.tile(offspring.mother), Elements.tile(offspring.father)]);
  }

  public updateMoneyDelta(delta: Moneys): void {
    Object.entries(delta).forEach(pv => this.addMoney(Number(pv[0]), pv[1]));
  }

  // FIXME: consider making async to permit animations
  public updateMoneys(moneys: Moneys): void {
    Object.entries(moneys).forEach(pv => this.moneyCounter.get(Number(pv[0])).toValue(pv[1]));
  }

  public addMoney(player_id: number, delta: number): void {
    this.moneyCounter.get(player_id).incValue(delta);
  }

  // FIXME: consider making this async to allow for animation
  public renderExtensions(player_id : number, extensions: number): void {
    const elem = $(IDS.boardId(player_id));
    elem.setAttribute(Attrs.EXTENSIONS, String(extensions));
  }

  getCurrentExtensions(player_id : number): number {
    const elem = $(IDS.boardId(player_id));
    return Number(elem.getAttribute(Attrs.EXTENSIONS) || 0);
  }

  async renderTileDraw(elem: HTMLElement, tile: Tile): Promise<any> {
    const setTile = () => {
      elem.id = IDS.tile(tile);
      elem.setAttribute(Attrs.TILE, tile.type);
    };

    // if (!this.bgaAnimationsActive()) {
    //   setTile();
    //   return Promise.resolve(null);
    // }

    // Create the front and back of the tile to flip
    const back = this.makeTileBackSpan();
    const front = this.tileSpan(tile);

    // "hide" the original tile
    elem.removeAttribute(Attrs.TILE);
    // Need them in the document
    elem.appendChild(front);
    elem.appendChild(back);

    await this.moreAnimations.flip(front, back);
    setTile();
    back.remove();
    front.remove();
  }

  renderStock(gamedatas: ZGamedatas) : void {
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

  renderTrucks(gamedatas: ZGamedatas): void {
    for (const truck of gamedatas.trucks) {
      truck.contents.forEach(contents => {
        if (contents.tile) {
          Elements.truckSpace(truck.truck_id, contents.pos).append(this.tileSpan(contents.tile));
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

  renderEnclosures(gamedatas: ZGamedatas): void {
    for (const player_id in gamedatas.enclosures) {
      gamedatas.enclosures[player_id]!.forEach(es => {
        if (es.tile) {
          Elements.enclosureSpace(Number(player_id), es.space).append(this.tileSpan(es.tile));
        }
      })
    }
  }

  updateEnclosureSummaries(summaries: EnclosureSummary[]) {
    summaries.forEach(summary => {
      const elem = $(IDS.playerPanelBoardSummary(summary.player_id, summary.enclosure_id));
      elem.setAttribute(Attrs.TILE, summary.tile_type);
      if (summary.tile_type) {
        elem.title = this.translatedTileDescription(summary.tile_type);
        elem.firstElementChild!.textContent = `${summary.count}`;
      } else {
        elem.title = '';
        elem.firstElementChild!.textContent = '';
      }
    });
  }

  translatedTileDescription(tile_type: string): string {
    return _(this.bga.gameui.gamedatas.tile_translations[tile_type]);
  }

  showLastTurnBanner() {
    this.bga.gameArea.addLastTurnBanner(_('This is the last round!'));
  }

  updateStockCounters(primary: number, endgame: number) {
    if (primary != 1000) {
      this.primaryStockCounter.toValue(primary);
    }
    this.endgameStockCounter.toValue(endgame);

  }

}
