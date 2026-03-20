<?php

spl_autoload_register(function (string $class) {
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


function fixup(string $s): string {
    return $s; // return str_replace("TINYINT", "INT", str_replace("tinyint","int",$s));
}

use Bga\Games\zooloretto\UpgradeDb;
use Bga\Games\zooloretto\Utils\MySQLDb;

$opts = getopt("", ["db:", "user:", "port:", "timestamp:", "dryrun", "seedfile:","drop" ]);

$host = isset($opts["host"]) ? $opts["host"] : "localhost";
$db = $opts["db"];
$ts = intval($opts["timestamp"]);
$user = $opts["user"];
$port = isset($opts["port"]) ? $opts["port"] : 3306;
$dryrun = isset($opts["dryrun"]);
$seedfile = isset($opts["seedfile"]) ? $opts["seedfile"] : null;
$drop = isset($opts["drop"]);

if (!$db || !$user) {
    die("Must supply --db and --user");
}

echo "DB password: ";
system('stty -echo');
$pw = trim(fgets(STDIN));
system('stty echo');
// add a new line since the users CR didn't echo
echo "\n";


mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$mysqli = new mysqli($host, $user, $pw, null, $port);

if ($seedfile) {
    echo "Initializing with seedfile\n";
    $seedsql = fixup(file_get_contents($seedfile));
    if ($dryrun) {
        if ($drop) {
            echo "DROP DATABASE $db\n";
        }
        echo "$seedsql\n";
    } else {
        if ($drop) {
            echo "Dropping db $db\n";
            $mysqli->query("DROP DATABASE $db;");
        }
        echo "Loading seedfile\n";
        $mysqli->multi_query($seedsql);
        do {
            $mysqli->store_result();
            if ($mysqli->more_results()) { }
        } while ($mysqli->next_result());
        // echo $mysqli->error,"\n";
    }
}

echo "Upgrading db\n";
$u = new UpgradeDb(new MySQLDb($mysqli, $db));

/** @var array{sql:list<string>,state_id:int}|null */
$upgrade = $u->upgradeSql($ts, 2);
foreach ($upgrade["sql"] as $sql) {
    $s2 = str_replace("DBPREFIX_", $db . ".", fixup($sql));
    echo "\n$s2;\n";
    if (!$dryrun) {
        if ($mysqli->query($s2)) {
            echo "  ===> SUCCESS\n";
        } else {
            die("$mysqli->error");
        }
    }
}
echo "State change to {$upgrade['state_id']}\n";
