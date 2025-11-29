<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

use Bga\Games\zooloretto\Model\{Enclosure, PositionSet, PossibleExchange, Space, Tile, TileType};

final class PossibleExchangeTest extends TestCase
{
    public function testEmpty(): void
    {
        $encs = [ Enclosure::forTest(1, 3, 1), Enclosure::forTest(2, 4, 2) ];
        $this->assertEquals([], PossibleExchange::getPossibleExchanges($encs));
        $encs[0]->placeTile(new Tile(1, TileType::CAMEL));
        $this->assertEquals([], PossibleExchange::getPossibleExchanges($encs));
    }

    private int $tile_id = 1;
    private function tile(TileType $type) {
        return new Tile($this->tile_id++, $type);
    }

    private function pe(int $src_id, array $src_pos, int $dest_id, array $dest_pos, array $children = []): PossibleExchange {
        return new PossibleExchange(new PositionSet($src_id, $src_pos), new PositionSet($dest_id, $dest_pos), $children);
    }

    public function testNoBarn_Simple(): void {
        $encs = [ Enclosure::forTest(1, 3, 1), Enclosure::forTest(2, 4, 2) ];
        $encs[0]->placeTile(new Tile(1, TileType::CAMEL));
        $encs[1]->placeTile(new Tile(2, TileType::ELEPHANT));
        $this->assertEquals([
            $this->pe(1, [1], 2, [1]),
            $this->pe(2, [1], 1, [1])
        ], PossibleExchange::getPossibleExchanges($encs));

        $encs[0]->placeTile(new Tile(3, TileType::CAMEL_MALE));
        $this->assertEquals([
            $this->pe(1, [1, 2], 2, [1,2]),
            $this->pe(2, [1, 2], 1, [1, 2])
        ], PossibleExchange::getPossibleExchanges($encs));
    }
    public function testNoBarn_SameAnimal() : void {
        $encs = [ Enclosure::forTest(1, 3, 1), Enclosure::forTest(2, 4, 2) ];
        $encs[0]->placeTile(new Tile(1, TileType::CAMEL));
        $encs[1]->placeTile(new Tile(2, TileType::CAMEL_FEMALE));
        $this->assertEquals([], PossibleExchange::getPossibleExchanges($encs));
    }

    public function testWithBarn(): void {
        $encs = [ Enclosure::barn(), Enclosure::forTest(1, 3, 1)];
        $this->assertEquals([], PossibleExchange::getPossibleExchanges($encs));
        $encs[0]->placeTile(new Tile(1, TileType::CAMEL));
        $encs[1]->placeTile(new Tile(2, TileType::CAMEL_FEMALE));
        $this->assertEquals([], PossibleExchange::getPossibleExchanges($encs));
        $encs[0]->placeTile(new Tile(3, TileType::ELEPHANT));
        $this->assertEquals([
            $this->pe(1, [1], 0, [2])
        ], PossibleExchange::getPossibleExchanges($encs));
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
        ], PossibleExchange::getPossibleExchanges($encs));

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
            $this->pe(1, [1,2,3], 2, [1,2,3], [new Space(2, 4)]),
            $this->pe(2, [1,2], 0, [1,5], [new Space(2, 3)]),
            $this->pe(2, [1], 0, [3]),
            $this->pe(2, [1], 0, [7]),
            $this->pe(2, [1,2,3], 1, [1,2,3], [new Space(2, 4)]),
        ], PossibleExchange::getPossibleExchanges($encs));

    }
}
