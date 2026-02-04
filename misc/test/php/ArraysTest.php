<?php

declare(strict_types=1);
use PHPUnit\Framework\TestCase;

use Bga\Games\zoolorettoalpha\Utils;
use Bga\Games\zoolorettoalpha\Utils\Arrays;

final class ArraysTest extends TestCase
{
    public function testArrayToString_empty(): void
    {
        $this->assertSame('[]', Arrays::arrayToString([]));
    }

    public function testArrayToString_singleElement(): void
    {
        $this->assertSame('[abc]', Arrays::arrayToString(['abc']));
    }

    public function testArrayToString_multiples(): void
    {
        $this->assertSame('[7,5,2]', Arrays::arrayToString([7,5,2]));
    }
}
