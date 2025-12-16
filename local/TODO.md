* Final score sheet rendering

* Handle rare exchange-triggers-two-offspring-in-one-enclosure case.

* Do we need to hide tile counts, really? if players put them in piles of 10, easy to count.
  * if we do, can we still give e.g. approx percentage left (like T&E)?

* Log messages for all significant events (either separate messages for moves/money/offspring, or compound ones).
  * It's a question: separate notifs for
    * completing enclosure
    * offspring produced
  * Need to rework model for server objects to properly do this for take truck placements

* Rework model objects

* Use "Enclosure 1", "Enclosure 2", ..., "Barn", "truck 1", truck 2", ... as translatable strings?

* Find bugs -- add "debug_" methods to help set up situations?
 * could add new tiles with this so don't have to fish through stock.

* UX: consider clicking on enclosures as a whole (for exchange, at least)?

* UX: placing tiles from trucks seems like has an extra click:
  1. click on "take truck"
  2. click on truck
  3. click on tile on truck
  4. click on destination
     go to 3 until done
Maybe combine 2 & 3? Maybe require truck pieces delivered "in order"?

* Generate PHP/TS classes from user preferences?

* UX: add "undo" to go back a step?

* UX questions:
  * Feedback on colors for highlighting
  * Put trucks & stock at top instead of right? for mobile, yes.
  * Modal player aid?

Bugs / to test:
  * first draw from endgame pile notif not handled right on on-triggering player
  * visual bug in barn -- 2nd (or last?) tile placed there has elongated height.
    * caused because end up with flipping front/back spans still existing!
  * Need to test offspring on exchanges
  * Need to test offspring "overflowing" into barn

* Statistics
  * Points for completed enclosures
  * Points for near-complete enclosures
  * Points for incomplete enclosures with stalls
  * Points for different stalls
  * Penalty for animals left in barn
  * Penalty for stalls left in barn (combine with prev?)
  * Offspring generated
  * Offspring generated into barn
  * Offspring generated that completed an enclosure (worth tracking?)
  * Tiles purchased
  * Tiles sold
  * Number of exchange actions
  * Number of exchange actions with barn
  * Number of discarded tiles
  * Number of moved stalls (not from barn)
  * Number of moved animals
  * Number of moved stalls from barn
  * Number of expansions purchased
  * Number of tiles drawn
  * Number of trucks taken
  * Number of tiles taken from trucks
  * Number of tiles taken from truck into barn
  * Number of coin tiles acquired
  * Coins gained from completion bonuses
