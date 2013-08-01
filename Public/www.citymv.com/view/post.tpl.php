<?php
/*@var $this \Lib\View*/
/*@var $post \Lib\Mysql\Posts*/
$url = $this->url();
?>
<div class="row">
    <div class="span10">
        <h1><?php echo $post->title;?></h1>
        <?php
        $tags = $post->tags();
        $tagsHtml = '';
        if (!empty($tags)) {
            $tagsHtml = '<div>';
            foreach ($tags as $tag) {
                $tagsHtml .= "<a href=\"{$url->link("search?q={$tag}")}\" class=\"label label-info\">{$tag}</a> ";
            }
            $tagsHtml .= '</div>';
        }
        echo $tagsHtml;
        ?>
        <div class="detail">
            <?php echo $post->content;?>
        </div>
    </div>
    <div class="span2">
        ads
    </div>
</div>