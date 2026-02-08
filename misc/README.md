== Implementation notes

* Enclosures are numbered 0-5, with 0 being the barn, and 1-3 being the first three, starting with the enclosure at "12 o'clock" and proceeding clockwise. (In otherwords, the starting enclosures of size 5,4, and 6 in that order.) The first available expansion is enclosure number 4, and (2p only) the 2nd expansion is 5.
* Spaces ("positions") in enclosures are number 1 - N where N is the number of spaces, *including stall spaces*. For example, for enclosure 1, positions 1-5 correspond to animal spaces and 6 is the stall space; for enclosure 2, positions 1-4 are animal spaces and 5-6 are stall spaces.
  * The barn doesn't distinguish "types" of spaces. Its spaces are 1-20.
    (the only conceptual limit is the UI, and then only if we insist the tiles have the same size everywhere).
* Server-side an enclosure space is represented by the Space class; client-side, a space is represented by a single number which is `100 * enclosure_id + pos`.

* Exchanges can generate offspring, but only if exchanging with the barn. If an exchange is with the barn, no offspring in barn, but maybe two offspring, if somehow got two fertile pairs. (Could be done, but boy is that weird.). If two enclosures are exchanged, no offpsring possible, since they would have already been generated.
