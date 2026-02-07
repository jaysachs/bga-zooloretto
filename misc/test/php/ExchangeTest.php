<?php

declare(strict_types=1);

namespace Bga\Games\zoolorettoalpha\Model;

use PHPUnit\Framework\TestCase;

final class ExchangeTest extends TestCase
{
    private Exchange $none;

    protected function setUp(): void {
        $this->none = new Exchange([], []);
    }

    public function testEmpty(): void
    {
        $encs = [ Enclosure::forTest(1, 3, 1), Enclosure::forTest(2, 4, 2) ];
        $this->assertEquals($this->none, Exchange::forEnclosures($encs));
        $encs[0]->placeTile(new Tile(1, TileType::CAMEL));
        $this->assertEquals($this->none, Exchange::forEnclosures($encs));
    }

    public function testNoBarn_SameAnimal() : void {
        $encs = [ Enclosure::forTest(1, 3, 1), Enclosure::forTest(2, 4, 2) ];
        $encs[0]->placeTile(new Tile(1, TileType::CAMEL));
        $encs[1]->placeTile(new Tile(2, TileType::CAMEL_FEMALE));
        $this->assertEquals($this->none, Exchange::forEnclosures($encs));
    }

    public function testNoBarn_Simple(): void {
        $encs = [ Enclosure::forTest(1, 3, 1), Enclosure::forTest(2, 4, 2) ];
        $encs[0]->placeTile(new Tile(1, TileType::CAMEL));
        $encs[1]->placeTile(new Tile(2, TileType::ELEPHANT));
        $this->assertEquals(new Exchange([1 => [2], 2 => [1]], []), Exchange::forEnclosures($encs));

        $encs[0]->placeTile(new Tile(3, TileType::CAMEL_MALE));
        $this->assertEquals(new Exchange([1 => [2], 2 => [1]], []), Exchange::forEnclosures($encs));
    }

    public function testWithBarn(): void {
        $encs = [ Enclosure::barn(), Enclosure::forTest(1, 3, 1)];
        $this->assertEquals($this->none, Exchange::forEnclosures($encs));
        $encs[0]->placeTile(new Tile(1, TileType::CAMEL));
        $encs[1]->placeTile(new Tile(2, TileType::CAMEL_FEMALE));
        $this->assertEquals($this->none, Exchange::forEnclosures($encs));
        $encs[0]->placeTile(new Tile(3, TileType::ELEPHANT));
        $this->assertEquals(
            new Exchange([],[1 => [new BarnExchange([2])]]),
            Exchange::forEnclosures($encs));
    }

    public function testFullNoOffspring(): void {
        $encs = [ Enclosure::barn(), Enclosure::forTest(1, 3, 1), Enclosure::forTest(2, 4, 2), Enclosure::forTest(3,1,0) ];

        $encs[0]->placeTile(new Tile(1, TileType::CAMEL));
        $encs[0]->placeTile(new Tile(2, TileType::KIOSK));
        $encs[0]->placeTile(new Tile(3, TileType::ELEPHANT_FEMALE));
        $encs[0]->placeTile(new Tile(4, TileType::CAMEL), 5);
        $encs[0]->placeTile(new Tile(5, TileType::LEOPARD), 7);
        $encs[0]->placeTile(new Tile(55, TileType::LEOPARD), 8);

        $encs[1]->placeTile(new Tile(6, TileType::CAMEL_FEMALE));
        $encs[1]->placeTile(new Tile(7, TileType::CAMEL));
        $encs[1]->placeTile(new Tile(8, TileType::CAMEL));

        $encs[2]->placeTile(new Tile(9, TileType::ZEBRA));

        $encs[3]->placeTile(new Tile(10, TileType::MONKEY));
        $actual = Exchange::forEnclosures($encs);
        var_dump($actual);
        $this->assertEquals(
            new Exchange(
                [1 => [2], 2 => [1,3], 3 => [2]],
                [1 => [new BarnExchange([3]), new BarnExchange([7,8])],
                 2 => [new BarnExchange([1,5]), new BarnExchange([3]),new BarnExchange([7,8])],
                 3 => [new BarnExchange([3])]]),
            Exchange::forEnclosures($encs));
    }

