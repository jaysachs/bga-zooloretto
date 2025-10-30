<?php

// require_once('work/test/module/table/table.game.php');

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
