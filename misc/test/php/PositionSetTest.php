<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

use Bga\Games\zooloretto\Model\{PositionSet};

final class PositionSetTest extends TestCase
{
    public function testEquals(): void
    {
        $this->assertEquals(new PositionSet(2,[1,2,3]), new PositionSet(2, [1,2,3]));

        $this->assertFalse(new PositionSet(3, [1]) == new PositionSet(2, [1]));
        $this->assertFalse(new PositionSet(2, [2]) == new PositionSet(2, [1]));
        $this->assertFalse(new PositionSet(2, [1,2]) == new PositionSet(2, [1]));
    }

    public function testToString(): void {
        $this->assertEquals("PositionSet(2,[3])", new PositionSet(2, [3]));
        $this->assertEquals("PositionSet(2,[5,12,13])", new PositionSet(2, [5,12,13]));
    }
}