    public function testFullWithOffspring(): void {
        $encs = [ Enclosure::barn(), Enclosure::forTest(1, 3, 1), Enclosure::forTest(2, 4, 2), Enclosure::forTest(3,1,0) ];

        $encs[0]->placeTile(new Tile(1, TileType::CAMEL_MALE));
        $encs[0]->placeTile(new Tile(2, TileType::KIOSK));
        $encs[0]->placeTile(new Tile(3, TileType::ELEPHANT_FEMALE));
        $encs[0]->placeTile(new Tile(4, TileType::CAMEL_FEMALE), 5);
        $encs[0]->placeTile(new Tile(5, TileType::LEOPARD), 7);
        $encs[0]->placeTile(new Tile(55, TileType::LEOPARD), 8);

        $encs[1]->placeTile(new Tile(6, TileType::CAMEL_FEMALE));
        $encs[1]->placeTile(new Tile(7, TileType::CAMEL));
        $encs[1]->placeTile(new Tile(8, TileType::CAMEL));

        $encs[2]->placeTile(new Tile(9, TileType::ZEBRA));

        $encs[3]->placeTile(new Tile(10, TileType::MONKEY));
        $actual = Exchange::forEnclosures($encs);
        var_dump($actual);
        $this->assertEquals(
            new Exchange(
                [1 => [2], 2 => [1,3], 3 => [2]],
                [1 => [new BarnExchange([3]), new BarnExchange([7,8])],
                 2 => [
                    new BarnExchange([1,5],
                        new Offspring(
                            new PlacedTile(new Tile(40001, TileType::CAMEL_KID),new Space(2, 3), false),
                            new Tile(4, TileType::CAMEL_FEMALE, true),
                            new Tile(1, TileType::CAMEL_MALE, true))),
                    new BarnExchange([3]),
                    new BarnExchange([7,8])],
                 3 => [new BarnExchange([3])]]),
            Exchange::forEnclosures($encs));
    }

    public function testFullWithOffspringIntoBarn(): void {
        $encs = [ Enclosure::barn(), Enclosure::forTest(1, 3, 1), Enclosure::forTest(2, 2, 2), Enclosure::forTest(3,1,0) ];

        $encs[0]->placeTile(new Tile(1, TileType::CAMEL_MALE));
        $encs[0]->placeTile(new Tile(2, TileType::KIOSK));
        $encs[0]->placeTile(new Tile(3, TileType::ELEPHANT_FEMALE));
        $encs[0]->placeTile(new Tile(4, TileType::CAMEL_FEMALE), 5);
        $encs[0]->placeTile(new Tile(5, TileType::LEOPARD), 7);
        $encs[0]->placeTile(new Tile(55, TileType::LEOPARD), 8);

        $encs[1]->placeTile(new Tile(6, TileType::CAMEL_FEMALE));
        $encs[1]->placeTile(new Tile(7, TileType::CAMEL));
        $encs[1]->placeTile(new Tile(8, TileType::CAMEL));

        $encs[2]->placeTile(new Tile(9, TileType::ZEBRA));

        $encs[3]->placeTile(new Tile(10, TileType::MONKEY));
        $actual = Exchange::forEnclosures($encs);
        var_dump($actual);
        $this->assertEquals(
            new Exchange(
                [2 => [3], 3 => [2]],
                [1 => [new BarnExchange([3]), new BarnExchange([7,8])],
                 2 => [
                    new BarnExchange([1,5],
                        new Offspring(
                            new PlacedTile(new Tile(40001, TileType::CAMEL_KID),new Space(0, 4), false),
                            new Tile(4, TileType::CAMEL_FEMALE, true),
                            new Tile(1, TileType::CAMEL_MALE, true))),
                    new BarnExchange([3]),
                    new BarnExchange([7,8])],
                 3 => [new BarnExchange([3])]]),
            Exchange::forEnclosures($encs));
    }
}