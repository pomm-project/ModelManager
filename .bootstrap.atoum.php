<?php
define('PROJECT_DIR', dirname(__FILE__));
$loader = require PROJECT_DIR . '/vendor/autoload.php';
$loader->addPsr4("PommProject\\Foundation\\Test\\", PROJECT_DIR."/vendor/pomm-project/foundation/sources/tests");
require PROJECT_DIR.'/sources/tests/config.php';
