<?php
namespace Lib\Mysql;
class Posts extends Data {
    public $id, $title, $categoryId, $categoryName, $categoryEnglishName, $userId, $userNickname, $status;
    public $parentCategoryId, $parentCategoryName, $parentCategoryEnglishName;
    public $content, $tags, $viewCount = 0, $picCount = 0, $insertedTime, $updatedTime;
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 2;
    public static $statusText = array(
        self::STATUS_ACTIVE => '正常',
        self::STATUS_INACTIVE => '丢弃',
    );
    /**
     * update时是否自动更新updatedTime属性, 默认为true
     * @var boolean
     */
    private static $autoUpdatedTime = true;
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
                'parentCategoryId' => 'parent_category_id',
                'parentCategoryName' => 'parent_category_name',
                'parentCategoryEnglishName' => 'parent_category_english_name',
                'userId' => 'user_id',
                'userNickname' => 'user_nickname',
                'status' => 'status',
                'tags' => 'tags',
                'viewCount' => 'view_count',
                'picCount' => 'pic_count',
                'content' => 'content',
                'insertedTime' => 'inserted_time',
                'updatedTime' => 'updated_time',
                'attributeData' => 'attris',
            ),
            'required' => array(
                'title', 'categoryId', 'userId', 'status', 'content', 'insertedTime', 'updatedTime',
            ),
            'searcher' => '\Lib\Solr\Posts',//指定searcher的类名
        );
        parent::init($options);
    }

    /**
     * 保存时若有categoryId会自动去查对应的中文名英文名和父类id,父类中文名,父类英文名<br />
     * 若有userId但是没有userNickname,会自动去查.
     * @return \Lib\Mysql\Posts
     * @throws \Exception
     */
    public function save() {        
        if (ctype_digit((string)$this->categoryId) && $this->categoryId > 0) {
            $category = new Category();
            try {
                $category->load($this->categoryId);                
                $this->categoryName = $category->name;
                $this->categoryEnglishName = $category->englishName;
                if ($this->parentCategoryId != $category->parentId) {
                    $parentCategory = $category->getParentCategory();
                    $this->parentCategoryId = $parentCategory->id;
                    $this->parentCategoryName = $parentCategory->name;
                    $this->parentCategoryEnglishName = $parentCategory->englishName;
                    unset($parentCategory);
                }
                unset($category);
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        }
        if (ctype_digit((string)$this->userId) && !$this->userNickname) {
            $user = new User();
            try {
                $user->load($this->userId);
                $this->userNickname = $user->nickname;
                unset($user);
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        }
        $this->checkContentPic();
        if (!$this->id) {
            $this->insertedTime = $this->updatedTime = time();
        } else {
            if (self::$autoUpdatedTime) {
                $this->updatedTime = time();
            }
        }
        return parent::save();
    }

    /**
     * 检查内容中包含的编辑器上传的图片数量,若有则改变$this->picCount并设置图片路径到attris的pics中
     * @return \Lib\Mysql\Posts
     */
    private function checkContentPic() {
        $uploadPath = UPLOAD_PATH;
        if (preg_match_all("@<img\ssrc=\"{$uploadPath}(?P<pics>.*)\"@U", $this->content, $matches)) {
            $this->picCount = count($matches['pics']);
            $this->set('pics', implode(',', $matches['pics']));
        }
        return $this;
    }

    /**
     * 设置是否update的时候自动更新updatedTime时间 默认为true
     * @param boolean $flag true为自动更新, false为不自动
     * @return \Lib\Mysql\Posts
     */
    public function setAutoUpdatedTime($flag) {
        self::$autoUpdatedTime = $flag;
        return $this;
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

    /**
     * 创建时间
     * @param string $fmt 时间格式
     * @return string
     */
    public function insertedTime($fmt = 'Y-m-d H:i:s') {
        return date($fmt, $this->insertedTime);
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

    /**
     * 获取post的链接地址
     * @return string
     */
    public function postUrl() {
        return $this->categoryEnglishName . '/a' . $this->id . '.html';
    }
}

?>
