<?php
define('CONFIG_PATH', __DIR__ . DIRECTORY_SEPARATOR);
define('CONTROLLER_PATH', realpath(CONFIG_PATH . '../controller') . DIRECTORY_SEPARATOR);
define('APP_PATH', realpath(CONFIG_PATH . '../') . DIRECTORY_SEPARATOR);
define('BOOT_PATH', realpath(CONFIG_PATH . '../../../Boot') . DIRECTORY_SEPARATOR);
/**
 * citymv数据库名
 */
define('MYSQL_DBNAME_CITYMV', 'citymv');
/**
 * citymv_posts在solr的core名称
 */
define('SOLR_CORE_JM_CITYMV_POSTS', 'citymv_posts');
/**
 * 上传文件路径(根目录)
 */
define('UPLOAD_PATH', '/files/');
/**
 * 编辑器上传图片需要做的裁剪和缩略图配置
 */
define('IMAGICK_EDITOR', 'imagick_editor');
/**
 * 各台webserver的ip地址,不在这些ip范围的不加载统计代码
 */
define('SERVER_IP_LIST', 'server_ip_list');
require_once BOOT_PATH . 'bootstrap.php';
?>
