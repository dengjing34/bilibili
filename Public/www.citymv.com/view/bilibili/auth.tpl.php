<?php
/*@var $this \Lib\View*/
?>
<!DOCTYPE html>
<html>
    <head>
        <title><?php echo $title;?></title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <?php
        foreach ($meta as $metaName => $metaContent) {
            echo <<<EOT
            <meta name="{$metaName}" content="{$metaContent}">\n
EOT;
        }
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
            <form method="post">
                <fieldset>
                    <legend>管理登录</legend>
                    <label>
                        <input type="text" required="true" value="<?php echo htmlspecialchars($nickname);?>" name="nickname" placeholder="您的昵称">
                    </label>
                    <label>
                        <input type="password" required="true" name="password" placeholder="您的密码">
                    </label>
                    <span class="help-block"><?php echo $error;?></span>
                    <button type="submit" class="btn btn-primary">登录</button>
                </fieldset>
            </form>
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