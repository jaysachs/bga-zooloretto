<?php

declare(strict_types=1);
use PHPUnit\Framework\TestCase;

use Bga\Games\zooloretto\Utils;

final class UtilsTest extends TestCase
{
    public function testArrayToString_empty(): void
    {
        $this->assertSame('[]', Utils::arrayToString([]));
    }

    public function testArrayToString_singleElement(): void
    {
        $this->assertSame('[abc]', Utils::arrayToString(['abc']));
    }

    public function testArrayToString_multiples(): void
    {
        $this->assertSame('[7,5,2]', Utils::arrayToString([7,5,2]));
    }
}
