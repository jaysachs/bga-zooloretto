<?php

spl_autoload_register(function ($class) {
    $game = basename(getcwd());
    $prefix = "Bga\\Games\\{$game}\\";
    if (str_starts_with($class, $prefix)) {
        $file = 'modules/php/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (file_exists($file)) {
            require $file;
            return true;
        }
    }
    return false;
});


use Bga\Games\zooloretto\UpgradeDb;
use Bga\Games\zooloretto\Utils\MySQLDb;

$db = "ebd_zooloretto_824479";

$pw = $argv[1];

$msql = new mysqli("localhost", "root", $pw, $db, 3306);

$u = new UpgradeDb(new MySQLDb($msql, $db));

$sql = $u->upgradeSql(0);

$sql = str_replace("DBPREFIX_", $db . ".", $sql);

echo $sql,"\n";
