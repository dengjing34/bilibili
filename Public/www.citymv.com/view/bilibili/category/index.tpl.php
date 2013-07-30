<?php
/* @var $this \Lib\View */
/* @var $category \Lib\Mysql\Category */
$url = $this->url();
?>
<form>
    <div class="input-prepend">
        <span class="add-on">英文名</span>
        <input id="englishName" class="input-medium" value="<?php echo $englishName;?>" name="englishName" pattern="\w+" type="search" placeholder="通过英文名搜索">
    </div>
    <div class="input-prepend">
        <span class="add-on">层级</span>
        <input id="level" class="input-medium" value="<?php echo $category->level;?>" name="level" min="1" pattern="\d+" type="number" placeholder="通过层级搜索">
    </div>
    <div class="input-prepend">
        <span class="add-on">父类Id</span>
        <input id="parentId" class="input-medium" value="<?php echo $category->parentId;?>" name="parentId" min="0" pattern="\d+" type="number" placeholder="通过父类Id搜索">
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
            <th>中文名</th>
            <th>英文名</th>
            <th>父类id</th>
            <th>层级</th>
            <th>路径</th>
            <th>状态</th>
            <th>创建时间</th>
            <th>修改时间</th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($pageResult['docs'] as $category) {
            echo <<<EOT
        <tr>
            <td><a class="btn btn-mini btn-primary" href="{$url->link("bilibili/category/edit?id={$category->id}")}">修改</a></td>
            <td>{$category->id}</td>
            <td>{$category->name}</td>
            <td>{$category->englishName}</td>
            <td>{$category->parentId}</td>
            <td>{$category->level}</td>
            <td>{$category->path}</td>
            <td>{$category->getStatus()}</td>
            <td>{$category->createdTime()}</td>
            <td>{$category->updatedTime()}</td>
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