// import { Gamedatas } from './bga-framework';
// FIXME: this isn't right.
import { Attrs, CSS } from './zhtml';
import { AnimationManager } from './libs';

export type Op = () => Promise<any>;

export interface NamedOp {
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


export type OpList = Op[];

export class FlowState {
    // FIXME: this needs to be part of this, but it shouldn't be exposed.
    marked: HTMLElement[] = [];

    private ops: NamedOp[] = [];
    private continuations: Continuation[] = [];
    private onClickAbortController : AbortController = new AbortController();
    private current: Continuation | undefined;
    private readonly consumer: (OpList) => any;
    constructor(consumer: (OpList) => any) {
      this.consumer = consumer;
    }

    private async undoTo(mark: number) {
      const anims: OpList = [];
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
        this.clearMarked();
        this.resetController();
    }

    resetController() {
      console.debug("flowState resetController");
      this.onClickAbortController.abort();
      this.onClickAbortController = new AbortController();
    }

    abortSignal(): AbortSignal {
      return this.onClickAbortController.signal;
    }

  clearMarked() {
    console.debug("clearMarked");
    while (this.marked.length > 0) {
      const elem = this.marked.pop()!;
      if (!this.inUndo) {
        console.debug("clearMarked **AS UNDOABLE OP**", elem);
        const m = elem.getAttribute(Attrs.MARK);
        this.pushOp({
          desc: `clearMarkedNotUndo:${strElem(elem)}:[${m}]`,
          op: async () => elem.setAttribute(Attrs.MARK, m)
        })
      }
      console.debug("clearing marked", elem);
      elem.title = '';
      elem.removeAttribute(Attrs.MARK);
    }
  }

  private inUndo: boolean = false;
  async rollback() {
    console.debug("***");
    console.debug("flowstate rollback");
    // this.clearMarked();
    this.inUndo = true;
    this.resetController();
    await this.undoTo(0).then(this.clear.bind(this)).then(() => this.inUndo = false );
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

  public onLeavingState(args: T, isCurrentPlayerActive: boolean) {
    console.log("onLeavingState", (this as any).constructor?.name, args, isCurrentPlayerActive);
    if (isCurrentPlayerActive) {
      // this.flowState.clear();
    }
  }

  public onEnteringState(args: T, isCurrentPlayerActive: boolean) {
    console.log("onEnteringState", (this as any).constructor?.name, args, isCurrentPlayerActive);
    if (isCurrentPlayerActive) {
      // this.flowState.clear();
      this.start(args);
    }
  }

  start(args: T) {
    console.log("start:", (this as any).constructor?.name);
    this.player_id = this.bga.gameui.player_id;
    const desc = "Start " + (this as any).constructor?.name;
    this.callUndoably(desc, () => this.doStart(args));
  }

  protected async callUndoably(desc: string, thing: () => Promise<any>) {
    this.flowState.callUndoably(desc, thing);
  }

  protected abstract doStart(args?: T);

  protected async playParallel(anims: OpList) {
    await this.animationManager.playParallel(anims);
  }

  protected abstract offboard(): HTMLElement | undefined;

  protected async slide(elem: HTMLElement, newParent: HTMLElement) {
    const currParent = elem.parentElement as HTMLElement;
    this.pushUndoOp(`slide:${strElem(elem)}`, () => this.animationManager.slideAndAttach(elem, currParent, {}));
    await this.animationManager.slideAndAttach(elem, newParent, {})
      .then(() => this.markMoved(newParent));
  }

  protected async slideIn(elem: HTMLElement, newParent: HTMLElement) {
    this.pushUndoOp(`sideIn:$${strElem(elem)}`, () => this.animationManager.slideOutAndDestroy(elem, this.offboard(), {}));
    newParent.appendChild(elem);
    await this.animationManager.slideIn(elem, this.offboard(), { });
  }

  protected async slideOutAndDestroy(elem: HTMLElement, toElem: HTMLElement) {
    const backup = elem.cloneNode() as HTMLElement;
    const parent = elem.parentElement as HTMLElement;
    this.pushUndoOp(`slideOutAndDestroy:${strElem(elem)}`, async () => {
      parent.appendChild(backup);
      await this.animationManager.slideIn(backup, toElem, {});
    });
    await this.animationManager.slideOutAndDestroy(elem, toElem, {});
  }

  protected async rollback() {
    await this.flowState.rollback();
  }

  protected initStatusBar(title: string, args?: any) {
    this.bga.statusBar.removeActionButtons();
    this.bga.statusBar.setTitle(title, args);
  }

  protected abstract confirmationsEnabled(): boolean;

  protected async addConfirmAndRestartActionButtons(bgaAction: string, args: any, settings?: {restart?: () => Promise<any>, post?: () => Promise<any>}) {
    const doAct = async () => {
        this.flowState.clearMarked();
        await this.bga.actions.performAction(bgaAction, args).then(settings?.post);
    };
    if (!this.confirmationsEnabled())  {
      return await doAct();
    }
    const confirmButton = this.bga.statusBar.addActionButton(_('Confirm'), doAct, { autoclick: this.useAutoclick() });
    this.addRestartAndUndoButtons({ ...settings, confirm: confirmButton });
  }

  protected addRestartAndUndoButtons(settings?: { confirm?: HTMLButtonElement, restart?: () => Promise<any>} ): void {
    this.bga.statusBar.addActionButton(_('Restart turn'),
        async () => {
          if (settings?.confirm) {
            settings.confirm.disabled = true;
            settings.confirm.remove();
          }
          if (settings?.restart) {
            console.debug("using setting restart", settings.restart)
            await this.rollback().then(() => settings.restart());
          } else {
            console.debug("rollback then restoreServerGameState")
            await this.rollback().then(() => {
              this.bga.states.restoreServerGameState();
            })
          }
        },
      { color: "secondary"});

    /*
    const undoReturn = this.flowState.undo();
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

  private mark(elem: HTMLElement | undefined, mark: 'selected' | 'selectable' | 'moved' ): void {
    if (!elem) {
      return;
    }
    console.debug("mark", elem, mark);
    this.flowState.marked.push(elem);
    const m = elem.getAttribute(Attrs.MARK);
    elem.setAttribute(Attrs.MARK, mark);
    this.pushUndoOp(`mark:${mark}:${m} ${strElem(elem)}`, async () => elem.setAttribute(Attrs.MARK, m));
  }

  protected markSelected(elem: HTMLElement | undefined) {
    this.mark(elem, 'selected');
  }

  protected markMoved(elem: HTMLElement) {
    this.mark(elem, 'moved');
  }

  protected markSelectable(elem: HTMLElement | undefined) {
    this.mark(elem, 'selectable');
  }

  protected addSelectableOnclick(elem: HTMLElement, onclick: (evt: MouseEvent) => any, desc?: string ) {
    this.markSelectable(elem);
    if (desc) {
      elem.title = desc;
    }
    elem.addEventListener(
      "click",
      async (ev: MouseEvent) => {
        console.debug(`clicked on ${strElem(elem)}`);
        this.clearOnclicks();
        this.flowState.clearMarked();
        this.markSelected(elem);
        await onclick(ev);
      },
      { signal: this.flowState.abortSignal() });
  }

  protected getPlayerPanelElement(player_id: number): HTMLElement {
    return this.bga.playerPanels.getElement(player_id);
  }
}
