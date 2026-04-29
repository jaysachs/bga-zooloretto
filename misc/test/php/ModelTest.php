<?php

declare(strict_types=1);

namespace Bga\Games\zoolorettoalpha\Model;

use PHPUnit\Framework\TestCase;

function e(int $x, int $y, Tile $t,?Offspring $offspring = null): PlacedTile {
    return new PlacedTile($t, new Space($x, $y), 0, $offspring);
}

final class ModelTest extends TestCase
{
    public function testPossibleDeliveries_EmptyTruck(): void
    {
        $encs = [ Enclosure::barn(), Enclosure::forTest(1, 3, 1), Enclosure::forTest(2, 4, 2) ];
        $truck = new Truck(1);
        $this->assertEquals([], Model::possibleDeliveriesFor($truck, $encs, 1));
    }

    public function testPossibleDeliveries_One(): void
    {
        $encs = [ Enclosure::barn(), Enclosure::forTest(1, 3, 1), Enclosure::forTest(2, 4, 2) ];
        $truck = new Truck(1);
        $t1 = new Tile(1, TileType::CAMEL);
        $truck->placeTileAt( $t1, 1);
        $this->assertEquals(
            [1 => [e(0,1,$t1), e(1,1,$t1), e(2,1,$t1)]],
            Model::possibleDeliveriesFor($truck, $encs, 1));
    }

    public function testPossibleDeliveries_TwoDifferentSpecies(): void
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

    public function testPossibleDeliveries_TwoOfSameSpecies(): void
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

    public function testPossibleDeliveries_FertilePair(): void
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

        $kid = function(int $eid, int $pos, int $comp = 0) use(&$rm, &$rf) : Offspring {
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

    public function testExchange_BarnAtCapacity(): void {
        $encs = [ Enclosure::barn(), Enclosure::forTest(3,6,1) ];
        $encs[0]->placeTile(new Tile(1, TileType::CAMEL), $encs[0]);
        $encs[0]->placeTile(new Tile(2, TileType::CAMEL_FEMALE), $encs[0]);
        $encs[0]->placeTile(new Tile(3, TileType::FLAMINGO), $encs[0]);
        $encs[0]->placeTile(new Tile(4, TileType::CAMEL), $encs[0]);
        $encs[0]->placeTile(new Tile(5, TileType::CAMEL_FEMALE), $encs[0]);
        $encs[0]->placeTile(new Tile(6, TileType::CAMEL_MALE), $encs[0]);
        $encs[0]->placeTile(new Tile(7, TileType::CAMEL_MALE), $encs[0]);

        $encs[1]->placeTile(new Tile(10, TileType::ELEPHANT), $encs[0]);
        $encs[1]->placeTile(new Tile(11, TileType::ELEPHANT), $encs[0]);
        $encs[1]->placeTile(new Tile(12, TileType::ELEPHANT_FEMALE), $encs[0]);

        $ce = Model::doExchange($encs[1], $encs[0], $encs[0], [1,2,4,5,6,7]);
        $this->assertEquals(
            new CompletedExchange(3, TileType::ELEPHANT, 0, TileType::CAMEL,
            [
                new PlacedTile(new Tile(10, TileType::ELEPHANT), new Space(0,1)),
                new PlacedTile(new Tile(11, TileType::ELEPHANT), new Space(0,2)),
                new PlacedTile(new Tile(12, TileType::ELEPHANT_FEMALE), new Space(0,4)),
                new PlacedTile(new Tile(1, TileType::CAMEL), new Space(3,1)),
                new PlacedTile(new Tile(2, TileType::CAMEL_FEMALE), new Space(3,2)),
                new PlacedTile(new Tile(4, TileType::CAMEL), new Space(3,3)),
                new PlacedTile(new Tile(5, TileType::CAMEL_FEMALE), new Space(3,4)),
                new PlacedTile(new Tile(6, TileType::CAMEL_MALE), new Space(3,5)),
                new PlacedTile(new Tile(7, TileType::CAMEL_MALE), new Space(3,6)),
            ],
        [
            new Offspring(
                new PlacedTile(
                    new Tile(20006, TileType::CAMEL_KID),
                    new Space(0, 5)),
                new Tile(2, TileType::CAMEL_FEMALE, true),
                new Tile(6, TileType::CAMEL_MALE, true)),
            new Offspring(
                new PlacedTile(
                    new Tile(50007, TileType::CAMEL_KID),
                    new Space(0, 6)),
                new Tile(5, TileType::CAMEL_FEMALE, true),
                new Tile(7, TileType::CAMEL_MALE, true)),
        ]
        ), $ce);
    }
}
