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

 import { BgaAnimations, AnimationManager } from './libs';
import { MoreAnimations } from './more-animations';

/** Class that extends default bga core game class with more functionality
 */

type SpecialLogArgs = Record<string, (x: any) => HTMLElement>;

export abstract class BaseGame<P extends Player, T extends Gamedatas<P>> {
  public readonly animationManager: AnimationManager;
  public readonly moreAnimations: MoreAnimations;
  public readonly bga: Bga<P, T>;
  private readonly special_log_args = new Map<string, (x: any) => HTMLElement>();

  constructor(bga: Bga<P, T>) {
    this.bga = bga;
    this.animationManager = new BgaAnimations.Manager({
      animationsActive: () => this.bgaAnimationsActive(),
    });
    this.moreAnimations = new MoreAnimations(this.animationManager);
  }

  protected bgaAnimationsActive(): boolean {
    return this.bga.gameui.bgaAnimationsActive();
  }

  protected registerLogArg(arg: string, xform: (x: any) => HTMLElement): void {
    this.special_log_args.set(arg, xform);
  }

  bgaFormatText(log: string, args: any): { log: string, args: any } {
    try {
      const shadowParent = document.createElement('span');
      if (log && args && !args.processed) {
        args.processed = true;
        this.special_log_args.forEach((xform, key) => {
          if (key in args) {
            const e = xform(args);
            shadowParent.appendChild(e);
            args[key] = shadowParent.getHTML();
            e.remove();
          }
        });
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
}
