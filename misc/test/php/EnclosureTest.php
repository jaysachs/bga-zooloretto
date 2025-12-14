<?php

declare(strict_types=1);

namespace Bga\Games\zooloretto\Model;

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
        // barns have "infinite" capacity and don't care about species
        $pos = 1;
        $id = 10;
        for ($i = 0; $i < 10; $i++) {
            $this->assertEquals(new Placement(new Space(0, $pos++)), $barn->placeTile(new Tile($id++, TileType::CAMEL)));
            $this->assertEquals(new Placement(new Space(0, $pos++)), $barn->placeTile(new Tile($id++, TileType::BARROW)));
            $this->assertEquals(new Placement(new Space(0, $pos++)), $barn->placeTile(new Tile($id++, TileType::ELEPHANT_FEMALE)));
        }
    }

    public function testPlacements(): void
    {
        $enc = Enclosure::forTest(1, 3, 2);

        $this->assertEquals(new Placement(new Space(1, 1)), $enc->placeTile(new Tile(1, TileType::ZEBRA)));

        // same species can still go in
        $this->assertEquals(2, $enc->availablePos(TileType::ZEBRA_FEMALE));
        // but not other species
        $this->assertEquals(0, $enc->availablePos(TileType::CAMEL));
        // stall can still go in
        $this->assertEquals(4, $enc->availablePos(TileType::KIOSK));

        // we can place another zebra
        $this->assertEquals(new Placement(new Space(1, 2)), $enc->placeTile(new Tile(2, TileType::ZEBRA_FEMALE)));
        // anod another
        $this->assertEquals(new Placement(new Space(1, 3), true), $enc->placeTile(new Tile(2, TileType::ZEBRA_MALE)));
        // but now we're full of animals
        $this->assertEquals(0, $enc->availablePos(TileType::ZEBRA_MALE));

        // 2 stalls can still go in
        $this->assertEquals(4, $enc->availablePos(TileType::KIOSK));
        $this->assertEquals(new Placement(new Space(1, 4)), $enc->placeTile(new Tile(4, TileType::KIOSK)));
        $this->assertEquals(new Placement(new Space(1, 5)), $enc->placeTile(new Tile(4, TileType::BARROW)));
        // and then we're full
        $this->assertEquals(0, $enc->availablePos(TileType::POPCORN));
    }

    public function testTakeTile()
    {
        $enc = Enclosure::forTest(1, 3, 2);

        $t1 = new Tile(1, TileType::ZEBRA);
        $t2 = new Tile(2, TileType::ZEBRA_FEMALE);
        $t3 = new Tile(3, TileType::KIOSK);
        $this->assertEquals(new Placement(new Space(1, 1)), $enc->placeTile($t1));
        $this->assertEquals(new Placement(new Space(1, 2)), $enc->placeTile($t2));
        $this->assertEquals(new Placement(new Space(1, 4)), $enc->placeTile($t3));

        $this->assertEquals($t2, $enc->takeTileAt(2));
        $this->assertEquals([1 => $t1, 4 => $t3], $enc->nonEmptyContents());

        $this->assertEquals($t1, $enc->takeTileAt(1));
        $this->assertEquals([4 => $t3], $enc->nonEmptyContents());

        // no species so we can now place other species.
        $this->assertEquals(new Placement(new Space(1, 1)), $enc->placeTile(new Tile(4, TileType::ELEPHANT)));
    }

    public function testCheckForOffspring(): void {
        $barn = Enclosure::barn();
        $e = Enclosure::forTest(1, 5, 2);
        $this->assertNull($e->checkForOffspring($barn));
        $barn->placeTile(new Tile(1, TileType::CAMEL_FEMALE));
        $barn->placeTile(new Tile(2, TileType::CAMEL_MALE));
        $this->assertNull($barn->checkForOffspring($barn));

        $e->placeTile(new Tile(3, TileType::CAMEL_FEMALE));
        $e->placeTile(new Tile(4, TileType::CAMEL_MALE));
        $this->assertEquals(
            new Offspring(
                new PlacedTile(new Tile(303, TileType::CAMEL_KID), new Space(1, 3)),
                new Tile(3, TileType::CAMEL_FEMALE_R),
                new Tile(4, TileType::CAMEL_MALE_R),
                false),
            $e->checkForOffspring($barn));
        $this->assertNull($e->checkForOffspring($barn));

        $e->placeTile(new Tile(5, TileType::ELEPHANT_MALE));
        $e->placeTile(new Tile(6, TileType::ELEPHANT_FEMALE));
        $this->assertEquals(
            new Offspring(
                new PlacedTile(new Tile(306, TileType::ELEPHANT_KID), new Space(0, 3)),
                new Tile(6, TileType::ELEPHANT_FEMALE_R),
                new Tile(5, TileType::ELEPHANT_MALE_R),
                false),
            $e->checkForOffspring($barn));
        $this->assertEquals(new Tile(306, TileType::ELEPHANT_KID), $barn->tileAt(3));
    }
}
