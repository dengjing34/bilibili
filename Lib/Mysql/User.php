<?php
namespace Lib\Mysql;
class User extends Data {
    public $id, $nickname, $password, $passwordExt, $status, $email, $createdTime, $updatedTime;
    const TABLE_NAME = 'user';
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 2;
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
}

?>
