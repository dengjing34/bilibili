<?php
/*@var $this \Lib\View*/
/*@var $post \Lib\Mysql\Posts*/
$url = $this->url();
foreach ($pageResult['docs'] as $post) {
    echo "<a href=\"{$url->link($post->postUrl())}\">{$post->title}</a><br />";
}
var_dump($pageResult);
?>