<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

use Bga\Games\zooloretto\Model\{Enclosure,Tile,TileType};

final class EnclosureTest extends TestCase
{
    public function testEmptyBarn(): void
    {
        $barn = Enclosure::barn();

        // nothing in it
        $this->assertEquals([], array_filter($barn->allContents(), fn($t) => !$t->isEmpty()));

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
            $this->assertEquals($pos++, $barn->placeTile(new Tile($id++, TileType::CAMEL)));
            $this->assertEquals($pos++, $barn->placeTile(new Tile($id++, TileType::BARROW)));
            $this->assertEquals($pos++, $barn->placeTile(new Tile($id++, TileType::ELEPHANT_FEMALE)));
        }
    }

    public function testPlacements(): void
    {
        $enc = Enclosure::create(1, 3, 2);

        $this->assertEquals(1, $enc->placeTile(new Tile(1, TileType::ZEBRA)));

        // same species can still go in
        $this->assertEquals(2, $enc->availablePos(TileType::ZEBRA_FEMALE));
        // but not other species
        $this->assertEquals(0, $enc->availablePos(TileType::CAMEL));
        // stall can still go in
        $this->assertEquals(4, $enc->availablePos(TileType::KIOSK));

        // we can place another zebra
        $this->assertEquals(2, $enc->placeTile(new Tile(2, TileType::ZEBRA_FEMALE)));
        // anod another
        $this->assertEquals(3, $enc->placeTile(new Tile(2, TileType::ZEBRA_MALE)));
        // but now we're full of animals
        $this->assertEquals(0, $enc->availablePos(TileType::ZEBRA_MALE));

        // 2 stalls can still go in
        $this->assertEquals(4, $enc->availablePos(TileType::KIOSK));
        $this->assertEquals(4, $enc->placeTile(new Tile(4, TileType::KIOSK)));
        $this->assertEquals(5, $enc->placeTile(new Tile(4, TileType::BARROW)));
        // and then we're full
        $this->assertEquals(0, $enc->availablePos(TileType::POPCORN));
    }

    public function testTakeTile()
    {
        $enc = Enclosure::create(1, 3, 2);

        $t1 = new Tile(1, TileType::ZEBRA);
        $t2 = new Tile(2, TileType::ZEBRA_FEMALE);
        $t3 = new Tile(3, TileType::KIOSK);
        $this->assertEquals(1, $enc->placeTile($t1));
        $this->assertEquals(2, $enc->placeTile($t2));
        $this->assertEquals(4, $enc->placeTile($t3));

        $this->assertEquals($t2, $enc->takeTileAt(2));
        $this->assertEquals([1 => $t1, 4 => $t3], array_filter($enc->allContents(), fn ($t) => !$t->isEmpty()));

        $this->assertEquals($t1, $enc->takeTileAt(1));
        $this->assertEquals([4 => $t3], array_filter($enc->allContents(), fn ($t) => !$t->isEmpty()));

        // no species so we can now place other species.
        $this->assertEquals(1, $enc->placeTile(new Tile(4, TileType::ELEPHANT)));
    }
}
