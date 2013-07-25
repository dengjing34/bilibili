<?php
define('CONFIG_PATH', __DIR__ . DIRECTORY_SEPARATOR);
define('CONTROLLER_PATH', realpath(CONFIG_PATH . '../controller') . DIRECTORY_SEPARATOR);
define('APP_PATH', realpath(CONFIG_PATH . '../') . DIRECTORY_SEPARATOR);
define('BOOT_PATH', realpath(CONFIG_PATH . '../../../Boot') . DIRECTORY_SEPARATOR);
define('MYSQL_DBNAME_CITYMV', 'citymv');
define('MYSQL_DBNAME_KB', 'koubei_import2');
define('SOLR_CORE_JM_PRODUCTS', 'jm_products');
define('SOLR_CORE_JM_CITYMV_POSTS', 'citymv_posts');
require_once BOOT_PATH . 'bootstrap.php';
?>
