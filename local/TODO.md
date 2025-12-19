* Final score sheet rendering

* Handle rare exchange-triggers-two-offspring-in-one-enclosure case.

* Log messages for all significant events (either separate messages for moves/money/offspring, or compound ones).
  * It's a question: separate notifs for
    * completing enclosure
    * offspring produced
  * Need to rework model for server objects to properly do this for take truck placements

* Rework model objects

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

* UX: consider: when other player's turn, scroll their board into view. or reorder as turns go?

* UX questions:
  * Feedback on colors for highlighting
  * Put trucks & stock at top instead of right? for mobile, yes.
  * Modal player aid?

To test:
  * Need to test offspring on exchanges
  * Need to test offspring "overflowing" into barn

Bugs:
  * Need to notify offspring and completion bonus(es) when taketruckplacetiles.
  * Undo a placement that generates offspring doesn't "go back" far enough. Refrehs fixes it.

Improvements:
  * When only one action just do it (?)
    * if only one option for each of remaining truck tiles, place them
    * if only one option for drawn tile, put it in that truck space
    * if only one option for tile to move, pre-select it
    * if only one option for moved to to go, move it
    * if only one option to exchange enclosures, exchange them
    * if only one option to purchase a tile, purchase it
    * if only one option for a purchased tile to go, put it there
    * etc etc
    * Maybe don't do this if no confirmation stage, though.
  * Consider clicking on the stock pile as a "Draw"
  * Consider clicking on a truck space as a "select truck and tile"
  * Consider clicking on any space in an enclosure as "that's the destination"
