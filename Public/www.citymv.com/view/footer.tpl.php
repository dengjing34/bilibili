<?php
/*@var $this \Lib\View*/
?>


        </div>
<?php
if (isset($appendStatic['js'])) {
    foreach ($appendStatic['js'] as $eachJs) {
        echo '<script type="text/javascript" src="' . $this->url()->jsUlr($eachJs) . '"/></script>' . "\n";
    }
}
?>
    </body>
</html>