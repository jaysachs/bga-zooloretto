type AnimationList = (() => Promise<any>)[];

// @ts-ignore
GameGui = /** @class */ (function () {
  function GameGui() { }
  return GameGui;
})();

/** Class that extends default bga core game class with more functionality
 */

abstract class BaseGame<T extends Gamedatas> extends GameGui<T> /* implements Bga<T> */ {
  animationManager: AnimationManager;

  constructor(bga: Bga<T>) {
    super(/* bga */);
    Object.assign(this, bga);
    console.log('game constructor');
  }

  override setup(gamedatas: T) {
    this.gamedatas = gamedatas;
    console.log('Starting game setup', this);
    // create the animation manager, and bind it to the `game.bgaAnimationsActive()` function
    this.animationManager = new BgaAnimations.Manager({
      animationsActive: () => this.bgaAnimationsActive(),
    });
  }

  /**
  * Returns the index of the given element among its parent's child elements or -1 if no parent.
  */
  protected indexInParent(el: Element): number {
    return Array.from(el.parentElement?.children ?? []).findIndex(e => e == el);
  }

  protected async notif_debug(args: any) {
    console.log("debug", args);
  }

}
