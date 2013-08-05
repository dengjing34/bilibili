<?php
require_once 'config/config.php';
$site = new Lib\Site();
$site->setRouter(array(
    '@^/(?P<categoryEnglishName>[a-z][a-z0-9\-_]*)/?$@' => array('home' => 'category'),
    '@^/(?P<categoryEnglishName>[a-z][a-z0-9\-_]*)/a(?P<id>\d+)\.html$@' => array('home' => 'post'),
    '@^/sitemap_index\.xml$@' => array('sitemap' => 'index'),
    '@^/sitemap_category\.xml$@' => array('sitemap' => 'category'),
    '@^/sitemap_post_(?P<page>\d+)\.xml$@' => array('sitemap' => 'post'),
))->run();
?>