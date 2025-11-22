<?php

require_once('work/test/module/table/table.game.php');

/*
spl_autoload_register(function ($class) {
    throw new \Throwable("BOO!{$class}");
    $prefix = 'Bga\\Games\\zooloretto\\';
    if (str_starts_with($class, $prefix)) {
        $file = 'modules/php/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (file_exists($file)) {
            require $file;
            return true;
        }
    }
    return false;
});
*/
