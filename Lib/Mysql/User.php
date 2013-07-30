<?php
namespace Lib\Mysql;
class User extends Data {
    public $id, $nickname, $password, $passwordExt, $status, $email, $createdTime, $updatedTime;
    const TABLE_NAME = 'user';
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 2;
    const COOKIE_USER_ID = 'uid';
    const COOKIE_USER_NICKNAME = 'nickname';
    public static $statusText = array(
        self::STATUS_ACTIVE => '有效',
        self::STATUS_INACTIVE => '无效',
    );

    public function __construct() {
        $options = array(
            'db' => MYSQL_DBNAME_CITYMV,
            'table' => self::TABLE_NAME,
            'key' => 'id',
            'columns' => array(
                'id' => 'id',
                'nickname' => 'nickname',
                'password' => 'password',
                'passwordExt' => 'password_ext',
                'status' => 'status',
                'email' => 'email',
                'createdTime' => 'created_time',
                'updatedTime' => 'updated_time',
                'attributeData' => 'attris',
            ),
            'required' => array(
                'nickname', 'password', 'status', 'email'
            ),
        );
        parent::init($options);
    }
    
    /**
     * 设置状态为启用
     * @return \Lib\Mysql\User
     */
    public function setActive() {
        $this->status = self::STATUS_ACTIVE;
        return $this;
    }

    /**
     * 设置状态为停用
     * @return \Lib\Mysql\User
     */
    public function setInactive() {
        $this->status = self::STATUS_INACTIVE;
        return $this;
    }

    /**
     *
     * @return \Lib\Mysql\User
     * @throws \Exception
     */
    public function save() {
        $sameNickNameUser = self::loadByNickname($this->nickname);
        $sameEmailUser = self::loadByEmail($this->email);
        if (!$this->id) {
            if ($sameEmailUser) {
                throw new \Exception("邮件地址 [{$this->email}] 已存在");
            }
            if ($sameNickNameUser) {
                throw new \Exception("昵称 [{$this->nickname}] 已存在");
            }
            $this->createdTime = $this->updatedTime = time();
            if (strlen($this->password) > 0) {
                $this->generatePassword();
            }
        } else {
            if ($sameEmailUser && $sameEmailUser->id != $this->id) {
                throw new \Exception("邮件地址 [{$this->email}] 已存在");
            }
            if ($sameNickNameUser && $sameNickNameUser->id != $this->id) {
                throw new \Exception("昵称 [{$this->nickname}] 已存在");
            }
            if (strlen($this->password) != 32) {
                $this->generatePassword();
            }
            $this->updatedTime = time();
        }
        return parent::save();
    }

    /**
     * 生成password
     * @return \Lib\Mysql\User
     */
    private function generatePassword() {
        if (is_null($this->passwordExt)) {
            $this->passwordExt = substr(md5(time()), 0, 10);
        }
        $this->password = md5($this->password . $this->passwordExt);
        return $this;
    }

    /**
     * 验证password
     * @param string $password 输入的密码
     * @return boolean
     */
    public function validatePassword($password) {
        return $this->password == md5($password . $this->passwordExt);
    }

    /**
     * 根据用户昵称查找
     * @param string $nickname 用户昵称
     * @return \Lib\Mysql\User 找得到返回user对象,找不到返回false
     */
    public static function loadByNickname($nickname) {
        $className = get_called_class();
        /*@var $user \Lib\Mysql\User*/
        $user = new $className();
        $user->nickname = $nickname;
        return current($user->find(array('limit' => 1)));
    }

    /**
     * 根据用户邮箱查找
     * @param string $email 用户邮箱
     * @return \Lib\Mysql\User 找得到返回user对象,找不到返回false
     */
    public static function loadByEmail($email) {
        $className = get_called_class();
        /*@var $user \Lib\Mysql\User*/
        $user = new $className();
        $user->email = $email;
        return current($user->find(array('limit' => 1)));
    }

    /**
     * 获取cookie中userId的key
     * @return string
     */
    public static function cookieUserId() {
        return self::COOKIE_USER_ID;
    }

    /**
     * 获取cookie中userNickname的key
     * @return string
     */
    public static function cookieUserNickname() {
        return self::COOKIE_USER_NICKNAME;
    }

    /**
     * 是否登录
     * @return boolean true为已经登录, false为未登录
     */
    public static function isLogin() {
        $result = false;
        if (\Lib\Cookie::get(self::cookieUserId()) && \Lib\Cookie::get(self::cookieUserNickname())) {
            $result = true;
        }
        return $result;
    }

    /**
     * 验证用户登录
     * @param array $user 包含nickname,passowrd的数组
     * @return \Lib\Mysql\User 验证登录成功返回user对象, 失败抛出异常
     * @throws \Exception
     */
    public static function validateLogin(array $user) {
        $required = array(
            'nickname' => '昵称',
            'password' => '密码',
        );
        $error = array();
        foreach ($required as $key => $text) {
            if (!isset($user[$key]) || (isset($user[$key]) && strlen(trim($user[$key])) == 0)) {
                $error[] = $text;
            }
        }
        if (!empty($error)) {
            throw new \Exception('[' . implode('], [', $error) . '] 不能为空');
        }
        /*@var $user \Lib\Mysql\User*/
        if (($u = self::loadByNickname($user['nickname'])) && $u->validatePassword($user['password'])) {
            \Lib\Cookie::set(self::cookieUserId(), $u->id, 86400);
            \Lib\Cookie::set(self::cookieUserNickname(), $u->nickname, 86400);
        } else {
            throw new \Exception('用户名密码不正确');
        }
        return $u;
    }

    public static function logout() {
        \Lib\Cookie::delete(self::cookieUserId());
        \Lib\Cookie::delete(self::cookieUserNickname());
    }

    /**
     * 获取登录用户信息
     * @return array 包含id,nickname的数组
     */
    public static function getLoginUser() {
        return array(
            'id' => \Lib\Cookie::get(self::cookieUserId()),
            'nickname' => \Lib\Cookie::get(self::cookieUserNickname()),
        );
    }

    /**
     * 重新写入登录用户cookie,一般在后台修改了用户昵称时需要用到
     * @param array $user array 包含id和nickname的数组
     * @return boolean 修改成功返回true,失败返回false
     */
    public static function setLoginUser(array $user) {
        $result = false;
        if (isset($user['id']) && ctype_digit($user['id']) && isset($user['nickname']) && $user['nickname']) {
            \Lib\Cookie::set(self::cookieUserId(), $user['id']);
            \Lib\Cookie::set(self::cookieUserNickname(), $user['nickname']);
            $result = true;
        }
        return $result;
    }

    /**
     * 创建时间
     * @param string $fmt 时间格式
     * @return string
     */
    public function createdTime($fmt = 'Y-m-d H:i:s') {
        return date($fmt, $this->createdTime);
    }

    /**
     * 更新时间
     * @param string $fmt 时间格式
     * @return string
     */
    public function updatedTime($fmt = 'Y-m-d H:i:s') {
        return date($fmt, $this->updatedTime);
    }

    /**
     * 获取用户状态描述
     * @return string
     */
    public function getStatus() {
        return self::$statusText[$this->status];
    }
}

?>
