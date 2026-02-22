Open
====

1. FIXED Restart not working properly for delivery. The issue seems to be that the undo-s go back too
   far or something. Need to only "reset" when an actual state change happens (not a leave state / re-enter same state -- that shouldn't reset.) And depending, maybe even more general, should be explicit with undo boundaries.

2. DONE Delivered trucks stay "blue" after being returned to play area from player board.
  * This is related to (1).
  * Consider marking truck "delivering" on StartDelivery notif, and clearing that on
    "DeliveryCompleted". Will need a "DeliveryCanceled" notif probably.

16. DONE When marking exchanged tiles, mark all the exchanged tiles; not empty spaces.

17.  Also, when selected an exchange destination, hover-highlight all the target tiles.

18. Ensure rendering is correct when offspring are produced. Send the offspring notification after the purchase/move/exchange ... so the log is correct.

19. Very enclosure completion is corretly computed in all cases. Send a separate notification for enclosure completion (so log is better/simpler).

20. NOT REPRO  When primary stock exhausted, UI is weird.

Deferred
========
3. DONE, actually put them in a private notif. Put "possibles" in private args. (Very important in Babylonia, however.)

4. Handle multiple offspring (difficult but possible to arrange)

5. Allow barn to have infinite size; shrink barn tiles when size goes above 9.

Maybe
=====
6. Add undo (and redo?)
  Only granular undo if all clientside. We're moving towards that.

8. (?) add back player aid

9. Update layout for mobile (trucks at top)

10. consider clicking on enclosures as a whole (for exchange, at least)

11. When only one option, or all options the same, auto-do it?
  * e.g. drawn tile placed into one of 4 equally sized empty trucks
  * e.g. drawn tile with only one space to go

12. "auto placement" of delivered tiles

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
