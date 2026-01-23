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

$opts = getopt("", ["db:", "user:", "port:", "timestamp:", "dryrun" ]);

$host = isset($opts["host"]) ? $opts["host"] : "localhost";
$db = $opts["db"];
$ts = intval($opts["timestamp"]);
$user = $opts["user"];
$port = isset($opts["port"]) ? $opts["port"] : 3306;
$dryrun = isset($opts["dryrun"]);

if (!$db || !$user) {
    echo "Must supply --db and --user";
}

echo "DB password: ";
system('stty -echo');
$pw = trim(fgets(STDIN));
system('stty echo');
// add a new line since the users CR didn't echo
echo "\n";


mysqli_report(MYSQLI_REPORT_STRICT);

$mysqli = new mysqli($host, $user, $pw, $db, $port);
$u = new UpgradeDb(new MySQLDb($mysqli, $db));

foreach ($u->upgradeSql(0) as $sql) {
    $s2 = str_replace("DBPREFIX_", $db . ".", $sql);
    echo "\n$s2;\n";
    if (!$dryrun) {
        if ($mysqli->query($s2)) {
            echo "  ===> SUCCESS\n";
        } else {
            echo "\n  **** ERROR: ", $mysqli->error,"\n";
            break;
        }
    }
}
