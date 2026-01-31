import { BgaAnimations } from './libs';

/** Class that extends default bga core game class with more functionality
 */

export abstract class BaseGame<T extends Gamedatas> {
  public readonly animationManager: InstanceType<typeof BgaAnimations.Manager>;
  public gamedatas: T;
  public readonly bga: Bga;

  constructor(bga: Bga<T>) {
    console.log('game constructor');
    this.bga = bga;
    this.animationManager = new BgaAnimations.Manager({
      animationsActive: () => this.bgaAnimationsActive(),
    });
  }

  protected bgaAnimationsActive(): boolean {
    return this.bga.gameui.bgaAnimationsActive();
  }

  setup(gamedatas: T) {
    console.log('Starting game setup', this);
    this.gamedatas = gamedatas;
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
