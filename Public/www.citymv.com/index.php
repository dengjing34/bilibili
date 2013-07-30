<?php
require_once 'config/config.php';
$site = new Lib\Site();
$site->setRouter(array())->run();
?>