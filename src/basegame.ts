import { BgaAnimations, AnimationManager } from './libs';
import { MoreAnimations } from './more-animations';

/** Class that extends default bga core game class with more functionality
 */

type SpecialLogArgs = Record<string, (any) => HTMLElement>;

export abstract class BaseGame<T extends Gamedatas> {
  public readonly animationManager: AnimationManager;
  public readonly moreAnimations: MoreAnimations;
  public readonly bga: Bga<T>;
  private readonly special_log_args: SpecialLogArgs;

  constructor(bga: Bga<T>, special_log_args: SpecialLogArgs) {
    console.log('game constructor');
    this.bga = bga;
    this.special_log_args = special_log_args;
    this.animationManager = new BgaAnimations.Manager({
      animationsActive: () => this.bgaAnimationsActive(),
    });
    this.moreAnimations = new MoreAnimations(this.animationManager);
  }

  protected bgaAnimationsActive(): boolean {
    return this.bga.gameui.bgaAnimationsActive();
  }

  bgaFormatText(log: string, args: any): { log: string, args: any } {
    try {
      let shadowParent = document.createElement('span');
      if (log && args && !args.processed) {
        args.processed = true;
        for (const key in this.special_log_args) {
          if (key in args) {
            let e = this.special_log_args[key](args);
            shadowParent.appendChild(e);
            args[key] = shadowParent.getHTML();
            e.remove();
          }
        }
      }
    } catch (e: any) {
      console.error(log, args, 'Exception thrown', e.stack);
    }
    return { log, args };
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
