<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

use Bga\Games\zooloretto\Utils;
use Bga\Games\zooloretto\Model\{Enclosure, PlacementForEnclosure, PlacementsForTruckPos, PossiblePlacement, Space, Tile, TileType, Truck};

function e(int $x, int $y, $z = null, int $o = 0, TileType $t = TileType::EMPTY) {
    return new PlacementForEnclosure($x, $y, $z, $o, $t);
}
function t(int $x, TileType $y, $z = []) { return new PlacementsForTruckPos($x, $y, $z); }
function p($x = []) { return new PossiblePlacement($x); }

final class PossiblePlacementTest extends TestCase
{
    public function testEmptyTruck(): void
    {
        $encs = [ Enclosure::barn(), Enclosure::create(1, 3, 1), Enclosure::create(2, 4, 2) ];
        $truck = new Truck(1);
        $this->assertEquals(p([]), PossiblePlacement::possiblePlacementFor($truck, $encs));
    }

    public function testOne(): void
    {
        $encs = [ Enclosure::barn(), Enclosure::create(1, 3, 1), Enclosure::create(2, 4, 2) ];
        $truck = new Truck(1);
        $truck->placeTileAt($this->tile(TileType::CAMEL), 1);
        $this->assertEquals(
            p([t(1, TileType::CAMEL, [e(0,1), e(1,1), e(2,1)])]),
            PossiblePlacement::possiblePlacementFor($truck, $encs));
    }

    public function testTwoDifferentSpecies(): void
    {
        $encs = [ Enclosure::barn(), Enclosure::create(1, 3, 1), Enclosure::create(2, 4, 2) ];
        $truck = new Truck(1);
        $truck->placeTileAt($this->tile(TileType::CAMEL), 1);
        $truck->placeTileAt($this->tile(TileType::ZEBRA), 2);

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
            PossiblePlacement::possiblePlacementFor($truck, $encs));
    }

    public function testTwoOfSameSpecies(): void
    {
        $encs = [ Enclosure::barn(), Enclosure::create(1, 3, 1), Enclosure::create(2, 4, 2) ];
        $truck = new Truck(1);
        $truck->placeTileAt($this->tile(TileType::CAMEL), 1);
        $truck->placeTileAt($this->tile(TileType::CAMEL_MALE), 2);

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
            PossiblePlacement::possiblePlacementFor($truck, $encs));
    }

    public function testFertilePair(): void
    {
        $encs = [ Enclosure::barn(), Enclosure::create(1, 3, 1), Enclosure::create(2, 4, 2) ];
        $truck = new Truck(1);
        $truck->placeTileAt($this->tile(TileType::CAMEL_FEMALE), 1);
        $truck->placeTileAt($this->tile(TileType::CAMEL_MALE), 2);

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
                    t(1, TileType::CAMEL_FEMALE, [
                        e(0, 2),
                        e(1, 1),
                        e(2, 1),
                    ])
                ])),
                e(1, 1, p([
                    t(1, TileType::CAMEL_FEMALE, [
                        e(0, 1),
                        e(1, 2),
                        e(2, 1),
                    ])
                ])),
                e(2, 1, p([
                    t(1, TileType::CAMEL_FEMALE, [
                        e(0, 1),
                        e(1, 1),
                        e(2, 2),
                    ])
                ])),
            ]),
        ]);

        $this->assertEquals(
            $expected,
            PossiblePlacement::possiblePlacementFor($truck, $encs));
    }

    private int $tile_id = 1;
    private function tile(TileType $type) {
        return new Tile($this->tile_id++, $type);
    }
}
