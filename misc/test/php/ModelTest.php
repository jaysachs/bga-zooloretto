<?php

declare(strict_types=1);

namespace Bga\Games\zoolorettoalpha\Model;

use Bga\Games\zoolorettoalpha\Utils\TestDb;
use PHPUnit\Framework\TestCase;

function e(int $x, int $y, ?Offspring $offspring = null, ?Moneys $moneyDelta = null): Destination {
    return new Destination(new Space($x, $y), $offspring, $moneyDelta);
}

final class ModelTest extends TestCase
{
    private Model $model;
    protected function setUp(): void {
        $this->model = new Model(1, new PersistentStore(new TestDb()));
    }

    public function testEmptyTruck(): void
    {
        $encs = [ Enclosure::barn(), Enclosure::forTest(1, 3, 1), Enclosure::forTest(2, 4, 2) ];
        $truck = new Truck(1);
        $this->assertEquals([], $this->model->possibleDeliveriesFor($truck, $encs));
    }

    public function testOne(): void
    {
        $encs = [ Enclosure::barn(), Enclosure::forTest(1, 3, 1), Enclosure::forTest(2, 4, 2) ];
        $truck = new Truck(1);
        $truck->placeTileAt( new Tile(1, TileType::CAMEL), 1);
        $this->assertEquals(
            [1 => [e(0,1), e(1,1), e(2,1)]],
            $this->model->possibleDeliveriesFor($truck, $encs));
    }

    public function testTwoDifferentSpecies(): void
    {
        $encs = [ Enclosure::barn(), Enclosure::forTest(1, 3, 1), Enclosure::forTest(2, 4, 2) ];
        $truck = new Truck(1);
        $truck->placeTileAt(new Tile(1, TileType::CAMEL), 1);
        $truck->placeTileAt(new Tile(2, TileType::ZEBRA), 2);

        $expected = [
            1 => [e(0, 1),e(1, 1),e(2, 1)],
            2 => [e(0, 1),e(1, 1),e(2, 1)],
        ];

        $this->assertEquals(
            $expected,
            $this->model->possibleDeliveriesFor($truck, $encs));
    }

    public function testTwoOfSameSpecies(): void
    {
        $encs = [ Enclosure::barn(), Enclosure::forTest(1, 3, 1), Enclosure::forTest(2, 4, 2) ];
        $truck = new Truck(1);
        $truck->placeTileAt(new Tile(1, TileType::CAMEL), 1);
        $truck->placeTileAt(new Tile(2, TileType::CAMEL_MALE), 2);

        $expected = [
            1 => [e(0, 1), e(1, 1), e(2, 1)],
            2 => [e(0, 1), e(1, 1), e(2, 1)],
        ];

        $this->assertEquals(
            $expected,
            $this->model->possibleDeliveriesFor($truck, $encs));
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
            1 => [e(0, 1), e(1, 1), e(2, 1)],
            2 => [e(0, 1), e(1, 1), e(2, 1)],
        ];

        $this->assertEquals(
            $expected,
            $this->model->possibleDeliveriesFor($truck, $encs));
    }
}
