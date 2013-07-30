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
        <div class="container-fluid">
            <div class="row-fluid">
                <div class="span2">
                    <div class="accordion" id="panelMenu">
                        <div class="accordion-group">
                            <div class="accordion-heading bili-royalBlue-bg">
                                <a class="accordion-toggle bili-white" href="<?php echo $this->url()->link('bilibili/profile')?>">
                                    <i class="icon-user icon-white pull-right"></i>
                                    <?php echo $user['nickname']?>
                                </a>
                            </div>
                        </div>
                        <div class="accordion-group">
                            <div class="accordion-heading bili-royalBlue-bg">
                                <a class="accordion-toggle bili-white" href="<?php echo $this->url()->link('bilibili')?>">
                                    <i class="icon-info-sign icon-white pull-right"></i>
                                    DashBoard
                                </a>
                            </div>
                        </div>
                        <?php
                        foreach ($menu as $menuText => $eachMenu) {
                            $in = isset($eachMenu['current']) ? ' in' : '';
                            echo <<<EOT
                      <div class="accordion-group">
                        <div class="accordion-heading bili-royalBlue-bg">
                          <a class="accordion-toggle bili-white" data-toggle="collapse" data-parent="#panelMenu" href="#collapse{$menuText}">
                              <i class="icon-chevron-right icon-white pull-right"></i>
                            {$eachMenu['text']}
                          </a>
                        </div>
                        <div id="collapse{$menuText}" class="accordion-body collapse{$in}">
EOT;
                            foreach ($eachMenu['sub'] as $menuUri => $eachSub) {
                                $icon = $menuUri == $subSegments ? '<i class="icon-chevron-right pull-right"></i>' : '';
                                echo <<<EOT

                          <div class="accordion-inner">
                                {$icon}
                                <a style="display:block;" href="{$this->url()->link("bilibili/{$menuUri}")}">{$eachSub}</a>
                          </div>
EOT;
                            }
                            echo <<<EOT
                        </div>
                      </div>
EOT;
                    }
                    ?>
                        <div class="accordion-group">
                            <div class="accordion-heading bili-royalBlue-bg">
                                <a class="accordion-toggle bili-white" href="<?php echo $this->url()->link('bilibili/quit')?>">
                                    <i class="icon-off icon-white pull-right"></i>
                                    注销登录
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span10">
                    <?php
                    foreach ($tip as $tipType => $eachTips) {
                        foreach ($eachTips as $eachTip) {
                            $tipTitle = isset($eachTips['title']) ? "<h4>{$eachTip['title']}</h4>" : '';
                            $tipMsg = isset($eachTip['msg']) ? $eachTip['msg'] : '';
                            echo <<<EOT
                    <div class="alert alert-{$tipType}">
                      <button type="button" class="close" data-dismiss="alert">&times;</button>
                      {$tipTitle}
                      {$tipMsg}
                    </div>
EOT;
                        }
                    }
                    ?>