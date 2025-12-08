type AnimationList = (() => Promise<any>)[];

// @ts-ignore
GameGui = /** @class */ (function () {
  function GameGui() { }
  return GameGui;
})();

/** Class that extends default bga core game class with more functionality
 */

abstract class BaseGame<T extends Gamedatas> extends GameGui<T> {
  protected currentState: string | null;
  protected currentStateArgs: any;
  animationManager: AnimationManager;
  private pendingUpdate: boolean;
  private currentPlayerWasActive: boolean;
  private readonly clientStateNames: string[];

  constructor(clientStateNames: string[]) {
    super();
    console.log('game constructor');

    this.currentState = null;
    this.currentStateArgs = null;
    this.pendingUpdate = false;
    this.currentPlayerWasActive = false;
    this.clientStateNames = clientStateNames;
  }

  override setup(gamedatas: T) {
    this.gamedatas = gamedatas;
    console.log('Starting game setup', this);
    // create the animation manager, and bind it to the `game.bgaAnimationsActive()` function
    this.animationManager = new BgaAnimations.Manager({
      animationsActive: () => this.bgaAnimationsActive(),
    });
    this.autowireStateChangeMethods();
  }

  private autowireStateChangeMethods() {
    // TODO: also ensure all client states have some callback!
    //   maybe do for server states as well, or have an "ignore" list?
    console.log("Checking dynamic state change methods");
    const stateNames = Object.values(this.gamedatas.gamestates).filter((gs: Gamestate) => gs.type == 'activeplayer').map((gs) => gs.name);
    stateNames.push(... this.clientStateNames);
    const maybeMatch = new RegExp('^on[A-Z][A-Za-z0-9_]*_(' + stateNames.join('|') + ')$');
    let wiredUp: string[] = [];
    let wrong: string[] = [];
    let maybe: string[] = [];
    Object.keys(Object.getPrototypeOf(this)).forEach((meth) => {
        if (meth.startsWith('onEnteringState_')) {
          if (stateNames.indexOf(meth.substring(16)) < 0) {
            wrong.push(meth);
          } else {
            wiredUp.push(meth);
          }
        }
        else if (meth.startsWith('onUpdateActionButtons_')) {
          if (stateNames.indexOf(meth.substring(22)) < 0) {
            wrong.push(meth);
          } else {
            wiredUp.push(meth);
          }
        } else if (maybeMatch.test(meth)) {
          maybe.push(meth);
        }
      }
    );
    let noCallbacks : string[] = [];
    stateNames.forEach((name: string ) => {
      if (!this.onUpdateActionButtonsFn(name) && !this.onEnteringStateFn(name)) {
        noCallbacks.push(name);
      }
    });
    if (noCallbacks.length > 0) {
      console.error("Found states with no callbacks: ", noCallbacks.join(','));
    }
    if (wrong.length > 0) {
      throw new Error("Found state-change methods that do not correspond to a state: " + wrong);
    }
    if (maybe.length > 0) {
      console.warn("possible misnamed to-be-wired methods:", maybe);
    }
    console.log("Wired up state change methods", wiredUp);
  }

  override bgaPerformAction(action: string, args?: any, params?: { lock?: boolean; checkAction?: boolean; checkPossibleActions?: boolean; }): Promise<any> {
    console.debug("action", action, args);
    return (this as any).inherited(arguments).then(() => console.debug("action completed", action, args));
  }

  protected reenterCurrentState() {
    console.debug("reenterCurrentState", this.currentState, this.currentStateArgs);
    if (!this.currentState) {
      console.error("Cannot re-enter unknown state");
      return;
    }
    // FIXME: consider when onEnteringState_foo is defined.
    this.maybeCallFn(this.onUpdateActionButtonsFn(this.currentState), this.currentStateArgs);
  }

  override onEnteringState(stateName: string, args: any) {
    console.debug('onEnteringState: ' + stateName, args, this.debugStateInfo());
    this.currentState = stateName;
    // Call appropriate method
    args = args ? args.args : null; // this method has extra wrapper for args for some reason
    this.currentStateArgs = args;
    this.maybeCallFn(this.onEnteringStateFn(stateName), args);

    if (this.pendingUpdate) {
      this.onUpdateActionButtons(stateName, args);
      this.pendingUpdate = false;
    }
  }

