<?php

declare(strict_types=1);

namespace Bga\Games\zoolorettoalpha\Model;

use PHPUnit\Framework\TestCase;

function e(int $x, int $y, Tile $t,?Offspring $offspring = null, ?Moneys $moneyDelta = null): PlacedTile {
    return new PlacedTile($t, new Space($x, $y), false, $moneyDelta, $offspring);
}

final class ModelTest extends TestCase
{
    public function testEmptyTruck(): void
    {
        $encs = [ Enclosure::barn(), Enclosure::forTest(1, 3, 1), Enclosure::forTest(2, 4, 2) ];
        $truck = new Truck(1);
        $this->assertEquals([], Model::possibleDeliveriesFor($truck, $encs, 1));
    }

    public function testOne(): void
    {
        $encs = [ Enclosure::barn(), Enclosure::forTest(1, 3, 1), Enclosure::forTest(2, 4, 2) ];
        $truck = new Truck(1);
        $t1 = new Tile(1, TileType::CAMEL);
        $truck->placeTileAt( $t1, 1);
        $this->assertEquals(
            [1 => [e(0,1,$t1), e(1,1,$t1), e(2,1,$t1)]],
            Model::possibleDeliveriesFor($truck, $encs, 1));
    }

    public function testTwoDifferentSpecies(): void
    {
        $encs = [ Enclosure::barn(), Enclosure::forTest(1, 3, 1), Enclosure::forTest(2, 4, 2) ];
        $truck = new Truck(1);
        $t1 = new Tile(1, TileType::CAMEL);
        $t2 = new Tile(2, TileType::ZEBRA);
        $truck->placeTileAt($t1, 1);
        $truck->placeTileAt($t2, 2);

        $expected = [
            1 => [e(0, 1, $t1),e(1, 1, $t1),e(2, 1,$t1)],
            2 => [e(0, 1,$t2),e(1, 1,$t2),e(2, 1,$t2)],
        ];

        $this->assertEquals(
            $expected,
            Model::possibleDeliveriesFor($truck, $encs, 1));
    }

    public function testTwoOfSameSpecies(): void
    {
        $encs = [ Enclosure::barn(), Enclosure::forTest(1, 3, 1), Enclosure::forTest(2, 4, 2) ];
        $truck = new Truck(1);
        $t1 = new Tile(1, TileType::CAMEL);
        $t2 = new Tile(2, TileType::CAMEL_MALE);
        $truck->placeTileAt($t1, 1);
        $truck->placeTileAt($t2, 2);

        $expected = [
            1 => [e(0, 1, $t1), e(1, 1, $t1), e(2, 1, $t1)],
            2 => [e(0, 1, $t2), e(1, 1, $t2), e(2, 1, $t2)],
        ];

        $this->assertEquals(
            $expected,
            Model::possibleDeliveriesFor($truck, $encs, 1));
    }

    public function testFertilePair(): void
    {
        $encs = [ Enclosure::barn(), Enclosure::forTest(1, 3, 1, 7), Enclosure::forTest(2, 2, 2, 5) ];
        $truck = new Truck(1);
        $mother = new Tile(1, TileType::CAMEL_FEMALE);
        $rm = $mother->clone();
        $father = new Tile(2, TileType::CAMEL_MALE);
        $rf = $father->clone();

        $rm->markReproduced();
        $rf->markReproduced();

        $truck->placeTileAt($mother, 1);
        $truck->placeTileAt($father, 2);

        $kid = function(int $eid, int $pos, bool $comp = false) use(&$rm, &$rf) : Offspring {
            return new Offspring(
                new PlacedTile(new Tile($rm->id*10000+$rf->id, TileType::CAMEL_KID), new Space($eid, $pos), $comp),
                $rm, $rf);
        };

        // FIXME: shouldn't there be offspring here?
        $expected = [
            1 => [e(0, 1, $mother), e(1, 1, $mother), e(2, 1, $mother)],
            2 => [e(0, 1, $father), e(1, 1, $father), e(2, 1, $father)],
        ];

        $this->assertEquals(
            $expected,
            Model::possibleDeliveriesFor($truck, $encs, 1));
    }
}
