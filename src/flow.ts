type Continuation = {
  desc: string;
  thing: () => Promise<any>;
  mark: number;
}

class UndoStack {
    private ops: { description: string, anim: ((() => Promise<any>) | (() => any))}[] = [];
    private continuations: Continuation[] = [];
    private current: Continuation | undefined;
    private consumer: (AnimationList) => any;
    constructor(consumer: (AnimationList) => any) {
      this.consumer = consumer;
    }

    private async undoTo(mark: number) {
      let anims: AnimationList = [];
      while (this.ops.length > mark) {
        let da = this.ops.pop()!
        anims.push(da.anim);
      }
      // while (this.continuations.pop()?.mark! > this.ops.length) { }
      await this.consumer(anims);
    }

    push(description: string, anim: (() => Promise<any>) | (() => any)): void {
      this.ops.push({description: description, anim: anim});
    }

    undo() : (() => Promise<any>) | undefined {
      let x = this.continuations.at(-1);
      if (x) {
        console.log("found undo to ", x);
        // this.current = x;
        // FIXME: ?? this should remove things when it actually fires?
        return async () => { console.log("undoing", x); this.remove(x); this.undoTo(x.mark).then(() => x.thing()) };
      } else {
        console.error("Nowhere to undo to!");
        return undefined;
      }
    }

    private remove(x: {desc: string, thing: () => Promise<any>; mark: number}) {
      // FIXME: can change to allow "jump" undos.
      // FIXME: verify x is at the top
      this.current = this.continuations.pop();
      // let i = this.continuations.indexOf(x);
      // if (i > 0) {
      //   // this.continuations = this.continuations.slice(0, i);
      // }
    }

    async callUndoably(desc: string, thing: () => Promise<any>) {
      console.log("callUndably", desc, this.current);
      if (this.current !== undefined) {
        console.log("pushing", this.current);
        this.continuations.push(this.current);
        console.log("now have", this.continuations);
      } else {
        console.log("current undefined");
      }
      this.current = {desc: desc, mark: this.ops.length, thing: thing};
      console.log("setting", this.current);
      await thing();
    }

    async rollback()  {
      this.undoTo(0);
    }
}

abstract class PlayFlow<T, U extends Gamedatas = Gamedatas, G extends BaseGame<U> = BaseGame<U>> {
  protected readonly game: G;
  protected readonly player_id: number;
  private onClickAbortController : AbortController = new AbortController();
  protected undoStack: UndoStack;

  constructor(g : G, undoStack: UndoStack) {
    this.game = g;
    this.undoStack = undoStack;
    this.player_id = g.player_id;
  }

  protected pushUndoAnim(desc: string, anim: (() => Promise<any>) | (() => any)): void {
    this.undoStack.push(desc, anim );
  }

  start(args?: T) {
    let desc = "Start " + (this as any).constructor?.name;
    this.onClickAbortController = new AbortController();
    this.callUndoably(desc, () => this.doStart(args));
  }

  protected async callUndoably(desc: string, thing: () => Promise<any>) {
    this.undoStack.callUndoably(desc, thing);
  }

  protected abstract doStart(args?: T);

  protected async playParallel(anims: AnimationList) {
    await this.game.animationManager.playParallel(anims);
  }

  protected strElem(elem: HTMLElement | undefined): string {
    if (!elem) { return "undefined"; }
    if (elem.id) { return "#" + elem.id; }
    if (elem.parentElement?.id) { return "#" + elem.parentElement.id + ">" + elem.tagName; }
    if (elem.parentElement?.parentElement?.id) { return "#" + elem.parentElement.parentElement.id + ">" + elem.parentElement.tagName + ">" + elem.tagName; }
    return elem.tagName;
  }

  protected async slide(elem: HTMLElement, newParent: HTMLElement) {
    let currParent = elem.parentElement as HTMLElement;
    this.pushUndoAnim(`slide:${this.strElem(elem)}`, () => this.game.animationManager.slideAndAttach(elem, currParent, {}));
    await this.game.animationManager.slideAndAttach(elem, newParent, {})
      .then(() => this.markMoved(newParent));
  }

