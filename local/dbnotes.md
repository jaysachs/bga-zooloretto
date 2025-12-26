Stock
=====
Definitely need just one table. We need tile_id (fk to tile table). Technically we don't need a `seq_id` primary key since `tile_ids` in the stock should always be unique. If we randomly create the initial `tile_ids`, that would work. How to represent the "drawn" tile? There, we could use ID of 0.

We let the middle manage the "endgame/main" separation.

Alternatively, we could add a separate `seq_id` primary key, and
shuffle the tiles before insertion. Can we use `seq_id` of 0 to
represent "drawn" if `seq_id` auto-increment? And presumably we can't
update the primary key, so we'd need to delete a tile and insert it
with seq_id of 0. But that may not be permitted.

In which case, we could add a column `drawn`, of type "bool"
(`int(1)`). Then, to mark something drawn, we just set that column of
the given row.

Columns are cheap, and the explicit is nice.


Tiles
=====
So could keep the current situation, where there are different tables
for each location type (truck, enclosures, stock).

Or we could put the location in the Tiles table:

```
  `id` int(10) unsigned NOT NULL,
  `type` varchar(10) NOT NULL,
  `reproduced` TINYINT(1) NOT NULL DEFAULT 0,

  -- explicit locations
  -- in truck
  `truck` int(1),
  `truck_pos` int(1),

  -- in an enclosure
  `enclosure_id` int(1),
  `enclosure_pos` int (1),
  `player_id` int(10),

  -- in the stock
  `stock_pos` int(3),
  `drawn` int (1)

  -- OR
  `location` varchar(20),
  -- where encoding is something like:
  -- in truck:
  --  T:{truck_id}:{truck_pos}
  -- in enclosure:
  --  E:{player_id}:{enc_id}:{enc_pos}
  -- in the stock:
  --  S:{stock_pos}
  -- drawn:
  --  D
  PRIMARY KEY (`id`)

  -- OR
  `location` varchar(1),
  -- ignored unless E
  `player_id` int(10) UNSIGNED,
  -- truck_id if T, enc_id if E
  `x` int(10) UNSIGNED,
  -- truck_pos if T, enc_pos if E, stock_pos if S
  `y` int(10) UNSIGNED,
```

In this case, the initial insert would put all tiles in the stock at
a random position.

Oh, but some tiles don't have a normal location -- like the "block"
tiles in the 2p trucks, and the "empty" tile. Well, the "empty" tile
could have an empty location, that just works. But for the block
tiles, we could give them specific positions in the trucks too. We'd just need to create 3 of the block tiles.

So how does drawing a tile work here? We retrieve the first
row where location starts with "S", and set its pos to 0.

Place a tile in a truck? Update the location.

Place a tile in an enclosure? Update the location.

To retrieve the stock... technically we only need counts and the
drawn tile, if any. We change "drawn" to have location "D"


```
-- get count of tiles in stock
SELECT COUNT(*) FROM tiles WHERE location LIKE 'S%';
-- get drawn tile
SELECT id, type FROM tiles WHERE location = 'D';
```

So, two SELECTs to get the stock.

Though, if we had this representation, we would more likely just suck
in the whole tile table and then create *everything* from it. That's kind of the beauty of it.

```php
$stocktiles = [];
$drawn = Tile::Empty();
$bankmoney = retrieveBankMoney();
$players = retrieveAllPlayers(); // has ext count
$trucks = createEmptyTrucksForPlayerCount($num_players);
$encs = createEmptyEnclosuresForPlayers($players);
foreach ($db->getObjectList("SELECT * FROM tiles ORDER BY location,x,y") as $row) {
  $tile = new Tile(intval($row['id']), TileType::from($row['type']));
  $loc = $row['location'];
  if ($loc startswith 'S') {
    $stocktiles[] = $tile;
  } else if ($loc == 'D') {
    $drwn = $tile;
  } else if ($loc startswith 'T') {
    ($truck_id, $truck_pos) = parseFromLoc($loc);
    $p = $trucks[$truck_id]->placeTile($tile, $truck_pos);
    if ($p <> $truck_pos) { error }
  } else if $(loc starts with 'E') {
    ($pid, $eid, $pos) = parseEncFromLoc($loc);
    $p = $encs[$pid][$eid]->placeTile($tile, $pos);
    if ($p <> $pos) { error }
  } else { error }
}

To update enclosures after an exchange:
  update location of all tiles in those enclosures
To update enclosures after a move:
  update location of all tiles in those enclosures
To update enclosures after a purchase:
  update location of all tiles in those enclosures
To update after a discard:
  update location of discarded tile
To update expand:
  update player
To update after taking a truck:
  update all tiles that were in the truck
    (coins need to be marked removed -- "O" for offboard
To dump tiles from a truck
  update location of all tiles that were in that truck
To update after a tile drawn
  update that tile


Independent "optimization" of stock retrieval:

```
SELECT COUNT(*) AS num, MIN(seq_id) AS top FROM stock;

SELECT s.tile_id, s.drawn, t.type
FROM stock s
INNER JOIN tiles t ON s.tile_id = t.id
ORDER BY s.seq_id
LIMIT 2
```
