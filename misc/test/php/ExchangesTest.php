<?php

declare(strict_types=1);

namespace Bga\Games\zooloretto\Model;

use Bga\Games\zooloretto\Utils\Arrays;
use PHPUnit\Framework\TestCase;

final class ExchangesTest extends TestCase
{
    public function testEmpty(): void
    {
        $encs = [ Enclosure::forTest(1, 3, 1), Enclosure::forTest(2, 4, 2) ];
        $barn = Enclosure::barn();
        $this->assertEquals(new Exchanges([1=>[], 2=>[]],[],[]), Exchanges::forEnclosures($encs));
        $encs[0]->placeTile(new Tile(1, TileType::CAMEL), $barn);
        $this->assertEquals(new Exchanges([1 => [1],2=>[]],[],[]), Exchanges::forEnclosures($encs));
    }

    public function testNoBarn_SameAnimal() : void {
        $barn = Enclosure::barn();
        $encs = [ Enclosure::forTest(1, 3, 1), Enclosure::forTest(2, 4, 2) ];
        $encs[0]->placeTile(new Tile(1, TileType::CAMEL), $barn);
        $encs[1]->placeTile(new Tile(2, TileType::CAMEL_FEMALE), $barn);
        $this->assertEquals(new Exchanges([1 => [1], 2 => [1]],[],[]), Exchanges::forEnclosures($encs));
    }

    public function testNoBarn_Simple(): void {
        $barn = Enclosure::barn();
        $encs = [ Enclosure::forTest(1, 3, 1), Enclosure::forTest(2, 4, 2) ];
        $encs[0]->placeTile(new Tile(1, TileType::CAMEL), $barn);
        $encs[1]->placeTile(new Tile(2, TileType::ELEPHANT), $barn);
        $this->assertEquals(new Exchanges([1 => [1], 2 => [1]], [1 => [2], 2 => [1]], []), Exchanges::forEnclosures($encs));

        $encs[0]->placeTile(new Tile(3, TileType::CAMEL_MALE), $barn);
        $this->assertEquals(new Exchanges([1 => [1,2], 2 => [1]], [1 => [2], 2 => [1]], []), Exchanges::forEnclosures($encs));
    }

    public function testWithBarn(): void {
        $encs = [ Enclosure::barn(), Enclosure::forTest(1, 3, 1)];
        $this->assertEquals(new Exchanges([0=>[], 1=>[]],[],[]), Exchanges::forEnclosures($encs));
        $encs[0]->placeTile(new Tile(1, TileType::CAMEL), $encs[0]);
        $encs[1]->placeTile(new Tile(2, TileType::CAMEL_FEMALE), $encs[0]);
        $this->assertEquals(new Exchanges([0=>[1], 1=>[1]],[],[]), Exchanges::forEnclosures($encs));
        $encs[0]->placeTile(new Tile(3, TileType::ELEPHANT), $encs[0]);
        $this->assertEquals(
            new Exchanges([0 => [1,2], 1 => [1]],[],[1 => [new BarnExchange([2])]]),
            Exchanges::forEnclosures($encs));
    }

    public function testWithBarn_moreInEnclosure(): void {
        $encs = [ Enclosure::barn(), Enclosure::forTest(1, 3, 1)];
        $this->assertEquals(new Exchanges([0=>[], 1=>[]],[],[]), Exchanges::forEnclosures($encs));
        $encs[0]->placeTile(new Tile(1, TileType::CAMEL), $encs[0]);
        $encs[1]->placeTile(new Tile(2, TileType::ELEPHANT), $encs[0]);
        $this->assertEquals(new Exchanges([0=>[1], 1=>[1]],[],[1 => [new BarnExchange([1])]]), Exchanges::forEnclosures($encs));
        $encs[1]->placeTile(new Tile(3, TileType::ELEPHANT_FEMALE), $encs[0]);
        $this->assertEquals(
            new Exchanges([0 => [1], 1 => [1,2]],[],[1 => [new BarnExchange([1, 2])]]),
            Exchanges::forEnclosures($encs));
    }

