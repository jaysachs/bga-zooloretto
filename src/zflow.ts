import { PlayFlow } from "./flow";
import { Moneys, Offspring } from "./zgametypes";
import { Attrs, Elements, IDS } from "./zhtml";
import { GameView } from "./zview";

export abstract class ZooFlow<T = undefined> extends PlayFlow<T> {

  protected readonly view: GameView;
  constructor(view: GameView) {
    super(view.animationManager, view.bga);
    this.view = view;
  }

  protected override confirmationsEnabled(): boolean {
    // FIXME: process gamepreferences.json and create constants/accessors/etc
    return this.bga.userPreferences.get(100) > 0;
  }

  protected override useAutoclick(): boolean {
    return this.bga.userPreferences.get(101) > 0;
  }

  override offboard(): HTMLElement {
    return $(IDS.BOX);
  }

  private negate(moneyDelta: Moneys): Moneys {
    return Object.fromEntries(Object.entries(moneyDelta).map(kv => [kv[0], -kv[1]]));
  }

  protected updateMoneyDelta(moneyDelta?: Moneys): void {
    if (! moneyDelta) {
      return;
    }
    this.pushUndoOp('updateMoneyDelta', async () => this.view.updateMoneyDelta(this.negate(moneyDelta)));
    this.view.updateMoneyDelta(moneyDelta);
  }

  protected offspringSlide(offspring : Offspring | undefined): Promise<any> {
    if (offspring) {
      // if it's already on-screen, skip animation.
      if (!$(IDS.tile(offspring.placed_tile.tile))) {
        const offspringElem = this.view.tileSpan(offspring.placed_tile.tile);
        // FIXME: why needed?
        offspringElem.style.transform = 'rotate(0deg)';
        return this.view.flashParents(offspring)
          .then(() => this.slideIn(offspringElem, Elements.enclosureSpace(this.player_id, offspring.placed_tile.space)));
      }
    }
    return Promise.resolve();
  }

  protected mark(elem: HTMLElement | undefined, mark: "selected" | "selectable" | "none"): (() => Promise<any>) | undefined {
    const m = elem.getAttribute(Attrs.MARK);
    elem.setAttribute(Attrs.MARK, mark);
    return async () => elem.setAttribute(Attrs.MARK, m);
  }

}
