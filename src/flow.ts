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

import { AnimationManager } from './libs';
import { AnimationList } from './more-animations';

type Op = () => Promise<any>;

interface NamedOp {
  desc: string;
  op: Op;
}

type Continuation = {
  op: NamedOp;
  mark: number;
}

function strElem(el: HTMLElement | undefined): string {
  if (!el) { return "undefined"; }
  var elem : HTMLElement | null = el;
  var s = "";
  while (elem) {
    if (elem.id) { return "#" + elem.id + s; }
    s = ">" + elem.tagName + s;
    elem = elem.parentElement;
  }
  return s;
}


export abstract class PlayFlow<T> {
  private ops: NamedOp[] = [];
  private continuations: Continuation[] = [];
  private onClickAbortController : AbortController = new AbortController();
  private current: Continuation | undefined;
  private readonly consumer: (oplist: Op[]) => any;
  private inUndo: boolean = false;
  private readonly marked: HTMLElement[] = [];

  protected readonly animationManager: AnimationManager;
  protected readonly bga: Bga;
  protected player_id: number = 0;

  protected constructor(animationManager: AnimationManager, bga: Bga) {
    this.animationManager = animationManager;
    this.bga = bga;
    this.consumer = (x : AnimationList) => animationManager.playSequentially(x);
  }

  public onLeavingState(args: T, isCurrentPlayerActive: boolean) {
    if (isCurrentPlayerActive) {
      this.clear();
      this.clearMarked();
    }
  }

  public onEnteringState(args: T, isCurrentPlayerActive: boolean) {
    if (isCurrentPlayerActive) {
      // this.clear();
      this.player_id = this.bga.gameui.player_id;
      const desc = "Start " + (this as any).constructor?.name;
      this.callUndoably(desc, async () => this.start(args));
    }
  }

  protected abstract start(args?: T): void;

  protected abstract mark(elem: HTMLElement | undefined, mark: 'selected' | 'selectable' | 'none'): Op;

  protected abstract useAutoclick(): boolean;

  protected abstract confirmationsEnabled(): boolean;

  protected abstract offboard(): HTMLElement | undefined;

  protected pushUndoOp(desc: string, anim: Op): void {
    this.pushOp({ desc: desc, op: anim });
  }

  // FIXME: consider whether this should await. Probably not ...
  protected async callUndoably(desc: string, thing: Op) {
    if (this.current !== undefined) {
      this.continuations.push(this.current);
    }
    this.current = {op: { desc: desc, op: thing }, mark: this.ops.length};
    await thing();
  }

  protected slide(elem: HTMLElement, newParent: HTMLElement) : Promise<any> {
    const currParent = elem.parentElement as HTMLElement;
    this.pushUndoOp(`slide:${strElem(elem)}`, () => this.animationManager.slideAndAttach(elem, currParent, {}));
    return this.animationManager.slideAndAttach(elem, newParent, {})
  }

  protected slideIn(elem: HTMLElement, newParent: HTMLElement): Promise<any> {
    this.pushUndoOp(`sideIn:$${strElem(elem)}`, () => this.animationManager.slideOutAndDestroy(elem, this.offboard(), {}));
    newParent.appendChild(elem);
    return this.animationManager.slideIn(elem, this.offboard(), { });
  }

  protected slideOutAndDestroy(elem: HTMLElement, toElem: HTMLElement): Promise<void> {
    const backup = elem.cloneNode() as HTMLElement;
    const parent = elem.parentElement as HTMLElement;
    this.pushUndoOp(`slideOutAndDestroy:${strElem(elem)}`, async () => {
      parent.appendChild(backup);
      // FIXME: this await is probably not needed
      await this.animationManager.slideIn(backup, toElem, {});
    });
    return this.animationManager.slideOutAndDestroy(elem, toElem, {});
  }

  protected initStatusBar(title: string, args?: any) {
    this.bga.statusBar.removeActionButtons();
    this.bga.statusBar.setTitle(title, args);
  }

