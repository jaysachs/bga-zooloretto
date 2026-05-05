<?php

declare(strict_types=1);

namespace Bga\Games\zoolorettoalpha\Model;

use PHPUnit\Framework\TestCase;

final class EnclosureTest extends TestCase
{
    public function testEmptyBarn(): void
    {
        $barn = Enclosure::barn();

        // nothing in it
        $this->assertEquals([], $barn->nonEmptyContents());

        // animals can go in
        $this->assertEquals(1, $barn->availablePos(TileType::CAMEL));
        // stalls can go into the same spot
        $this->assertEquals(1, $barn->availablePos(TileType::KIOSK));
        // other kinds cannot
        $this->assertEquals(0, $barn->availablePos(TileType::COIN));
    }

    public function testBarnPlacements(): void
    {
        $barn = Enclosure::barn();
        // barns have "large" (20) capacity and don't care about species
        $pos = 1;
        $id = 10;
        for ($i = 0; $i <6; $i++) {
            $tile = new Tile($id++, TileType::CAMEL);
            $this->assertEquals(new PlacedTile($tile, new Space(0, $pos++)), $barn->placeTile($tile, $barn));
            $tile = new Tile($id++, TileType::BARROW);
            $this->assertEquals(new PlacedTile($tile, new Space(0, $pos++)), $barn->placeTile($tile, $barn));
            $tile = new Tile($id++, TileType::ELEPHANT_FEMALE);
            $this->assertEquals(new PlacedTile($tile, new Space(0, $pos++)), $barn->placeTile($tile, $barn));
        }
    }

    public function testPlacements(): void
    {
        $barn = Enclosure::barn();
        $enc = Enclosure::forTest(1, 3, 2, 17);
        $tile = new Tile(1, TileType::ZEBRA);
        $this->assertEquals(new PlacedTile($tile, new Space(1, 1)), $enc->placeTile($tile, $barn));

        // same species can still go in
        $this->assertEquals(2, $enc->availablePos(TileType::ZEBRA_FEMALE));
        // but not other species
        $this->assertEquals(0, $enc->availablePos(TileType::CAMEL));
        // stall can still go in
        $this->assertEquals(4, $enc->availablePos(TileType::KIOSK));

        // we can place another zebra
        $tile1 = new Tile(2, TileType::ZEBRA_FEMALE);
        $tile1r = $tile1->clone()->markReproduced();
        $this->assertEquals(new PlacedTile($tile1, new Space(1, 2)), $enc->placeTile($tile1, $barn));
        // and another
        $tile2 = new Tile(3, TileType::ZEBRA_MALE);
        $tile2r = $tile2->clone()->markReproduced();
        $pt = $enc->placeTile($tile2, $barn);
        $this->assertEquals(new PlacedTile($tile2, new Space(1, 3), 17,
            new Offspring(
                new PlacedTile(new Tile(20003, TileType::ZEBRA_KID), new Space(0,1)),
                $tile1r, $tile2r)), $pt);
        // but now we're full of animals
        $this->assertEquals(0, $enc->availablePos(TileType::ZEBRA_MALE));

        // 2 stalls can still go in
        $this->assertEquals(4, $enc->availablePos(TileType::KIOSK));
        $tile = new Tile(4, TileType::KIOSK);
        $this->assertEquals(new PlacedTile($tile, new Space(1, 4)), $enc->placeTile($tile, $barn));
        $tile = new Tile(5, TileType::BARROW);
        $this->assertEquals(new PlacedTile($tile, new Space(1, 5)), $enc->placeTile($tile, $barn));
        // and then we're full
        $this->assertEquals(0, $enc->availablePos(TileType::POPCORN));
    }

    public function testTakeTile(): void
    {
        $barn = Enclosure::barn();
        $enc = Enclosure::forTest(1, 3, 2);

        $t1 = new Tile(1, TileType::ZEBRA);
        $t2 = new Tile(2, TileType::ZEBRA_FEMALE);
        $t3 = new Tile(3, TileType::KIOSK);
        $this->assertEquals(new PlacedTile($t1, new Space(1, 1)), $enc->placeTile($t1, $barn));
        $this->assertEquals(new PlacedTile($t2, new Space(1, 2)), $enc->placeTile($t2, $barn));
        $this->assertEquals(new PlacedTile($t3, new Space(1, 4)), $enc->placeTile($t3, $barn));

        $this->assertEquals($t2, $enc->takeTileAt(2));
        $this->assertEquals([1 => $t1, 4 => $t3], $enc->nonEmptyContents());

        $this->assertEquals($t1, $enc->takeTileAt(1));
        $this->assertEquals([4 => $t3], $enc->nonEmptyContents());

        // no species so we can now place other species.
        $t4 = new Tile(4, TileType::ELEPHANT);
        $this->assertEquals(new PlacedTile($t4, new Space(1, 1)), $enc->placeTile($t4, $barn));
    }

    public function testCheckForOffspring(): void {
        $barn = Enclosure::barn();
        $e = Enclosure::forTest(1, 5, 2);
        // $this->assertNull($e->checkForOffspring($barn));
        $this->assertNull($barn->placeTile(new Tile(1, TileType::CAMEL_FEMALE), $barn)->offspring);
        $this->assertNull($barn->placeTile(new Tile(2, TileType::CAMEL_MALE), $barn)->offspring);

        $this->assertNull($e->placeTile(new Tile(3, TileType::CAMEL_FEMALE), $barn)->offspring);
        $pt = $e->placeTile(new Tile(4, TileType::CAMEL_MALE), $barn);
        $this->assertEquals(
            new Offspring(
                new PlacedTile(new Tile(30004, TileType::CAMEL_KID), new Space(1, 3)),
                new Tile(3, TileType::CAMEL_FEMALE, true),
                new Tile(4, TileType::CAMEL_MALE, true)),
            $pt->offspring);

        $this->assertNull($e->placeTile(new Tile(54, TileType::ELEPHANT_MALE), $barn)->offspring);
        $pt = $e->placeTile(new Tile(36, TileType::ELEPHANT_FEMALE), $barn);
        $this->assertEquals(
            new Offspring(
                new PlacedTile(new Tile(360054, TileType::ELEPHANT_KID), new Space(0, 3)),
                new Tile(36, TileType::ELEPHANT_FEMALE, true),
                new Tile(54, TileType::ELEPHANT_MALE, true)),
            $pt->offspring);
        $this->assertEquals(new Tile(360054, TileType::ELEPHANT_KID), $barn->tileAt(3));
    }
}
