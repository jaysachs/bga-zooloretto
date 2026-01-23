Migration sketch
================

1. Update schema:
   * Create `trucks` table
   * Create `tiles` table
   * Add columns to `player` table
2. Read everything in from old tables as arrays of assoc arrays
 * `players`
 * `wagons`
 * `animals`
3. For each player, copy `unblockedzoo` to `purchased_extensions`, handling null.
4. For each "animal":
 * If status = AVAILABLE, LASTSET, DISCARDED, DRAWN, PLAYED, STALL
     Insert the appropriate tile
      (for STALL, need to find the next available barn position)
      (for stalls, need to adjust enclosure/position)
 * If status = THINKING
      Insert tile, but indicate on truck (truck # is in `x` column)
 * If status = THIKINGKID or THIKINGKIDSTALL
      do nothing
5. For each wagon
 * if status == 'PLAYED'
    pick a player with `skipped` == `Y` and grab the player ID, and record that the player was assigned a truck
 * Insert a truck row.
   * If size is not 3, create BLOCK tiles and insert them, and add them to the truck
   * Use the original "animal" ID in the `valX` column



Player table
------------

```sql
-- hmm, the original is signed, and default NULL ... ?
ALTER TABLE player ADD COLUMN `purchased_extensions` int(10) unsigned NOT NULL DEFAULT 0;
```
