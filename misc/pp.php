<?php

declare(strict_types=1);

require_once('work/test/module/table/table.game.php');

spl_autoload_register(function ($class) {
    if (str_starts_with($class, 'Bga\\Games\\zooloretto\\')) {
        $file = 'modules/php/' . str_replace('\\', '/', substr($class, 21)) . '.php';
        if (file_exists($file)) {
            require $file;
            return true;
        }
    }
    return false;
});

use Bga\Games\zooloretto\Model\Enclosure;
use Bga\Games\zooloretto\Model\PossiblePlacement;
use Bga\Games\zooloretto\Model\Tile;
use Bga\Games\zooloretto\Model\TileType;
use Bga\Games\zooloretto\Model\Truck;

function a2s(string $nesting, mixed $a): string {
    if (is_array($a)) {
        $s = "[\n";
        foreach ($a as $k => $v) {
            $s .= "  " . $nesting . "{$k}:";
            if (is_array($v)) {
                $s .= a2s($nesting . "  ", $v) . "\n";
            }
            else {
                $s .= "{$v}\n";
            }
        }
        $s .= "{$nesting}]";
        return $s;
    } else {
        return "{$nesting}${a}\n";
    }
}

$t = new Truck(1, [new Tile(1, TileType::CAMEL), new Tile(2, TileType::BARROW), new Tile(0, TileType::CAMEL_FEMALE)]);

$e = [];
$e[] = new Enclosure(99, 99, 99); // the barn
$e[] = new Enclosure(10, 2, 2);
$e[] = new Enclosure(11, 1, 1);

$pp = PossiblePlacement::possiblePlacementFor($t, $e);
echo a2s('', $pp->serialize()), "\n";

$t = new Truck(1, [new Tile(1, TileType::COIN), new Tile(2, TileType::COIN), new Tile(0, TileType::BLOCK)]);

$pp = PossiblePlacement::possiblePlacementFor($t, $e);
var_dump($pp->serialize());
echo a2s('', $pp->serialize()), "\n";
