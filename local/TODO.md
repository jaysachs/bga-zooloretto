* Fix restart
* Truck animation into/out of player boards "glitch" (temporarily grows player board area -- need to keep size of truck small until removed / before added)
* (?) Add undo back
* Verify all notifications are properly handled
* Verify all log messages are complete and correct
* (?) If just one option, automatically do it, e.g.
  * draw: only one truck spot open, or all equivalent
  * deliver tile: if only one spot or all equivalent
* Verify offspring generated at right times
  * in particular, the rare exchange-triggers-two-offspring?
* Verify offspring overflow into barn
* Verify completion bonuses generated at right times
* Mobile testing
* (?) add back player aid
* Update layout for mobile (trucks at top)
* Potential improvements
  * Add "redo" to flows
  * UX: consider: when other player's turn, scroll their board into view. or reorder as turns go?
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
  * Consider clicking on any space in an enclosure as "that's the destination"
* (?) Automatic delivery choices? How can that really be good except in trivial cases? I guess with a "restart turn" it can be flawed.
* Generate PHP/TS classes from user preferences?
* Eliminate zglobal tables, replace with extra field on player
