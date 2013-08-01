<?php
/*@var $this \Lib\View*/
/*@var $post \Lib\Mysql\Posts*/
$url = $this->url();
?>

<?php
$i = 1;
foreach ($pageResult['docs'] as $post) {
    $tags = $post->tags();
    $tagsHtml = '';
    if (!empty($tags)) {
        $tagsHtml = '<li>';
        foreach ($tags as $tag) {
            $tagsHtml .= "<a href=\"{$url->link("search?q={$tag}")}\" class=\"label label-info\">{$tag}</a> ";
        }
        $tagsHtml .= '</li>';
    }
    echo <<<EOT
    <ul class="span3 list">
        <li>
            <a href="{$url->link($post->postUrl())}">{$post->title}</a>
        </li>
        <li class="content">{$post->stripHtmlContent(400)}</dd>
        <li class="date">
            {$post->insertedTime('Y/m/d')}
            <a class="cat-label" href="{$url->link($post->categoryName)}">{$post->categoryName}</a>
        </li>
        {$tagsHtml}
    </ul>
EOT;
    if ($i % 4 == 0) {
        echo "<div class=\"clearfix\"></div>";
    }
    $i++;
    
}
?>
