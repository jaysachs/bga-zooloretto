<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

use Bga\Games\zooloretto\Model\{SwapGroup};

final class SwapGroupTest extends TestCase
{
    public function testEquals(): void
    {
        $this->assertEquals(new SwapGroup(2,[1,2,3]), new SwapGroup(2, [1,2,3]));

        $this->assertFalse(new SwapGroup(3, [1]) == new SwapGroup(2, [1]));
        $this->assertFalse(new SwapGroup(2, [2]) == new SwapGroup(2, [1]));
        $this->assertFalse(new SwapGroup(2, [1,2]) == new SwapGroup(2, [1]));
    }

    public function testToString(): void {
        $this->assertEquals("SwapGroup(2,[3])", new SwapGroup(2, [3]));
        $this->assertEquals("SwapGroup(2,[5,12,13])", new SwapGroup(2, [5,12,13]));
    }
}
