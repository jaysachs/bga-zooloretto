// import { Gamedatas } from './bga-framework';
// FIXME: this isn't right.
import { CSS } from './zhtml';
import { BgaAnimations, AnimationManager } from './libs';

export type Op = () => Promise<any>;

export interface NamedOp {
  desc: string;
  op: Op;
}

type Continuation = {
  op: NamedOp;
  mark: number;
}

export type OpList = Op[];

export class FlowState {
    // FIXME: this needs to be part of this, but it shouldn't be exposed.
    marked: HTMLElement[] = [];

    private ops: NamedOp[] = [];
    private continuations: Continuation[] = [];
    private onClickAbortController : AbortController = new AbortController();
    private current: Continuation | undefined;
    private consumer: (OpList) => any;
    constructor(consumer: (OpList) => any) {
      this.consumer = consumer;
    }

    private async undoTo(mark: number) {
      let anims: OpList = [];
      while (this.ops.length > mark) {
        anims.push(this.ops.pop()!.op);
      }
      await this.consumer(anims);
    }

    private remove(x: Continuation) {
      console.debug("flowState remove", x.op.desc);
      // FIXME: can change to allow "jump" undos
      this.current = this.continuations.pop();
      if (this.current !== x) {
        console.error("Undo remove expected top of ", x, "but found", this.current);
      }
    }

    pushOp(op : NamedOp): void {
      this.ops.push(op);
    }

    undo() : Op | undefined {
      console.debug("flowState undo");
      let x = this.continuations.at(-1);
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

    async callUndoably(desc: string, thing: Op) {
      console.debug("flowState callUndoably", desc);
      if (this.current !== undefined) {
        console.debug("flowState pushing", this.current);
        this.continuations.push(this.current);
      }
      this.current = {op: { desc: desc, op: thing }, mark: this.ops.length};
      console.debug("flowState new current: ", this.current);
      await thing();
    }

    clear() {
        console.debug("flowState clear");
        this.current = undefined;
        this.continuations = [];
        this.ops = [];
        this.marked = [];
        this.resetController();
    }

    async reset()  {
      await this.undoTo(0).then(this.clear.bind(this));
    }

    resetController() {
      console.debug("flowState resetController");
      this.onClickAbortController.abort();
      this.onClickAbortController = new AbortController();
    }

    abortSignal(): AbortSignal {
      return this.onClickAbortController.signal;
    }
}

export abstract class PlayFlow<T> {
  private static lastId: number = 0;
  protected id: number;
  protected readonly animationManager: AnimationManager;
  protected readonly bga: Bga;
  protected flowState: FlowState;
  protected player_id: number;

  constructor(animationManager: AnimationManager, bga: Bga, flowState: FlowState) {
    this.animationManager = animationManager;
    this.bga = bga;
    this.flowState = flowState;
    this.id = PlayFlow.lastId++;
  }

  protected useAutoclick(): boolean {
    return false;
  }

  protected pushUndoOp(desc: string, anim: Op): void {
    this.flowState.pushOp({ desc: desc, op: anim });
  }

  public onEnteringState(args: T, isCurrentPlayerActive: boolean) {
    console.log("onEnteringState", (this as any).constructor?.name, args, isCurrentPlayerActive);
    if (isCurrentPlayerActive) {
      this.flowState.clear();
      this.start(args);
    }
  }

  start(args: T) {
    this.player_id = this.bga.gameui.player_id;
    let desc = "Start " + (this as any).constructor?.name;
    // this.flowState.resetController();
    // this.callUndoably(desc, () => this.doStart(args));
    this.doStart(args);
  }

  protected async callUndoably(desc: string, thing: () => Promise<any>) {
    this.flowState.callUndoably(desc, thing);
  }

  protected abstract doStart(args?: T);

  protected async playParallel(anims: OpList) {
    await this.animationManager.playParallel(anims);
  }

