* Needs thorough testing
  * undo / restart flows
  * offspring generated at right times
    * in particular, the rare exchange-triggers-two-offspring?
  * offspring overflow into barn
  * completion bonuses generated at right times
  * notifications / UI updates correctly in those cases
  * mobile

* Player aid

* Update layout for mobile (trucks at top)

* Potential improvements
  * Add "redo" to flows
  * UX: consider: when other player's turn, scroll their board into view. or reorder as turns go?
  * UX: for multi-stage flows, keep "parent" choices highlighted
    * e.g. truck when placing tiles
  * consider clicking on enclosures as a whole (for exchange, at least)
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
  * UX: placing tiles from trucks seems like has an extra click:
    1. click on "take truck"
    2. click on truck
    3. click on tile on truck
    4. click on destination
       go to 3 until done
    Maybe combine 2 & 3? Maybe require truck pieces delivered "in order"?
  * Consider clicking on the stock pile as a "Draw"
  * Consider clicking on a truck space as a "select truck and tile"
  * Consider clicking on any space in an enclosure as "that's the destination"
  * Automatic placement into enclosures? How can that really be good except in trivial cases? I guess with a "restart turn" it can be flawed.

* Generate PHP/TS classes from user preferences?