    public function testFullNoOffspring(): void {
        $encs = [ Enclosure::barn(), Enclosure::forTest(1, 3, 1), Enclosure::forTest(2, 4, 2), Enclosure::forTest(3,1,0) ];

        $encs[0]->placeTile(new Tile(1, TileType::CAMEL), $encs[0]);
        $encs[0]->placeTile(new Tile(2, TileType::KIOSK), $encs[0]);
        $encs[0]->placeTile(new Tile(3, TileType::ELEPHANT_FEMALE), $encs[0]);
        $encs[0]->placeTile(new Tile(4, TileType::CAMEL), $encs[0], 5);
        $encs[0]->placeTile(new Tile(5, TileType::LEOPARD), $encs[0], 7);
        $encs[0]->placeTile(new Tile(55, TileType::LEOPARD), $encs[0], 8);

        $encs[1]->placeTile(new Tile(6, TileType::CAMEL_FEMALE), $encs[0]);
        $encs[1]->placeTile(new Tile(7, TileType::CAMEL), $encs[0]);
        $encs[1]->placeTile(new Tile(8, TileType::CAMEL), $encs[0]);

        $encs[2]->placeTile(new Tile(9, TileType::ZEBRA), $encs[0]);

        $encs[3]->placeTile(new Tile(10, TileType::MONKEY), $encs[0]);
        $this->assertEquals(
            new Exchanges(
                [0 => [1,3,5,7,8], 1 => [1,2,3], 2=>[1], 3=>[1]],
                [1 => [2], 2 => [1,3], 3 => [2]],
                [1 => [new BarnExchange([3,4,6]), new BarnExchange([7,8,4])],
                 2 => [new BarnExchange([1,5]), new BarnExchange([3]),new BarnExchange([7,8])],
                 3 => [new BarnExchange([3])]]),
            Exchanges::forEnclosures($encs));
    }

    public function testFullWithOffspring(): void {
        $encs = [ Enclosure::barn(), Enclosure::forTest(1, 3, 1), Enclosure::forTest(2, 4, 2), Enclosure::forTest(3,1,0) ];

        $encs[0]->placeTile(new Tile(1, TileType::CAMEL_MALE), $encs[0]);
        $encs[0]->placeTile(new Tile(2, TileType::KIOSK), $encs[0]);
        $encs[0]->placeTile(new Tile(3, TileType::ELEPHANT_FEMALE), $encs[0]);
        $encs[0]->placeTile(new Tile(4, TileType::CAMEL_FEMALE), $encs[0], 5);
        $encs[0]->placeTile(new Tile(5, TileType::LEOPARD), $encs[0], 7);
        $encs[0]->placeTile(new Tile(55, TileType::LEOPARD), $encs[0], 8);

        $encs[1]->placeTile(new Tile(6, TileType::CAMEL_FEMALE), $encs[0]);
        $encs[1]->placeTile(new Tile(7, TileType::CAMEL), $encs[0]);
        $encs[1]->placeTile(new Tile(8, TileType::CAMEL), $encs[0]);

        $encs[2]->placeTile(new Tile(9, TileType::ZEBRA), $encs[0]);

        $encs[3]->placeTile(new Tile(10, TileType::MONKEY), $encs[0]);
        $this->assertEquals(
            new Exchanges(
                [0 => [1,3,5,7,8], 1 => [1,2,3], 2=>[1], 3=>[1]],
                [1 => [2], 2 => [1,3], 3 => [2]],
                [1 => [new BarnExchange([3,4,6]), new BarnExchange([7,8,4])],
                 2 => [
                    new BarnExchange([1,5]),
                    new BarnExchange([3]),
                    new BarnExchange([7,8])],
                 3 => [new BarnExchange([3])]]),
            Exchanges::forEnclosures($encs));
    }

