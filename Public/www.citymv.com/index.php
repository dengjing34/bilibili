<?php
require_once 'config/config.php';
$site = new Lib\Site();
$site->setRouter(array(
    '@^/a(?P<id>\d+)\.html$@' => array('test/Sub' => 'ad'),
    '@^/x(?P<id>\d+)\.html$@' => array('Home' => 'indexone'),
))->run();
?>