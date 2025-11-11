==== Big things to focus on:

1. Rendering tiles. The responsive (to resize) layout is nifty, but the tile
images don't scale right. This is likely because there is a
compounding of size change based on nested containers having
height/width on %ages. Need to find a solution that works. Ideally,
all tiles in every container would be the same size (modulo the other
player boards at low-zoom).

2. Re-evaulate the current model, and establish some formal
   guidelines. Things like:
   1. All mutations go through the Model class, which basically offers
      a public method for each action (aside from getters).
   2. Pull out a PersistentStore to keep the DB reads/updates in one
   place and commonality. Define a ResultRow class to offer
   getString(), getInt() etc methods.
   3. Evaluate the current DB model.
   4. Look at the internal and DB representations of enclosures.
   5. Extend the use of "no null-ness" as much as possible.

3. The UI code is gnarly. Look for commonality. In babylonia, I had
   permanent onclick handlers that consulted state to determine
   whether to fire or not. This time, I'm trying to add (and remove)
   onclicks dynamically. Not sure that's better, though the onclick
   *could* remove itself.

4. Add unit tests.

5. For layout, having active playerboard take 50% (actually 46%) means
   easy zoom of other players to have no overlap. Ideally, the active
   board would be more than 50% of the screen width, and when another
   board "zooms", the active board "shrinks", i.e. they swap
   sizes. That probably requires javascript.

6. FIXED. BIG ISSUE: while responsivle layout works great with player
   panels on top, they fail totally when playerboards are on the
   right. Need to have two mockups, for each possiblity.  Switched to
   4.0vw size and this works now.



UI idea:
 state-based
 all elements that can be interacted with have a generic onclick handler
 that generic handler can have per-state "subhandlers" registered with them, and dispatch to the subhandler based on current state.

 So, for example, choosing truck + placing tiles into enclosures:
 1. all trucks, truck spaces and enclosure spaces have onclick handlers
 2. We have server state
    * PlaceTruckTiles, and
    client states
    * cChooseTruck:
      * on entry
        1. mark trucks "selectable"
        2. Buttons:
           * "Undo" => PlayerTurn
      * on truck click
        1. unmark trucks selectable
        2. "elevate" the selected truck
        3. => cChooseTruckTile
    * cChooseTruckTile
      * on entry:
        * if no placeable contents => cConfirmPlaceTruckTiles
          else mark truck contents "selectable"
        * Buttons:
          * "Undo" => server undo (which does =>PlayerTurn)
      * on truck tile click
        1. unmark truck contents
        2. mark selected tile "selected"
        3. => cTruckTileChosen
    * cTruckTileChosen
      * on entry:
        * mark available destinations "selectable"
        * Buttons:
          * "Undo" => server undo
      * on truck tile click
        1. unmark current available destinations
        2. mark available destinations "selectable"
      * on enclosure space click
        1. unmark current available destinations
        2. unmark selected truck tile
        3. execute action
        4. => cChooseTruckTile
    * cConfirmPlaceTruckTiles
      * on entry:
        * Buttons:
          * "Undo" => server undo
          * "Confirm" => server action

Some thoughts: want these to be "together" in the source code; don't
want it spread out.

Could define a "lifecycle" object:
```
   onEntry: () => { }
   onLeave: () => { }
```
and in `onEntry`, we register the onclicks (which could be functions on the object). `onLeave` we de-regiter them? could we automated that somehow?

class ClientStateDispatcher {
  private registry: (Event -> any)[];
  public register(h : Event -> any, state: string) {
    this.registry[state] = h;
  }
  public onclick(evt): any {
    let h = this.registry[currentState];
    if (h) { h(evt); }
  }
}

class ClientStateHandler {
  protected registerHandler(h : Event -> any, element: string | HTMLElement, state: string) {
    if (element.onclick == null) {
      // Don't need a new one for each element, just a new one for each kind.
      element.onclick = new ClientStateDispatcher();
    }
    (onWhat.onclick as ClientStateDispatcher).register(h, state);
  }
  public onEntry() { }
  public onLeave() { }
}

class cChooseTruckTileHandler extends ClientStateHandler {
  public cChooseTruckTileHandler(truck_id: number) {
    this.registerHandler(this.onClick, "all (placeable) tiles on the truck");
  }
  public override onEntry() {
    if (noPlaceableTiles) {
      this.changeClientState(cConfirmPlaceTruckTiles);
    } else {
      this.markAllContentsSelectable();
    }
    this.removeAllButtons();
    this.addUndoButtonForServerUndo();
  }

  protected onClick(evt) {
    this.unmarkAllContents();
    this.markSelectedTileSelected();
    this.changeClientState(cTruckTileChosen);
  }
}
