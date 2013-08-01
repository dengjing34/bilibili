<?php
/*@var $this \Lib\View*/
$url = $this->url();
$year = date('Y') == '2013' ? '2013' : '2013 - ' . date('Y');
?>


        </div>
        <footer class="container">
            &COPY; <?php echo $year;?> <a href="<?php echo $url->link();?>">www.citymv.com</a> All Rights Reserved. Powered by jingd.
        </footer>
<?php
if (isset($appendStatic['js'])) {
    foreach ($appendStatic['js'] as $eachJs) {
        echo '<script type="text/javascript" src="' . $url->jsUlr($eachJs) . '"/></script>' . "\n";
    }
}
?>
<script type="text/javascript">
$(function(){
    $('.nav-tabs > li > a').hover( function(){
      $(this).tab('show');
   });
});
</script>
    </body>
</html>