Required for alpha
==================

* Decide on good highlight colors, and ensure they work with the background (whiteblock?) of the shared components area

* Improve "parent flash" animation; in particular, don't double-flash, and also the "moved" blue border is weird on just one tile when offspring are produced as a result of a purchase or move (or exchange).

Open
====

* Is accounting for --bga-game-zoom the right way to handle the mobile responsive issues?

* Use https://github.com/thoun/bga-jump-to/?tab=readme-ov-file to facilitate navigating to different player boards.

* When selecting things to do, if doing an "exchange", highlight ALL the source tiles.

* When selected an exchange destination, hover-highlight all the target tiles.

Deferred
========

* Handle multiple offspring (difficult but possible to arrange)

* Allow barn to have infinite size; shrink barn tiles when size goes above 9.

Maybe
=====
*. Add undo (and redo?)

* (?) add back player aid

* Update layout for mobile (trucks at top)

* Consider clicking on enclosures as a whole (for exchange, at least)

* Consider "initial selection of truck to deliver" also auto-selects the animal.

* When only one option, or all options the same, auto-do it?
  * e.g. drawn tile placed into one of 4 equally sized empty trucks
  * e.g. drawn tile with only one space to go

* "auto placement" of delivered tiles?

* Generate PHP/TS classes from user preferences?

Verification required
=====================
* all notifications are properly handled
* all notif messages are complete and correct
* Verify offspring generated at right times
  * in particular, the rare exchange-triggers-two-offspring?
* Verify offspring overflow into barn
* Verify completion bonuses generated at right times
* Mobile testing
* Ensure rendering is correct when offspring are produced. Send the offspring notification after the purchase/move/exchange ... so the log is correct.

* Ensure every enclosure completion is corretly computed in all cases. Send a separate notification for enclosure completion (so log is better/simpler).
