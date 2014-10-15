<?php
$loader = require __DIR__ . '/vendor/autoload.php';
$loader->addPsr4('PommProject\\Foundation\\Test\\', __DIR__.'/vendor/pomm-project/foundation/sources/tests/');
$file = __DIR__.'/sources/tests/config.php';

if (file_exists($file)) {
    // custom configuration
    require $file;
} else {
    // we are using travis configuration by default
    require __DIR__.'/sources/tests/config.travis.php';
}