    public function testFullWithOffspringIntoBarn(): void {
        $encs = [ Enclosure::barn(), Enclosure::forTest(1, 3, 1), Enclosure::forTest(2, 2, 2), Enclosure::forTest(3,1,0) ];

        $encs[0]->placeTile(new Tile(1, TileType::CAMEL_MALE), $encs[0]);
        $encs[0]->placeTile(new Tile(2, TileType::KIOSK), $encs[0]);
        $encs[0]->placeTile(new Tile(3, TileType::ELEPHANT_FEMALE), $encs[0]);
        $encs[0]->placeTile(new Tile(4, TileType::CAMEL_FEMALE), $encs[0], 5);
        $encs[0]->placeTile(new Tile(5, TileType::LEOPARD), $encs[0], 7);
        $encs[0]->placeTile(new Tile(55, TileType::LEOPARD), $encs[0], 8);

        $encs[1]->placeTile(new Tile(6, TileType::CAMEL_FEMALE), $encs[0]);
        $encs[1]->placeTile(new Tile(7, TileType::CAMEL), $encs[0]);
        $encs[1]->placeTile(new Tile(8, TileType::CAMEL), $encs[0]);

        $encs[2]->placeTile(new Tile(9, TileType::ZEBRA), $encs[0]);

        $encs[3]->placeTile(new Tile(10, TileType::MONKEY), $encs[0]);
        $this->assertEquals(
            new Exchanges(
                [0 => [1,3,5,7,8], 1 => [1,2,3], 2=>[1], 3=>[1]],
                [2 => [3], 3 => [2]],
                [1 => [new BarnExchange([3,4,6]), new BarnExchange([7,8,4])],
                 2 => [
                    new BarnExchange([1,5]),
                    new BarnExchange([3]),
                    new BarnExchange([7,8])],
                 3 => [new BarnExchange([3])]]),
            Exchanges::forEnclosures($encs));

        // error_log(Arrays::arrayToString(Exchanges::forEnclosures($encs)->serialize(), true));
    }

    public function testFertilePairInBarn(): void {
        $encs = [ Enclosure::barn(), Enclosure::forTest(1, 5, 1) ];

        $encs[0]->placeTile(new Tile(1, TileType::ELEPHANT), $encs[0]);
        $encs[0]->placeTile(new Tile(2, TileType::ELEPHANT_MALE), $encs[0]);
        $encs[0]->placeTile(new Tile(3, TileType::KANGAROO), $encs[0]);
        $encs[0]->placeTile(new Tile(29, TileType::ELEPHANT_FEMALE), $encs[0]);

        $encs[1]->placeTile(new Tile(4, TileType::KANGAROO), $encs[0]);
        $encs[1]->placeTile(new Tile(5, TileType::KANGAROO_MALE), $encs[0]);
        $encs[1]->placeTile(new Tile(6, TileType::KANGAROO), $encs[0]);
        $encs[1]->placeTile(new Tile(7, TileType::KANGAROO), $encs[0]);
        $encs[1]->placeTile(new Tile(8, TileType::KANGAROO_MALE), $encs[0]);

        $this->assertEquals(
            new Exchanges(
                [0 => [1,2,3,4], 1 => [1,2,3,4,5]],
                [],
                [
                    1 => [new BarnExchange([1,2,4,5,6])]
                ]
            ), Exchanges::forEnclosures($encs));
    }

    public function testBarnAtCapacity(): void {
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

        $this->assertEquals(
            new Exchanges(
                [0 => [1,2,3,4,5,6,7], 3 => [1,2,3]],
                [],
                [3 => [new BarnExchange([1,2,4,5,6,7]), new BarnExchange([3,8,9])]]
            ),
            Exchanges::forEnclosures($encs));
    }

