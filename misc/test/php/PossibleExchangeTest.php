<?php

declare(strict_types=1);

namespace Bga\Games\zooloretto\Model;

use PHPUnit\Framework\TestCase;

final class PossibleExchangeTest extends TestCase
{
    private Moneys $moneys;

    public function setUp(): void {
        $this->moneys = new Moneys(0);
    }

    public function testEmpty(): void
    {
        $encs = [ Enclosure::forTest(1, 3, 1), Enclosure::forTest(2, 4, 2) ];
        $this->assertEquals([], PossibleExchange::getPossibleExchanges($encs, $this->moneys));
        $encs[0]->placeTile(new Tile(1, TileType::CAMEL));
        $this->assertEquals([], PossibleExchange::getPossibleExchanges($encs, $this->moneys));
    }

    private int $tile_id = 1;
    private function tile(TileType $type) {
        return new Tile($this->tile_id++, $type);
    }

    private function pe(int $src_id, array $src_pos, int $dest_id, array $dest_pos, array $children = []): PossibleExchange {
        return new PossibleExchange($src_id, $src_pos, $dest_id, $dest_pos, $children, $this->moneys);
    }

    public function testNoBarn_Simple(): void {
        $encs = [ Enclosure::forTest(1, 3, 1), Enclosure::forTest(2, 4, 2) ];
        $encs[0]->placeTile(new Tile(1, TileType::CAMEL));
        $encs[1]->placeTile(new Tile(2, TileType::ELEPHANT));
        $this->assertEquals([
            $this->pe(1, [1], 2, [1]),
            $this->pe(2, [1], 1, [1])
        ], PossibleExchange::getPossibleExchanges($encs, $this->moneys));

        $encs[0]->placeTile(new Tile(3, TileType::CAMEL_MALE));
        $this->assertEquals([
            $this->pe(1, [1, 2], 2, [1,2]),
            $this->pe(2, [1, 2], 1, [1, 2])
        ], PossibleExchange::getPossibleExchanges($encs, $this->moneys));
    }
    public function testNoBarn_SameAnimal() : void {
        $encs = [ Enclosure::forTest(1, 3, 1), Enclosure::forTest(2, 4, 2) ];
        $encs[0]->placeTile(new Tile(1, TileType::CAMEL));
        $encs[1]->placeTile(new Tile(2, TileType::CAMEL_FEMALE));
        $this->assertEquals([], PossibleExchange::getPossibleExchanges($encs, $this->moneys));
    }

    public function testWithBarn(): void {
        $encs = [ Enclosure::barn(), Enclosure::forTest(1, 3, 1)];
        $this->assertEquals([], PossibleExchange::getPossibleExchanges($encs, $this->moneys));
        $encs[0]->placeTile(new Tile(1, TileType::CAMEL));
        $encs[1]->placeTile(new Tile(2, TileType::CAMEL_FEMALE));
        $this->assertEquals([], PossibleExchange::getPossibleExchanges($encs, $this->moneys));
        $encs[0]->placeTile(new Tile(3, TileType::ELEPHANT));
        $this->assertEquals([
            $this->pe(1, [1], 0, [2])
        ], PossibleExchange::getPossibleExchanges($encs, $this->moneys));
    }

    public function testFullNoOffspring(): void {
        $encs = [ Enclosure::barn(), Enclosure::forTest(1, 3, 1), Enclosure::forTest(2, 4, 2) ];

        $encs[0]->placeTile(new Tile(1, TileType::CAMEL));
        $encs[0]->placeTile(new Tile(2, TileType::KIOSK));
        $encs[0]->placeTile(new Tile(3, TileType::ELEPHANT_FEMALE));
        $encs[0]->placeTile(new Tile(4, TileType::CAMEL), 5);
        $encs[0]->placeTile(new Tile(5, TileType::LEOPARD), 7);

        $encs[1]->placeTile(new Tile(6, TileType::CAMEL_FEMALE));
        $encs[1]->placeTile(new Tile(7, TileType::CAMEL));
        $encs[1]->placeTile(new Tile(8, TileType::CAMEL));

        $encs[2]->placeTile(new Tile(9, TileType::ZEBRA));
        $this->assertEquals([
            $this->pe(1, [1,2,3], 0, [3,4,6]),
            $this->pe(1, [1,2,3], 0, [7,4,6]),
            $this->pe(1, [1,2,3], 2, [1,2,3]),
            $this->pe(2, [1,2], 0, [1,5]),
            $this->pe(2, [1], 0, [3]),
            $this->pe(2, [1], 0, [7]),
            $this->pe(2, [1,2,3], 1, [1,2,3]),
        ], PossibleExchange::getPossibleExchanges($encs, $this->moneys));

    }

    public function testFullWithOffspring(): void {
        $encs = [ Enclosure::barn(), Enclosure::forTest(1, 3, 1), Enclosure::forTest(2, 4, 2) ];

        $encs[0]->placeTile(new Tile(1, TileType::CAMEL_FEMALE));
        $encs[0]->placeTile(new Tile(2, TileType::KIOSK));
        $encs[0]->placeTile(new Tile(3, TileType::ELEPHANT_FEMALE));
        $encs[0]->placeTile(new Tile(4, TileType::CAMEL_MALE), 5);
        $encs[0]->placeTile(new Tile(5, TileType::LEOPARD), 7);

        $encs[1]->placeTile(new Tile(6, TileType::CAMEL_FEMALE));
        $encs[1]->placeTile(new Tile(7, TileType::CAMEL));
        $encs[1]->placeTile(new Tile(8, TileType::CAMEL_MALE));

        $encs[2]->placeTile(new Tile(9, TileType::ZEBRA));
        $this->assertEquals([
            $this->pe(1, [1,2,3], 0, [3,4,6]),
            $this->pe(1, [1,2,3], 0, [7,4,6]),
            $this->pe(1, [1,2,3], 2, [1,2,3],
                [new Offspring(
                    new PlacedTile(new Tile(306, TileType::CAMEL_KID), new Space(2, 4)),
                    new Tile(6, TileType::CAMEL_FEMALE_R),
                    new Tile(8, TileType::CAMEL_MALE_R),
                    true)]),
            $this->pe(2, [1,2], 0, [1,5],
                [new Offspring(
                    new PlacedTile(new Tile(301, TileType::CAMEL_KID),new Space(2, 3)),
                    new Tile(1, TileType::CAMEL_FEMALE_R),
                    new Tile(4, TileType::CAMEL_MALE_R),
                    false)]),
            $this->pe(2, [1], 0, [3]),
            $this->pe(2, [1], 0, [7]),
            $this->pe(2, [1,2,3], 1, [1,2,3],
                [new Offspring(
                    new PlacedTile(new Tile(306, TileType::CAMEL_KID),new Space(2, 4)),
                    new Tile(6, TileType::CAMEL_FEMALE_R),
                    new Tile(8, TileType::CAMEL_MALE_R),
                    true)]),
        ], PossibleExchange::getPossibleExchanges($encs, $this->moneys));

    }
}