  protected strElem(el: HTMLElement | undefined): string {
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

  protected abstract offboard(): HTMLElement | undefined;

  protected async slide(elem: HTMLElement, newParent: HTMLElement) {
    let currParent = elem.parentElement as HTMLElement;
    this.pushUndoOp(`slide:${this.strElem(elem)}`, () => this.animationManager.slideAndAttach(elem, currParent, {}));
    await this.animationManager.slideAndAttach(elem, newParent, {})
      .then(() => this.markMoved(newParent));
  }

  protected async slideIn(elem: HTMLElement, newParent: HTMLElement) {
    this.pushUndoOp(`sideIn:$${this.strElem(elem)}`, () => this.animationManager.slideOutAndDestroy(elem, this.offboard(), {}));
    newParent.appendChild(elem);
    await this.animationManager.slideIn(elem, this.offboard(), { });
  }

  protected async slideOutAndDestroy(elem: HTMLElement, toElem: HTMLElement) {
    let backup = elem.cloneNode() as HTMLElement;
    let parent = elem.parentElement as HTMLElement;
    this.pushUndoOp(`slideOutAndDestroy:${this.strElem(elem)}`, async () => {
      parent.appendChild(backup);
      await this.animationManager.slideIn(backup, toElem, {});
    });
    await this.animationManager.slideOutAndDestroy(elem, toElem, {});
  }

  protected async rollback() {
    // this.clearMarked();
    this.inUndo = true;
    this.flowState.resetController();
    await this.flowState.reset().then(() => {
      this.inUndo = false;
    });
  }

  protected initStatusBar(title: string, args?: any) {
    this.bga.statusBar.removeActionButtons();
    this.bga.statusBar.setTitle(title, args);
  }

  protected abstract confirmationsEnabled(): boolean;

  protected async addConfirmAndRestartActionButtons(bgaAction: string, args: any) {
    let doAct = async () => {
        this.clearMarked();
        await this.bga.actions.performAction(bgaAction, args);
    };
    if (!this.confirmationsEnabled())  {
      await doAct();
    } else {
      let confirm = this.bga.statusBar.addActionButton(
        _('Confirm'), doAct, { autoclick: this.useAutoclick() }
      );
      this.addRestartAndUndoButtons(confirm);
    }
  }

  protected addRestartAndUndoButtons(confirm?: HTMLButtonElement): void {
    this.bga.statusBar.addActionButton(_('Restart turn'),
        async () => {
          if (confirm) {
            confirm.disabled = true;
            confirm.remove();
          }
          await this.rollback().then(() => {
            this.bga.states.restoreServerGameState();
          })
        },
      { color: "secondary"});

    /*
    let undoReturn = this.flowState.undo();
    if (undoReturn) {
      this.bga.statusBar.addActionButton(_('Undo'),
        async () => {
          if (confirm) {
            confirm.disabled = true;
            confirm.remove();
          }
          this.clearOnclicks(); await undoReturn()
        },
        { color: "secondary"});
    }
    */
  }

  protected clearOnclicks(): void {
    this.flowState.resetController();
  }

  private markClass(elem: HTMLElement | undefined, classToAdd: string): void {
    if (!elem) {
      return;
    }
    this.flowState.marked.push(elem);
    let c = elem.className;
    elem.classList.add(classToAdd);
    this.pushUndoOp(`markClass:${classToAdd}:${c} ${this.strElem(elem)}`, async () => elem.className = c);
  }

  protected markSelected(elem: HTMLElement | undefined) {
    this.markClass(elem, CSS.SELECTED);
  }

  protected markMoved(elem: HTMLElement) {
    this.markClass(elem, CSS.MOVED);
  }

  protected markTargetable(elem: HTMLElement) {
    this.markClass(elem, CSS.TARGETABLE);
  }

  protected markSelectable(elem: HTMLElement | undefined) {
    this.markClass(elem, CSS.SELECTABLE);
  }

  private inUndo: boolean = false;

  private clearMarked() {
    while (this.flowState.marked.length > 0) {
      let elem = this.flowState.marked.pop()!;
      if (!this.inUndo) {
        let c = elem.className;
        this.flowState.pushOp({
          desc: `clearMarkedNotUndo:${this.strElem(elem)}:[${c}]`,
          op: async () => { elem.className = c }
        })
      }
      elem.title = '';
      elem.classList.remove(CSS.SELECTABLE, CSS.SELECTED, CSS.TARGETABLE, CSS.MOVED);
    }
  }

  protected addSelectableOnclick(elem: HTMLElement, onclick: (evt: MouseEvent) => any, desc?: string ) {
    this.markSelectable(elem);
    if (desc) {
      elem.title = desc;
    }
    elem.addEventListener(
      "click",
      async (ev: MouseEvent) => {
        console.debug(`clicked on ${this.strElem(elem)}`);
        this.clearOnclicks();
        this.clearMarked();
        this.markSelected(elem);
        await onclick(ev);
      },
      { signal: this.flowState.abortSignal() });
  }

  protected getPlayerPanelElement(player_id: number): HTMLElement {
    return this.bga.playerPanels.getElement(player_id);
  }
}
