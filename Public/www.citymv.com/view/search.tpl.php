<?php
/*@var $this \Lib\View*/
/*@var $post \Lib\Mysql\Posts*/
$url = $this->url();
?>
<div class="row">
    <div class="span10">
        <?php
        foreach ($pageResult['docs'] as $post) {
            $tags = $post->tags();
            $tagsHtml = '';
            if (!empty($tags)) {
                $tagsHtml = '<dt>';
                foreach ($tags as $tag) {
                    $tagsHtml .= "<a href=\"{$url->link("search?q={$tag}")}\" class=\"label label-info\">{$tag}</a> ";
                }
                $tagsHtml .= '</dt>';
            }
            $picsHtml = '';
            if (($pics = $post->picThumbsSmall())) {
                $picsHtml .= '<dd class="pics">';
                foreach ($pics as $pic) {
                    $picsHtml .= "<img class=\"img-rounded\" src=\"{$url->fileUrl($pic)}\" />";
                }
                $picsHtml .= '<dd>';
            }
            $title = $q ? preg_replace("@({$q})@Ui", "<em>$1</em>", $post->title) : $post->title;
            $content = $q ? preg_replace("@({$q})@Ui", "<em>$1</em>", $post->stripHtmlContent(1000)) : $post->stripHtmlContent(1000);
            echo <<<EOT
            <dl class="listing">
                <dt><a href="{$post->postUrl()}">{$title}</a><span class="pull-right badge badge-important">{$post->insertedTime('Y/m/d')}</span></dt>
                {$tagsHtml}
                {$picsHtml}
                <dd></dd>
                <dd class="content">{$content}</dd>
            </dl>
EOT;
        }
        ?>
        <div class="pagination">
            <?php echo \Lib\Pager::showPage($pageResult['numFound'], $pageResult['rows'])?>
        </div>
    </div>
    <div class="span2">
        ads
    </div>
</div>