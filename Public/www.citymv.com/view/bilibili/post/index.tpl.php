<?php
/* @var $this \Lib\View */
/* @var $post \Lib\Mysql\Posts */
$url = $this->url();
?>
<form>
    <div class="input-prepend">
        <span class="add-on">关键词</span>
        <input id="q" class="input-medium" value="<?php echo $q;?>" name="q" type="search" placeholder="输入关键词">
    </div>
    <div class="input-prepend">
        <span class="add-on">分类Id</span>
        <input id="categoryId" class="input-medium" min="1" pattern="\d+" value="<?php echo $categoryId;?>" name="categoryId" type="number" placeholder="输入分类id">
    </div>
    <div class="input-prepend">
        <span class="add-on">父类Id</span>
        <input id="parentCategoryId" class="input-medium" min="1" pattern="\d+" value="<?php echo $parentCategoryId;?>" name="parentCategoryId" type="number" placeholder="输入父类id">
    </div>
    <p>
        <button type="submit" class="btn btn-primary">搜索</button>
    </p>
</form>
<table class="table table-bordered table-hover">
    <thead>
        <tr>
            <th>操作</th>
            <th>id</th>
            <th>标题</th>
            <th>父类</th>
            <th>子类</th>
            <th>浏览次数</th>
            <th>作者</th>
            <th>状态</th>
            <th>标签</th>
            <th>创建时间</th>
            <th>修改时间</th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($pageResult['docs'] as $post) {
            echo <<<EOT
        <tr>
            <td><a class="btn btn-mini btn-primary" href="{$url->link("bilibili/post/edit?id={$post->id}")}">修改</a></td>
            <td>{$post->id}</td>
            <td>{$post->title}</td>
            <td>
                <a data-toggle="tooltip" href="{$url->link("bilibili/post?parentCategoryId={$post->parentCategoryId}")}" title="{$post->parentCategoryEnglishName}">{$post->parentCategoryId}:{$post->parentCategoryName}</a>
            </td>
            <td>
                <a data-toggle="tooltip" href="{$url->link("bilibili/post?categoryId={$post->categoryId}")}" title="{$post->categoryEnglishName}">{$post->categoryId}:{$post->categoryName}</a>
            </td>
            <td>{$post->viewCount}</td>
            <td><a href="{$url->link("bilibili/post?userId={$post->userId}")}">{$post->userNickname}</a></td>
            <td>{$post->getStatus()}</td>
            <td>{$post->tags}</td>
            <td>{$post->insertedTime()}</td>
            <td>{$post->updatedTime()}</td>
        </tr>
EOT;
        }
        ?>
    </tbody>
</table>
<div class="pagination">
<?php
echo \Lib\Pager::showPage($pageResult['numFound'], $pageResult['rows']);
?>
</div>
<script type="text/javascript">
$(function(){
   $('td a').tooltip();
});
</script>