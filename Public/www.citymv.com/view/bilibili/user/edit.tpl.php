<?php
/*@var $this \Lib\View*/
/*@var $user \Lib\Mysql\User*/
?>
<form method="post">
    <fieldset>
        <legend>编辑用户</legend>
        <label for="nickname">昵称</label>
        <input type="text" name="nickname" required="true" value="<?php echo $user->nickname;?>" id="nickname" placeholder="输入昵称">
        <label for="password">密码</label>
        <input type="password" name="password" required="true" value="<?php echo $user->password;?>" id="password" placeholder="输入密码">
        <label for="email">邮件</label>
        <input type="email" name="email" required="true" value="<?php echo $user->email;?>" id="email" placeholder="输入邮件">
        <label>状态</label>
        <?php
        $i = 1;
        foreach (\Lib\Mysql\User::$statusText as $statValue => $statusText) {
            $checked = $user->status == $statValue ? 'checked="checked"' : '';
        ?>
            <label class="radio inline">
                <input type="radio" name="status" value="<?php echo $statValue;?>" <?php echo $checked;?>><?php echo $statusText; ?>
            </label>
        <?php
            $i++;
        }
        ?>
        <label>创建时间:<?php echo $user->createdTime();?></label>
        <label>更新时间:<?php echo $user->updatedTime();?></label>
        <p>
            <button type="submit" class="btn btn-primary">保存</button>
        </p>
    </fieldset>
</form>