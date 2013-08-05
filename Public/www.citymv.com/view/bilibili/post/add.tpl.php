<?php
/*@var $this \Lib\View*/
/*@var $post \Lib\Mysql\Posts*/
$url = $this->url();
?>
<form method="post">
    <fieldset>
        <legend>编辑文章</legend>
        <label for="title">标题</label>
        <input type="text" class="input-xxlarge" name="title" required="true" value="<?php echo $post->title;?>" id="title" placeholder="输入标题">
        <label for="title">分类</label>
        <?php echo $categories;?>
        <label for="tags">标签</label>
        <input type="text" class="input-xxlarge" name="tags" value="<?php echo $post->tags;?>" id="tags" placeholder="输入标签,空格分割">
        <label>状态</label>
        <?php
        $i = 1;
        foreach (\Lib\Mysql\Posts::$statusText as $statValue => $statusText) {
            $checked = $i == 1 ? 'checked="checked"' : '';
        ?>
            <label class="radio inline">
                <input type="radio" name="status" value="<?php echo $statValue;?>" <?php echo $checked;?>><?php echo $statusText; ?>
            </label>
        <?php
            $i++;
        }
        ?>
        <label for="content">内容</label>
        <textarea id="content" name="content" style="width:100%;" rows="15"><?php echo $post->content;?></textarea>
        <p>
            <button type="submit" class="btn btn-primary">保存</button>
        </p>
    </fieldset>
</form>
<script type="text/javascript">
$(function(){
    KindEditor.ready(function(K) {
            window.editor = K.create('#content', {
                cssPath : '<?php echo $url->cssUrl('prettify.css')?>',
                uploadJson : '<?php echo $url->link('file/editor')?>',
                allowFileManager : false
            });
            prettyPrint();
    });
});
</script>