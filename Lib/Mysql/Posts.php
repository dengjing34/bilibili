<?php
namespace Lib\Mysql;
class Posts extends Data {
    public $id, $title, $categoryId, $categoryName, $categoryEnglishName, $userId, $userNickname, $status;
    public $content, $tags, $insertedTime, $updatedTime;
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 2;
    public static $statusText = array(
        self::STATUS_ACTIVE => '正常',
        self::STATUS_INACTIVE => '冻结',
    );
    const TABLE_NAME = 'posts';
    public function __construct() {
        $options = array(
            'db' => MYSQL_DBNAME_CITYMV,
            'table' => self::TABLE_NAME,
            'key' => 'id',
            'columns' => array(
                'id' => 'id',
                'title' => 'title',
                'categoryId' => 'category_id',
                'categoryName' => 'category_name',
                'categoryEnglishName' => 'category_english_name',
                'userId' => 'user_id',
                'userNickname' => 'user_nickname',
                'status' => 'status',
                'tags' => 'tags',
                'content' => 'content',
                'insertedTime' => 'inserted_time',
                'updatedTime' => 'updated_time',
                'attributeData' => 'attris',
            ),
            'required' => array(
                'title', 'categoryId', 'userId', 'tags', 'content', 'insertedTime', 'updatedTime',
            ),
            'searcher' => '\Lib\Solr\Posts',//指定searcher的类名
        );
        parent::init($options);
    }

    /**
     * 保存时若有categoryId但是没有categoryName或categoryEnglishName,会自动去查.<br />
     * 若有userId但是没有userNickname,会自动去查.
     * @return \Lib\Mysql\Posts
     * @throws \Exception
     */
    public function save() {
        if (ctype_digit((string)$this->categoryId) && (!$this->categoryName || $this->categoryEnglishName)) {
            $category = new Category();
            try {
                $category->load($this->categoryId);
                $this->categoryName = $category->name;
                $this->categoryEnglishName = $category->englishName;
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        }
        if (ctype_digit((string)$this->userId) && !$this->userNickname) {
            $user = new User();
            try {
                $user->load($this->userId);
                $this->userNickname = $user->nickname;
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        }
        if (!$this->id) {
            $this->insertedTime = $this->updatedTime = time();
        } else {
            $this->updatedTime = time();
        }
        return parent::save();
    }

    /**
     * 设置状态正常
     * @return \Lib\Mysql\Posts
     */
    public function setActive() {
        $this->status = self::STATUS_ACTIVE;
        return $this;
    }

    /**
     * 设置状态冻结
     * @return \Lib\Mysql\Posts
     */
    public function setInactive() {
        $this->status = self::STATUS_INACTIVE;
        return $this;
    }
}

?>
