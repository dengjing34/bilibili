<?php
/*@var $this \Lib\View*/
/*@var $category \Lib\Mysql\Category*/
?>
<form method="post">
    <fieldset>
        <legend>编辑分类</legend>
        <label for="name">中文名</label>
        <input type="text" name="name" required="true" value="<?php echo $category->name; ?>" id="name" placeholder="输入中文名">
        <label for="englishName">英文名</label>
        <input type="text" name="englishName" required="true" pattern="\w+" value="<?php echo $category->englishName;?>" id="englishName" placeholder="输入英文名">
        <label for="parentId">父类Id</label>
        <input type="number" name="parentId" min="0" required="required" value="<?php echo $category->parentId;?>" id="parentId" placeholder="输入父类Id,不能小于0">
        <label>状态</label>
        <?php
        $i = 1;
        foreach (\Lib\Mysql\Category::$statusText as $statValue => $statusText) {
            $checked = $category->status == $statValue ? 'checked="checked"' : '';
        ?>
            <label class="radio inline">
                <input type="radio" name="status" value="<?php echo $statValue;?>" <?php echo $checked;?>><?php echo $statusText; ?>
            </label>
        <?php
            $i++;
        }
        ?>
        <label>路径:<?php echo $category->path;?></label>
        <label>层级:<?php echo $category->level;?></label>
        <label>创建时间:<?php echo $category->createdTime();?></label>
        <label>修改时间:<?php echo $category->updatedTime();?></label>
        <p>
            <button type="submit" class="btn btn-primary">保存</button>
        </p>
    </fieldset>
</form>