  protected async slideIn(elem: HTMLElement, newParent: HTMLElement) {
    this.pushUndoAnim(`sideIn:$${this.strElem(elem)}`, () => this.game.animationManager.slideOutAndDestroy(elem, $(IDS.OFF_BOARD), {}));
    newParent.appendChild(elem);
    await this.game.animationManager.slideIn(elem, $(IDS.OFF_BOARD), { });
  }

  protected async slideOutAndDestroy(elem: HTMLElement, toElem: HTMLElement) {
    let backup = elem.cloneNode() as HTMLElement;
    let parent = elem.parentElement as HTMLElement;
    this.pushUndoAnim(`slideOutAndDestroy:${this.strElem(elem)}`, async () => {
      parent.appendChild(backup);
      await this.game.animationManager.slideIn(backup, toElem, {});
    });
    await this.game.animationManager.slideOutAndDestroy(elem, toElem, {});
  }

  protected async rollback() {
    // this.clearMarked();
    this.inUndo = true;
    this.onClickAbortController.abort();
    this.onClickAbortController = new AbortController();
    await this.undoStack.rollback().then(() => {
      this.inUndo = false;
    });
  }

  protected initStatusBar(title: string, args?: any) {
    this.game.bga.statusBar.removeActionButtons();
    this.game.bga.statusBar.setTitle(title, args);
  }

  protected confirmationsEnabled(): boolean {
    // FIXME: process gamepreferences.json and create constants/accessors/etc
    return this.game.bga.userPreferences.get(100) > 0;
  }

  protected async addConfirmAndRestartActionButtons(bgaAction: string, args: any, autoclick? : boolean) {
    let doAct = async () => {
        // this.clearMarked();
        this.game.bga.statusBar.removeActionButtons();
        await this.game.bga.actions.performAction(bgaAction, args);
    };
    if (this.confirmationsEnabled())  {
      await doAct();
    } else {
      this.game.bga.statusBar.addActionButton(
        _('Confirm'), doAct, { autoclick: (autoclick === undefined) || autoclick }
      );
      this.addRestartAndUndoButtons();
    }
  }

  protected addRestartAndUndoButtons(): void {
    this.game.bga.statusBar.addActionButton(_('Restart turn'),
        async () => {
          await this.rollback().then(() => {
            this.game.restoreServerGameState();
          })
        },
      { color: "secondary"});
    let undoReturn = this.undoStack.undo();
    if (undoReturn) {
      this.game.bga.statusBar.addActionButton(_('Undo'),
        async () => { console.log("in undo"); this.clearOnclicks(); await undoReturn() },
        { color: "secondary"});
    }
  }

  private clearOnclicks(): void {
    this.onClickAbortController.abort();
    this.onClickAbortController = new AbortController();
    // console.log("clearOnclicksDone", this.undoStack);
  }

  private marked: HTMLElement[] = [];

  private markClass(elem: HTMLElement | undefined, classToAdd: string): void {
    if (!elem) {
      return;
    }
    this.marked.push(elem);
    let c = elem.className;
    elem.classList.add(classToAdd);
    this.pushUndoAnim(`markClass:${classToAdd}:${c} ${this.strElem(elem)}`, () => elem.className = c);
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
    // console.log("clearMarked", this);
    while (this.marked.length > 0) {
      let elem = this.marked.pop()!;
      if (!this.inUndo) {
        let c = elem.className;
        this.undoStack.push(`clearMarkedNotUndo:${this.strElem(elem)}:[${c}]`, () => elem.className = c);
      }
      elem.classList.remove(CSS.SELECTABLE, CSS.SELECTED, CSS.TARGETABLE, CSS.MOVED, CSS.PARENT);
    }
    // console.log("clearMarkedDone", this);
  }

  protected addSelectableOnclick(elem: HTMLElement, onclick: (evt: MouseEvent) => any ) {
    this.markSelectable(elem);
    elem.addEventListener(
      "click",
      async (ev: MouseEvent) => {
        console.log(`clicked on ${this.strElem(elem)}`);
        this.clearOnclicks();
        this.clearMarked();
        this.markSelected(elem);
        await onclick(ev);
      },
      { signal: this.onClickAbortController.signal });
  }

  protected getPlayerPanelElement(player_id: number): HTMLElement {
    return this.game.bga.playerPanels.getElement(player_id);
  }
}
