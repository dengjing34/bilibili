<?php
/* @var $this \Lib\View */
?>
<table class="table table-bordered table-hover">
    <tr>
        <td>系统环境检测</td>
        <td>可用性</td>
    </tr>
    <tr>
        <td>主机名 (IP：端口)：</td>
        <td><?php echo $_SERVER['SERVER_NAME'] . "(" . $_SERVER['SERVER_ADDR'] . ":" . $_SERVER['SERVER_PORT'] . ")" ?></td>
    </tr>
    <tr>
        <td>网站目录</td>
        <td><?php echo APP_PATH ?></td>
    </tr>
    <tr>
        <td>Web服务器：</td>
        <td><?php echo $_SERVER['SERVER_SOFTWARE'] ?></td>
    </tr>
    <tr>
        <td>PHP 运行方式：</td>
        <td><?php echo PHP_SAPI ?></td>
    </tr>
    <tr>
        <td>PHP版本：</td>
        <td><?php echo PHP_VERSION ?></td>
    </tr>
    <tr>
        <td>MySQL 版本：</td>
        <td><?php echo function_exists("mysql_close") ? mysql_get_client_info() : $disabled ?></td>
    </tr>
    <tr>
        <td>Alternative PHP Cache(可选PHP缓存APC)：</td>
        <td><?php echo function_exists("apc_cache_info") && ($apcSmaInfo = apc_sma_info()) ? "total : " . number_format($apcSmaInfo['seg_size'] / 1024 / 1024, 2) . "M" : $disabled ?></td>
    </tr>
    <tr>
        <td>GD库版本：</td>
        <td><?php echo function_exists('gd_info') ? current(gd_info()) : $disabled ?></td>
    </tr>
    <tr>
        <td>最大上传限制：</td>
        <td><?php echo ini_get('file_uploads') ? ini_get('upload_max_filesize') : $disabled ?></td>
    </tr>
    <tr>
        <td>最大执行时间：</td>
        <td><?php echo ini_get('max_execution_time') . "秒" ?></td>
    </tr>
    <tr>
        <td>(curl_init)检测：</td>
        <td><?php echo function_exists('curl_init') ? $enabled : $disabled ?></td>
    </tr>
    <tr>
        <td>(memcached)扩展：</td>
        <td><?php echo class_exists('\Memcached') ? $enabled : $disabled ?></td>
    </tr>
    <tr>
        <td>(redis扩展)：</td>
        <td><?php echo class_exists('\Redis') ? $enabled : $disabled ?></td>
    </tr>
    <tr>
        <td>(Imagick裁剪缩放图片扩展)：</td>
        <td><?php echo class_exists('\Imagick') ? $enabled : $disabled ?></td>
    </tr>
</table>