  override setClientState(stateName: string, args: any) {
    // this.gamedatas.gamestate.args = args;
    if (this.clientStateNames.indexOf(stateName) < 0) {
      throw new Error(`No client state ${stateName}`);
    }
    if (stateName == this.currentState) {
      // (this as any).inherited(arguments); // super.setClientState(stateName, args);
      // this.onUpdateActionButtons(stateName, args);
      // return;
    }
    // this.onLeavingState(this.currentState!);
    (this as any).inherited(arguments); // super.setClientState(stateName, args);
    // this.onEnteringState(stateName, args);
    // this.onUpdateActionButtons(stateName, args);
  }

  override onLeavingState(stateName: string) {
    // console.debug('onLeavingState: ' + stateName, this.debugStateInfo());
    this.currentPlayerWasActive = false;
  }

  override onUpdateActionButtons(stateName: string, args: any) {
    if (this.currentState != stateName) {
      // delay firing this until onEnteringState is called so they always called in same order
      this.pendingUpdate = true;
      console.debug('   DELAYED onUpdateActionButtons');
      return;
    }
    this.pendingUpdate = false;
    if (gameui.isCurrentPlayerActive() && this.currentPlayerWasActive == false) {
      console.debug('onUpdateActionButtons: ' + stateName, args, this.debugStateInfo());
      this.currentPlayerWasActive = true;
      // Call appropriate method
      this.maybeCallFn(this.onUpdateActionButtonsFn(stateName), args);
    } else {
      this.currentPlayerWasActive = false;
    }
  }

  // utils
  debugStateInfo(): any {
    return "";
    // return {
    //   isCurrentPlayerActive: gameui.isCurrentPlayerActive(),
    //   instantaneousMode: gameui.instantaneousMode,
    //   replayMode: typeof g_replayFrom != 'undefined',
    // };
  }

  /**
   *
   * @param {string} methodName
   * @param {object} args
   * @returns
   */
  private maybeCallFn(fnName: string | undefined, args: any): any {
    if (fnName) {
      return (this as any)[fnName](args);
    }
    console.debug("no function", fnName);
    return undefined;
  }

  private onUpdateActionButtonsFn(stateName: string) : string | undefined {
    const anythis = this as any;
    const methodName = "onUpdateActionButtons_" + stateName;
    return (anythis[methodName]) ? methodName : undefined;
  }

  private onEnteringStateFn(stateName: string) : string | undefined {
    const anythis = this as any;
    const methodName = "onEnteringState_" + stateName;
    return (anythis[methodName]) ? methodName : undefined;
  }

  // Returns the index of the given element among its parent's child elements
  // Returns 0 if no parent.
  protected indexInParent(el: Element): number {
    const parentEl = el.parentElement;
    if (!parentEl) { return 0; }
    for (let i = 0; i < parentEl.childElementCount; ++i) {
      if (el == parentEl.children[i]) {
        return i;
      }
    }
    throw new Error("element not found among its parent's children: ${el}");
  }

  protected async notif_debug(args: any) {
    console.log("debug", args);
  }

}

class Html {
  public static makeElem(ty: string, args: {id?: string, text?: string, attrs?: Record<string, string>, classes?: (string | string[]), style?: (string | string[])}, ...children: (HTMLElement | undefined) []): HTMLElement  {
    let e = document.createElement(ty);
    if (args.id) { e.id = args.id; }
    if (args.classes) {
      if (typeof(args.classes) == "string") { args.classes = [args.classes]; }
      args.classes.forEach(c => { if (c) e.classList.add(c) });
    }
    if (args.style) {
      if (typeof(args.style) == "string") { args.style = [args.style]; }
      e.style = args.style.join(';');
    }
    if (args.text) {
      e.innerText = args.text;
    }
    if (args.attrs) {
      Object.keys(args.attrs).forEach(k => e.setAttribute(k, args.attrs![k]!));
    }
    children.forEach(c => c && e.appendChild(c));
    return e;
  }

  public static div(args: {id?: string, text?: string, classes?: (string | string[])}, ...children: (HTMLElement | undefined) []) {
    return this.makeElem('div', args, ...children);
  }

  public static span(args: {id?: string, text?: string, attrs?: Record<string, string>, classes?: (string | string[])}, ...children: (HTMLElement | undefined) []) {
    return this.makeElem('span', args, ...children);
  }

  public static p(args: {id?: string, text?: string, attrs?: Record<string, string>, style?: (string | string[]), classes?: (string | string[])}, ...children: (HTMLElement | undefined) []) {
    return this.makeElem('p', args, ...children);
  }
}
