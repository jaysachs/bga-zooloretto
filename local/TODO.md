Open
====
1. Restart not working properly for delivery. The issue seems to be that the undo-s go back too far or something. Need to only "reset" when an actual state change happens (not a leave state / re-enter same state -- that shouldn't reset.) And depending, maybe even more general, should be explicit with undo boundaries.
2. Delivered trucks stay "blue" after being returned to play area from player board.
  * Hacked it in. But this is related to (1).
  * Consider marking truck "delivering" on StartDelivery notif, and clearing that on "DeliveryCompleted". Will need a "DeliveryCanceled" notif probably.
15. Consider a "pendingChanges" table, same structure as tiles. When reading stuff in, read them both and "overlay" the pending changes. On confirm delivery, apply the pending changes; on undo, just delete the rows. Then don't need undoSavepoint. (And utilize the "DeliveryCanceled" notif idea.)
    * Or, could just have new status "P" for "pending delivery" and "O" for "pending offspring". That's what gets updated on delivering a tile; and confirm delivery just changes "P" to "E" and "O" to "E". This is actually pretty simple.
      * That doesn't allow for granular undo, and isn't easily extended to that. To do that, we'd need the pending table with an additional "sequence" column (the value of which would be shared by both the offspring and the delivered tile rows.) Then, undo would be just read & delete the latest sequence number rows.
  * An even more radical approach would be to never update or delete a row (except for undos), but only add a new ones with a larger sequence IDs. To read, read in reverse sequence ID order, keeping track of which tileIDs we've seen and skip over already-seen ones. Each tile will eventually have 4+ entries (stock, drawn, truck, enclosure, plus one for each move/exchange/discard/purchase), so we're looking at ~500 rows by end of game.



Deferred
========
3. Put "possibles" in private args. (Very important in Babylonia, however.)
4. Handle multiple offspring (difficult but possible to arrange)
5. Allow barn to have infinite size; shrink barn tiles when size goes above 9.

Maybe
=====
6. Add undo (and redo?)
7. (?) If just one option, automatically do it, e.g.
  * draw: only one truck spot open, or all equivalent
  * deliver tile: if only one spot or all equivalent
8. (?) add back player aid
9. Update layout for mobile (trucks at top)
10. consider clicking on enclosures as a whole (for exchange, at least)
11. When only one option, or all options the same, auto-do it?
  * e.g. drawn tile placed into one of 4 equally sized empty trucks
  * e.g. drawn tile with only one space to go
12. "auto placement" of delivered tiles
13. Consider clicking on any part of an enclosure as "that's the destination"
14. Generate PHP/TS classes from user preferences?

Verification required
=====================
* all notifications are properly handled
* all notif messages are complete and correct
* Verify offspring generated at right times
  * in particular, the rare exchange-triggers-two-offspring?
* Verify offspring overflow into barn
* Verify completion bonuses generated at right times
* Mobile testing
