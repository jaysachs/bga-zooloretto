Open
====
1. Restart not working properly for delivery. The issue seems to be that the undo-s go back too far or something. Need to only "reset" when an actual state change happens (not a leave state / re-enter same state -- that shouldn't reset.) And depending, maybe even more general, should be explicit with undo boundaries.
2. Delivered trucks stay "blue" after being returned to play area from player board.
3. Put "possibles" in private args. (Very important in Babylonia.)

Deferred
========
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
