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
