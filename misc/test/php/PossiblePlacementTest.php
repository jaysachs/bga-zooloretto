<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

use Bga\Games\zooloretto\Utils;
use Bga\Games\zooloretto\Model\{Enclosure, MoneyDelta, Offspring, PlacementForEnclosure, PlacementsForTruckPos, PossiblePlacement, Space, Tile, TileType, Truck};

function e(int $x, int $y, $z = null, ?Offspring $offspring = null, ?MoneyDelta $moneyDelta = null) {
    return new PlacementForEnclosure(new Space($x, $y), $z, $offspring, $moneyDelta);
}
function t(int $truck_id, TileType $y, $z = []) { return new PlacementsForTruckPos($truck_id, $y, $z); }
function p($x = []) { return new PossiblePlacement($x); }

final class PossiblePlacementTest extends TestCase
{
    public function testEmptyTruck(): void
    {
        $encs = [ Enclosure::barn(), Enclosure::forTest(1, 3, 1), Enclosure::forTest(2, 4, 2) ];
        $truck = new Truck(1);
        $this->assertEquals(p([]), PossiblePlacement::possiblePlacementFor(0, $truck, $encs));
    }

    public function testOne(): void
    {
        $encs = [ Enclosure::barn(), Enclosure::forTest(1, 3, 1), Enclosure::forTest(2, 4, 2) ];
        $truck = new Truck(1);
        $truck->placeTileAt( new Tile(1, TileType::CAMEL), 1);
        $this->assertEquals(
            p([t(1, TileType::CAMEL, [e(0,1), e(1,1), e(2,1)])]),
            PossiblePlacement::possiblePlacementFor(0, $truck, $encs));
    }

    public function testTwoDifferentSpecies(): void
    {
        $encs = [ Enclosure::barn(), Enclosure::forTest(1, 3, 1), Enclosure::forTest(2, 4, 2) ];
        $truck = new Truck(1);
        $truck->placeTileAt(new Tile(1, TileType::CAMEL), 1);
        $truck->placeTileAt(new Tile(2, TileType::ZEBRA), 2);

        $expected = p([
            t(1, TileType::CAMEL, [
                e(0, 1, p([
                    t(2, TileType::ZEBRA, [
                        e(0, 2),
                        e(1, 1),
                        e(2, 1),
                    ])
                ])),
                e(1, 1, p([
                    t(2, TileType::ZEBRA, [
                        e(0, 1),
                        e(2, 1),
                    ])
                ])),
                e(2, 1, p([
                    t(2, TileType::ZEBRA, [
                        e(0, 1),
                        e(1, 1),
                    ])
                ])),
            ]),
            t(2, TileType::ZEBRA, [
                e(0, 1, p([
                    t(1, TileType::CAMEL, [
                        e(0, 2),
                        e(1, 1),
                        e(2, 1),
                    ])
                ])),
                e(1, 1, p([
                    t(1, TileType::CAMEL, [
                        e(0, 1),
                        e(2, 1),
                    ])
                ])),
                e(2, 1, p([
                    t(1, TileType::CAMEL, [
                        e(0, 1),
                        e(1, 1),
                    ])
                ])),
            ]),
        ]);

        $this->assertEquals(
            $expected,
            PossiblePlacement::possiblePlacementFor(0, $truck, $encs));
    }

    public function testTwoOfSameSpecies(): void
    {
        $encs = [ Enclosure::barn(), Enclosure::forTest(1, 3, 1), Enclosure::forTest(2, 4, 2) ];
        $truck = new Truck(1);
        $truck->placeTileAt(new Tile(1, TileType::CAMEL), 1);
        $truck->placeTileAt(new Tile(2, TileType::CAMEL_MALE), 2);

        $expected = p([
            t(1, TileType::CAMEL, [
                e(0, 1, p([
                    t(2, TileType::CAMEL_MALE, [
                        e(0, 2),
                        e(1, 1),
                        e(2, 1),
                    ])
                ])),
                e(1, 1, p([
                    t(2, TileType::CAMEL_MALE, [
                        e(0, 1),
                        e(1, 2),
                        e(2, 1),
                    ])
                ])),
                e(2, 1, p([
                    t(2, TileType::CAMEL_MALE, [
                        e(0, 1),
                        e(1, 1),
                        e(2, 2),
                    ])
                ])),
            ]),
            t(2, TileType::CAMEL_MALE, [
                e(0, 1, p([
                    t(1, TileType::CAMEL, [
                        e(0, 2),
                        e(1, 1),
                        e(2, 1),
                    ])
                ])),
                e(1, 1, p([
                    t(1, TileType::CAMEL, [
                        e(0, 1),
                        e(1, 2),
                        e(2, 1),
                    ])
                ])),
                e(2, 1, p([
                    t(1, TileType::CAMEL, [
                        e(0, 1),
                        e(1, 1),
                        e(2, 2),
                    ])
                ])),
            ]),
        ]);

        $this->assertEquals(
            $expected,
            PossiblePlacement::possiblePlacementFor(0, $truck, $encs));
    }

    public function testFertilePair(): void
    {
        $encs = [ Enclosure::barn(), Enclosure::forTest(1, 3, 1), Enclosure::forTest(2, 2, 2, 5) ];
        $truck = new Truck(1);
        $mother = new Tile(1, TileType::CAMEL_FEMALE);
        $rm = $mother->clone();
        $father = new Tile(2, TileType::CAMEL_MALE);
        $rf = $father->clone();

        $rm->markReproduced();
        $rf->markReproduced();

        $truck->placeTileAt($mother, 1);
        $truck->placeTileAt($father, 2);

        $kid = function($eid, $pos) use(&$rm, &$rf) : Offspring {
            return new Offspring(new Tile($rm->id+300, TileType::CAMEL_KID),
                                 $rm, $rf, new Space($eid, $pos));
        };

        $expected = p([
            t(1, TileType::CAMEL_FEMALE, [
                e(0, 1, p([
                    t(2, TileType::CAMEL_MALE, [
                        e(0, 2),
                        e(1, 1),
                        e(2, 1),
                    ])
                ])),
                e(1, 1, p([
                    t(2, TileType::CAMEL_MALE, [
                        e(0, 1),
                        e(1, 2, null, $kid(1,3)),
                        e(2, 1),
                    ])
                ])),
                e(2, 1, p([
                    t(2, TileType::CAMEL_MALE, [
                        e(0, 1),
                        e(1, 1),
                        e(2, 2, null, $kid(0, 1), MoneyDelta::chargePlayer(100, -5)),
                    ])
                ])),
            ]),
            t(2, TileType::CAMEL_MALE, [
                e(0, 1, p([
                    t(1, TileType::CAMEL_FEMALE, [
                        e(0, 2),
                        e(1, 1),
                        e(2, 1),
                    ])
                ])),
                e(1, 1, p([
                    t(1, TileType::CAMEL_FEMALE, [
                        e(0, 1),
                        e(1, 2, null, $kid(1, 3)),
                        e(2, 1),
                    ])
                ])),
                e(2, 1, p([
                    t(1, TileType::CAMEL_FEMALE, [
                        e(0, 1),
                        e(1, 1),
                        e(2, 2, null, $kid(0, 1), MoneyDelta::chargePlayer(100, -5)),
                    ])
                ])),
            ]),
        ]);

        $this->assertEquals(
            $expected,
            PossiblePlacement::possiblePlacementFor(100, $truck, $encs));
    }
}