  protected async addConfirmAndRestartActionButtons(bgaAction: string, args: any, settings?: {restart?: () => Promise<any>, post?: () => Promise<any>}) {
    const doAct = async () => {
        this.clearMarked();
        await this.bga.actions.performAction(bgaAction, args)
        if (settings?.post) {
          await settings.post();
        }
    };
    if (!this.confirmationsEnabled())  {
      return this.bga.gameui.wait(100).then(() => doAct());
    }
    const confirmButton = this.bga.statusBar.addActionButton(_('Confirm'), doAct, { autoclick: this.useAutoclick() });
    this.addRestartAndUndoButtons({ ...settings, confirm: confirmButton });
  }

  protected addRestartAndUndoButtons(settings?: { confirm?: HTMLButtonElement } ): void {
    this.bga.statusBar.addActionButton(_('Restart turn'),
        async () => {
          if (settings?.confirm) {
            settings.confirm.disabled = true;
            settings.confirm.remove();
          }
          await this.rollback();
          this.bga.states.restoreServerGameState();
        },
      { color: "secondary"});

    /*
    const undoReturn = this.undo();
    if (undoReturn) {
      this.bga.statusBar.addActionButton(_('Undo'),
        async () => {
          if (confirm) {
            confirm.disabled = true;
            confirm.remove();
          }
          this.resetController(); await undoReturn()
        },
        { color: "secondary"});
    }
    */
  }

  // FIXME: shouldn't need to expose this?
  //   but need to allow butons to cancel onclicks.
  protected clearOnclicks(): void {
    this.resetController();
  }

  protected markSelected(elem: HTMLElement | undefined) {
    this.setMarked(elem, 'selected');
  }

  protected markSelectable(elem: HTMLElement | undefined) {
    this.setMarked(elem, 'selectable');
  }

  protected addSelectableOnclick(elem: HTMLElement, onclick: (evt: MouseEvent) => any, desc?: string ) {
    this.markSelectable(elem);
    if (desc) {
      elem.title = desc;
    }
    elem.addEventListener(
      "click",
      async (ev: MouseEvent) => {
        this.resetController();
        this.clearMarked();
        this.markSelected(elem);
        // FIXME: is this await needed?
        await onclick(ev);
      },
      { signal: this.abortSignal() });
  }

  private clear() {
    this.current = undefined;
    this.continuations = [];
    this.ops = [];
    this.resetController();
  }

  private undoTo(mark: number): Promise<any> {
    const anims: Op[] = [];
    while (this.ops.length > mark) {
      anims.push(this.ops.pop()!.op);
    }
    return this.consumer(anims);
  }

  private resetController() {
    this.onClickAbortController.abort();
    this.onClickAbortController = new AbortController();
  }

  private abortSignal(): AbortSignal {
    return this.onClickAbortController.signal;
  }

  private pushOp(op : NamedOp): void {
    this.ops.push(op);
  }

  /*
  private remove(x: Continuation) {
    console.debug("flowState remove", x.op.desc);
    // FIXME: can change to allow "jump" undos
    this.current = this.continuations.pop();
    if (this.current !== x) {
      console.error("Undo remove expected top of ", x, "but found", this.current);
    }
  }

  undo() : Op | undefined {
    console.debug("flowState undo");
    const x = this.continuations.at(-1);
    if (x) {
      console.debug("   flowState undo ", x.op.desc);
      // this.current = x;
      // FIXME: ?? this should remove things when it actually fires?
      return async () => { this.remove(x); await this.undoTo(x.mark).then(() => x.op.op()) };
    } else {
      console.error("Nowhere to undo to!");
      return undefined;
    }
  }
  */

  private setMarked(elem: HTMLElement | undefined, mark: 'selected' | 'selectable' ): void {
    if (!elem) {
      return;
    }
    this.marked.push(elem);
    this.pushUndoOp(`unmark:${mark} ${strElem(elem)}`, this.mark(elem, mark));
  }

  private async rollback() {
    this.inUndo = true;
    this.resetController();
    await this.undoTo(0);
    this.clearMarked();
    this.clear();
    this.inUndo = false;
  }

  protected clearMarked() {
    while (this.marked.length > 0) {
      const elem = this.marked.pop()!;
      const undoMark = this.mark(elem, 'none');
      if (!this.inUndo) {
        this.pushOp({
          desc: `clearMarkedNotUndo:${strElem(elem)}`,
          op: undoMark,
        })
      }
      elem.title = '';
    }
  }

}
