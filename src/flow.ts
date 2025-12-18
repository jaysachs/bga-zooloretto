abstract class PlayFlow<T, U extends Gamedatas = Gamedatas, G extends BaseGame<U> = BaseGame<U>> {
  protected readonly game: G;
  protected readonly player_id: number;
  private onClickAbortController : AbortController = new AbortController();
  private undos: AnimationList = [];

  constructor(g : G) {
    this.game = g;
    this.player_id = g.player_id;
  }

  protected pushUndoAnim(anim: (() => Promise<any>) | (() => any)): void {
    this.undos.push(anim);
  }

  start(args?: T) {
    console.debug("Starting", this, args);
    this.undos = [];
    this.onClickAbortController = new AbortController();
    this.doStart(args);
  }

  protected abstract doStart(args?: T);

  protected playParallel(anims: AnimationList): Promise<any> {
    return this.game.animationManager.playParallel(anims);
  }

  protected slide(elem: HTMLElement, newParent: HTMLElement): Promise<any> {
    let currParent = elem.parentElement as HTMLElement;
    this.pushUndoAnim(() => this.game.animationManager.slideAndAttach(elem, currParent, {}));
    return this.game.animationManager.slideAndAttach(elem, newParent, {})
      .then(() => this.markMoved(newParent));
  }

  protected slideIn(elem: HTMLElement, newParent: HTMLElement): Promise<any> {
    this.pushUndoAnim(() => this.game.animationManager.slideOutAndDestroy(elem, $(IDS.OFF_BOARD), {}));
    newParent.appendChild(elem);
    return this.game.animationManager.slideIn(elem, $(IDS.OFF_BOARD), { });
  }

  protected slideOutAndDestroy(elem: HTMLElement, toElem: HTMLElement): Promise<any> {
    let backup = elem.cloneNode() as HTMLElement;
    let parent = elem.parentElement as HTMLElement;
    this.pushUndoAnim(() => {
      parent.appendChild(backup);
      this.game.animationManager.slideIn(backup, toElem, {});
    });
    return this.game.animationManager.slideOutAndDestroy(elem, toElem, {});
  }

  protected async rollback(): Promise<any> {
    let anims: AnimationList = [];
    while (this.undos.length > 0) {
      anims.push(this.undos.pop()!);
    }
    this.clearMarked();
    await this.game.animationManager.playParallel(anims);
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
        this.clearMarked();
        this.game.bga.statusBar.removeActionButtons();
        this.game.bga.actions.performAction(bgaAction, args);
    };
    if (this.confirmationsEnabled())  {
      await doAct();
    } else {
      this.game.bga.statusBar.addActionButton(
        _('Confirm'), doAct, { autoclick: (autoclick === undefined) || autoclick }
      );
      this.addRestartTurnButton();
    }
  }

  protected addRestartTurnButton(onCancel?: CallableFunction): void {
    this.game.bga.statusBar.addActionButton(_('Restart turn'),
        () => {
          this.rollback().then(() => {
            this.game.bga.statusBar.removeActionButtons();
            onCancel && onCancel();
            this.game.restoreServerGameState();
          })
        },
      { color: "secondary"});
  }

  private clearOnclicks(): void {
    this.onClickAbortController.abort();
    this.onClickAbortController = new AbortController();
    this.clearMarked();
  }

  private marked: HTMLElement[] = [];

  protected markSelected(elem?: HTMLElement) {
    if (!elem) {
      return;
    }
    this.marked.push(elem);
    elem.classList.add(CSS.SELECTED);
  }

  protected markMoved(elem: HTMLElement) {
    this.marked.push(elem);
    elem.classList.add(CSS.MOVED);
  }

  protected markTargetable(elem: HTMLElement) {
    this.marked.push(elem);
    elem.classList.add(CSS.TARGETABLE);
  }

  protected markSelectable(elem?: HTMLElement) {
    if (!elem) {
      return;
    }
    console.log("marking selectable", elem);
    this.marked.push(elem);
    elem.classList.add(CSS.SELECTABLE);
  }

  protected clearMarked() {
    // FIXME: should this just clear them everywhere?
    this.marked.forEach(e => e.classList.remove(CSS.SELECTABLE, CSS.SELECTED, CSS.TARGETABLE, CSS.MOVED, CSS.PARENT));
  }

  protected addSelectableOnclick(elem: HTMLElement, onclick: (evt: MouseEvent) => any ) {
    this.markSelectable(elem);
    elem.addEventListener(
      "click",
      async (ev: MouseEvent) => { this.clearOnclicks(); this.markSelected(elem); await onclick(ev); },
      { signal: this.onClickAbortController.signal });
  }

  protected getPlayerPanelElement(player_id: number): HTMLElement {
    return this.game.bga.playerPanels.getElement(player_id);
  }
}