    public function testExceedCapacity(): void {
        $encs = [ Enclosure::barn(), Enclosure::forTest(3,6,1) ];
        $encs[0]->placeTile(new Tile(1, TileType::CAMEL), $encs[0]);
        $encs[0]->placeTile(new Tile(2, TileType::CAMEL_FEMALE), $encs[0]);
        $encs[0]->placeTile(new Tile(3, TileType::FLAMINGO), $encs[0]);
        $encs[0]->placeTile(new Tile(4, TileType::CAMEL), $encs[0]);
        $encs[0]->placeTile(new Tile(5, TileType::CAMEL_FEMALE), $encs[0]);
        $encs[0]->placeTile(new Tile(6, TileType::CAMEL_MALE), $encs[0]);
        $encs[0]->placeTile(new Tile(7, TileType::CAMEL_MALE), $encs[0]);
        $encs[0]->placeTile(new Tile(8, TileType::CAMEL), $encs[0]);

        $encs[1]->placeTile(new Tile(10, TileType::ELEPHANT), $encs[0]);
        $encs[1]->placeTile(new Tile(11, TileType::ELEPHANT), $encs[0]);
        $encs[1]->placeTile(new Tile(12, TileType::ELEPHANT_FEMALE), $encs[0]);

        $this->assertEquals(
            new Exchanges([0 => [1,2,3,4,5,6,7,8], 3 => [1,2,3]], [], [3 => [new BarnExchange([3,9,10])]]),
            Exchanges::forEnclosures($encs));
    }
    /*
    public function testBug(): void {
        $encs = [ Enclosure::barn(), Enclosure::forTest(1, 5, 1), Enclosure::forTest(2, 4, 2), Enclosure::forTest(3,6,1), Enclosure::forTest(4, 5, 1) ];

        $encs[0]->placeTile(new Tile(1, TileType::ELEPHANT), $encs[0]);
        $encs[0]->placeTile(new Tile(2, TileType::ELEPHANT_MALE), $encs[0]);
        $encs[0]->placeTile(new Tile(3, TileType::KANGAROO), $encs[0]);
        $encs[0]->placeTile(new Tile(29, TileType::ELEPHANT_FEMALE), $encs[0]);

        $encs[1]->placeTile(new Tile(4, TileType::KANGAROO), $encs[0]);
        $encs[1]->placeTile(new Tile(5, TileType::KANGAROO_MALE), $encs[0]);
        $encs[1]->placeTile(new Tile(6, TileType::KANGAROO), $encs[0]);
        $encs[1]->placeTile(new Tile(7, TileType::KANGAROO), $encs[0]);
        $encs[1]->placeTile(new Tile(8, TileType::KANGAROO_MALE), $encs[0]);

        $encs[2]->placeTile(new Tile(9, TileType::LEOPARD_FEMALE), $encs[0]);

        $t = new Tile(11, TileType::FLAMINGO_FEMALE);
        $t->markReproduced();
        $encs[3]->placeTile($t, $encs[0]);
        $t = new Tile(14, TileType::FLAMINGO_MALE);
        $t->markReproduced();
        $encs[3]->placeTile($t, $encs[0]);
        $encs[3]->placeTile(new Tile(11014, TileType::FLAMINGO_KID), $encs[0]);
        $encs[3]->placeTile(new Tile(12, TileType::FLAMINGO), $encs[0]);
        $encs[3]->placeTile(new Tile(13, TileType::FLAMINGO), $encs[0]);
        $encs[3]->placeTile(new Tile(20, TileType::FLAMINGO_FEMALE), $encs[0]);

        $encs[4]->placeTile(new Tile(15, TileType::CAMEL), $encs[0]);
        $encs[4]->placeTile(new Tile(16, TileType::CAMEL), $encs[0]);
        $encs[4]->placeTile(new Tile(17, TileType::CAMEL_MALE), $encs[0]);
        $encs[4]->placeTile(new Tile(18, TileType::CAMEL), $encs[0]);
        $encs[4]->placeTile(new Tile(19, TileType::CAMEL), $encs[0]);

        $x = Exchanges::forEnclosures($encs);
    }
        */
}