<?php

spl_autoload_register(function ($class) {

    $file = __DIR__ . '/' . $class . '.php';
    if (file_exists($file)) {
        include_once $file;
        return true;
    }
    $file = __DIR__ . '/core/vendor/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        include_once $file;
        return true;
    }

    // $file = __DIR__ . '/core/' . str_replace('Qwwwest\\Namaskar\\', '', $class) . '.php';
    $class = str_replace('Qwwwest\\Namaskar\\', '', $class);
    $file = __DIR__ . '/core/' . str_replace('\\', '/', $class) . '.php';

    if (file_exists($file)) {
        include_once $file;
        return true;
    }

    $class = str_replace('App\\', '', $class);
    $file = __DIR__ . '/src/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        include_once $file;
        return true;
    }


    die($file . " not found.'$class' Me so sorry.");

});

//require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/core/helpers.php';


debug(false);



try {
    $kernel = new Qwwwest\Namaskar\Kernel();
    $response = $kernel->handle();
    $response->send();

} catch (Exception $e) {
    echo $e->getMessage();
    echo debug();
    die();
}

//echo debug();