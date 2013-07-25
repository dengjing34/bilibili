<?php
if (!defined('BOOT_PATH')) {
    define('BOOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}
define('SYS_ROOT', realpath(BOOT_PATH . '../') . DIRECTORY_SEPARATOR);
require_once SYS_ROOT . 'Lib/AutoLoader.php';
Lib\AutoLoader::instance()->register(SYS_ROOT, 'loadByNameSpace');
Lib\AutoLoader::instance()->register(APP_PATH, 'loadByControllerName');
define('START_TIME', microtime(true));
?>
