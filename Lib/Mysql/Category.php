<?php
namespace Lib\Mysql;
class Category extends Data {
    public $id, $name, $englishName, $parentId, $level, $path, $status, $createdTime, $updatedTime;
    const TABLE_NAME = 'category_test';
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 2;
    const APC_KEY_CATEGORIES = 'categories';
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
            try {
                $this->getPathLevel($this->parentId);
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        } else {
            if ($sameNameCategory && $sameNameCategory->id != $this->id) {
                throw new \Exception("分类中文名 [{$this->name}] 已存在");
            }
            if ($sameEnglishNameCategory && $sameEnglishNameCategory->id != $this->id) {
                throw new \Exception("分类英文名 [{$this->englishName}] 已存在");
            }
            $this->updatedTime = time();
            $this->level = 1;
            $this->path = $this->id;
            try {
                $this->getPathLevel($this->parentId);
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        }
        $this->apcInstance()->delete($this->cacheKey(self::APC_KEY_CATEGORIES));
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
            if ($parentId > 0) {
                throw new \Exception("父类Id : [{$parentId}] 不存在");
            }
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

    /**
     * 根据$this->parentId查出父类的对象
     * @return \Lib\Mysql\Category 父类对象
     * @throws \Exception
     */
    public function getParentCategory() {
        $className = $this->className();
        /*@var $category \Lib\Mysql\Category*/
        $category = new $className();
        try {
            $category->load($this->parentId);
        } catch (\Exception $e) {
            throw new \Exception('category parentId : ' . $e->getMessage());
        }
        return $category;
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

    /**
     * 获取所有分类数据
     * @param int $parentId 默认是0,从顶级开始查询
     * @param int $recursion 递归查询次数 默认是1次, 若为-1则是递归查询所有children
     * @return array
     */
    public static function children($parentId = 0, $recursion = 1) {
        $className = get_called_class();
        /*@var $category \Lib\Mysql\Category*/
        $category = new $className();
        $category->parentId = $parentId;
        $category->setActive();
        $categories = $category->find(array(
            'order' => array('id' => 'ASC')
        ));
        if ($recursion > 0 || $recursion == -1) {
            foreach ($categories as $eachCategory) {
                $eachCategory->children = self::children($eachCategory->id, $recursion > 0 ? $recursion - 1 : $recursion);
            }
        }
        return $categories;
    }

    /**
     * 获取所有的分类数据
     * @return array
     */
    public function categories() {
        $key = $this->cacheKey(self::APC_KEY_CATEGORIES);
        $apc = $this->apcInstance();
        if (($categories = $apc->get($key)) === false) {
            $categories = self::children(0, 1);
            $apc->set($key, $categories);
        }
        return $categories;
    }

    /**
     * 获取全部分类的表单下拉选择控件html
     * @param string $name 表单控件名字
     * @param int $selectedId 默认选中的二级分类
     * @return string
     */
    public function categoriesFormSelect($name, $selectedId = 0) {
        $categories = $this->categories();
        $result = "<select required=\"required\" name=\"{$name}\" id=\"{$name}\"><option value=\"\">--请选择--</option>";
        foreach ($categories as $firstCategory) {
            $result .= "<optgroup label=\"{$firstCategory->name}\">";
            foreach ($firstCategory->children as $secondCategory) {
                $selected = $selectedId == $secondCategory->id ? ' selected="selected"' : '';
                $result .= "<option{$selected} value=\"{$secondCategory->id}\">{$secondCategory->name}</option>";
            }
        }
        $result .= '</select>';
        return $result;
    }

}

?>
