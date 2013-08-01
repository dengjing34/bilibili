<?php
/*@var $this \Lib\View*/
/*@var $firstCategory \Lib\Mysql\Category*/
/*@var $secondCategory \Lib\Mysql\Category*/
$url = $this->url();
$currFirstCategory = $currSecondCategory = null;
?>
<!DOCTYPE html>
<html>
    <head>
        <title><?php echo $title;?></title>
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
        <nav class="container" id="nav">
            <ul class="nav nav-tabs">
                <li<?php echo $url->segments() ? '' : ' class="active"'?>><a data-target="#" href="<?php echo $url->link()?>">首页</a></li>
                <?php
                foreach ($categories as $firstCategory) {
                    if ($categoryEnglishName == $firstCategory->englishName) {
                        $currFirstCategory = $firstCategory;
                    }
                    $activeName = $firstCategory->englishName . 'Active';
                    ${$activeName} = $categoryEnglishName == $firstCategory->englishName || in_array($categoryEnglishName, array_map(function($v){return $v->englishName;}, $firstCategory->children)) ? ' class="active"' : '';
                    echo <<<EOT
                    <li{${$activeName}}><a href="{$url->link($firstCategory->englishName)}" data-target="#nav_{$firstCategory->englishName}">{$firstCategory->name}</a></li>
EOT;
                }
                ?>
                <li class="pull-right">
                    <form class="form-search" action="<?php echo $url->link('search')?>">
                        <div class="input-append">
                            <input class="span2 search-query" placeholder="输入关键字" name="q" value="<?php echo $url->get('q')?>" type="search">
                            <button class="btn" type="submit">Search</button>
                        </div>
                    </form>
                </li>
            </ul>
            <div class="tab-content">
            <?php
            $currStyle = ' class="bili-orangeRed"';
            foreach ($categories as $firstCategory) {
                $activeName = $firstCategory->englishName . 'Active';
                $categoryActive = ${$activeName} ? ' active' : ' fade';
                echo "<div title=\"{$firstCategory->englishName}\" class=\"tab-pane{$categoryActive}\" id=\"nav_{$firstCategory->englishName}\">";
                foreach ($firstCategory->children as $secondCategory) {
                    if ($categoryEnglishName == $secondCategory->englishName) {
                        $currSecondCategory = $secondCategory;
                        $currFirstCategory = $firstCategory;
                    }
                    $activeLink = $categoryEnglishName == $secondCategory->englishName ? $currStyle : '';
                    echo <<<EOT
                    <a{$activeLink} title="{$secondCategory->englishName}" href="{$url->link($secondCategory->englishName)}">{$secondCategory->name}</a>
EOT;
                }
                echo '</div>';
            }
            ?>
            </div>
        </nav>
        <div class="container">
            <?php
            if (!empty($breadcrumb)) {
                $breadCount = count($breadcrumb);
            ?>
            <ul class="breadcrumb">
                <?php
                $k = 1;
                foreach ($breadcrumb as $breadText => $breadLink) {
                    if ($k < $breadCount) {
                    echo <<<EOT
                    <li><a href="{$url->link($breadLink)}">{$breadText}</a><span class="divider">/</span></li>
EOT;
                    } else {
                        echo "<li class=\"active\">{$breadText}</li>";
                    }
                    $k++;
                }
                ?>
            </ul>
            <?php
            }
            ?>
            