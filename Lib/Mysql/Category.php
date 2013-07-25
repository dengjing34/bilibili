<?php
namespace Lib\Mysql;
class Category extends Data {
    public $id, $name, $englishName, $parentId, $level, $path, $status, $createdTime, $updatedTime;
    const TABLE_NAME = 'category';
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
                'name' => 'name',
                'englishName' => 'english_name',
                'parentId' => 'parent_id',
                'level' => 'level',
                'path' => 'path',
                'status' => 'status',
                'createdTime' => 'created_time',
                'updatedTime' => 'updated_time',
                'attributeData' => 'attris',
            ),
            'required' => array(
                'name', 'englishName', 'parentId', 'status',
            ),
        );
        parent::init($options);
    }

    /**
     * 设置状态为启用
     * @return \Lib\Mysql\Category
     */
    public function setActive() {
        $this->status = self::STATUS_ACTIVE;
        return $this;
    }

    /**
     * 设置状态为停用
     * @return \Lib\Mysql\Category
     */
    public function setInactive() {
        $this->status = self::STATUS_INACTIVE;
        return $this;
    }


    /**
     * 中文名,英文名必须唯一
     * @return \Lib\Mysql\Category
     */
    public function save() {
        $sameNameCategory = self::loadByName($this->name);
        $sameEnglishNameCategory = self::loadByEnglishName($this->englishName);
        if (!$this->id) {
            if ($sameNameCategory) {
                throw new \Exception("分类中文名 [{$this->name}] 已存在");
            }
            if ($sameEnglishNameCategory) {
                throw new \Exception("分类英文名 [{$this->englishName}] 已存在");
            }
            $this->createdTime = $this->updatedTime = time();
            $this->path = '';
            $this->level = 1;
            parent::save();
            $this->path = $this->id;    
            $this->getPathLevel($this->parentId);
        } else {
            if ($sameNameCategory && $sameNameCategory->id != $this->id) {
                throw new \Exception("分类中文名 [{$this->name}] 已存在");
            }
            if ($sameEnglishNameCategory && $sameEnglishNameCategory->id != $this->id) {
                throw new \Exception("分类英文名 [{$this->englishName}] 已存在");
            }
            $this->updatedTime = time();
        }
        return parent::save();
    }


    /**
     * 根据parentId来查询出路径和层级
     * @param int $parentId
     * @return \Lib\Mysql\Category
     */
    private function getPathLevel($parentId) {
        $className = get_called_class();
        /*@var $category \Lib\Mysql\Category*/
        $category = new $className();
        try {
            $category->load($parentId);
            $this->path = $parentId . ',' . $this->path;
            $this->level++;
            $this->getPathLevel($category->parentId);
        } catch (\Exception $e) {
            
        }
        unset($category);
        return $this;
    }


    /**
     * 通过中文名读取
     * @param string $name 中文名
     * @return \Lib\Mysql\Category 找得到返回\Lib\Mysql\Category,找不到返回false
     */
    public static function loadByName($name) {
        $className = get_called_class();
        /*@var $category \Lib\Mysql\Category*/
        $category = new $className();
        $category->name = $name;
        return current($category->find(array('limit' => 1)));
    }

    /**
     * 通过英文名读取
     * @param string $englishName 英文名
     * @return \Lib\Mysql\Category 找得到返回\Lib\Mysql\Category,找不到返回false
     */
    public static function loadByEnglishName($englishName) {
        $className = get_called_class();
        /*@var $category \Lib\Mysql\Category*/
        $category = new $className();
        $category->englishName = $englishName;
        return current($category->find(array('limit' => 1)));
    }

}

?>
