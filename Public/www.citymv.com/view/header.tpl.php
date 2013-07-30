<?php
/*@var $this \Lib\View*/
?>
<!DOCTYPE html>
<html>
    <head>
        <title>demo1</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <?php
        if (isset($prependStatic['css'])) {
            foreach ($prependStatic['css'] as $eachCss) {
                echo '<link rel="stylesheet" href="' . $this->url()->cssUrl($eachCss) . '">' . "\n";
            }
        }
        if (isset($prependStatic['js'])) {
            foreach ($prependStatic['js'] as $eachJs) {
                echo '<script type="text/javascript" src="' . $this->url()->jsUlr($eachJs) . '"></script>' . "\n";
            }
        }
        ?>
    </head>

    <body>
        <div class="container-fluid b">
            