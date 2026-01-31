// import { Gamedatas, GameGui } from 'bga-framework';
import { BgaAnimations } from './libs';
import { AnimationManager } from './more-animations';

/** Class that extends default bga core game class with more functionality
 */

export abstract class BaseGame<T extends Gamedatas> /* implements Bga<T> */ {
  animationManager: InstanceType<typeof BgaAnimations.Manager>;
  public gamedatas: T;
  public readonly bga: Bga;
//  public player_id: number;

  constructor(bga: Bga<T>) {
    console.log('game constructor');
    this.bga = bga;
  }

  protected bgaAnimationsActive(): boolean {
    return this.bga.gameui.bgaAnimationsActive();
  }

  setup(gamedatas: T) {
    this.gamedatas = gamedatas;
//    this.player_id = this.bga.gameui.player_id;
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
