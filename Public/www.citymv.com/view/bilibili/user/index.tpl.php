<?php
/* @var $this \Lib\View */
/* @var $user \Lib\Mysql\User */
$url = $this->url();
?>
<table class="table table-bordered table-hover">
    <thead>
        <tr>
            <th>操作</th>
            <th>id</th>
            <th>昵称</th>
            <th>邮件地址</th>
            <th>状态</th>
            <th>创建时间</th>
            <th>修改时间</th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($pageResult['docs'] as $user) {
            echo <<<EOT
        <tr>
            <td><a class="btn btn-mini btn-primary" href="{$url->link("bilibili/user/edit?id={$user->id}")}">修改</a></td>
            <td>{$user->id}</td>
            <td>{$user->nickname}</td>
            <td>{$user->email}</td>
            <td>{$user->getStatus()}</td>
            <td>{$user->createdTime()}</td>
            <td>{$user->updatedTime()}</td>
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