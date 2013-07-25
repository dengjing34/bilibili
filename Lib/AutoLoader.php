<?php
namespace Lib;
class AutoLoader {
    private $prefix = array();
    private static $instance;

    /**
     * singleton
     * @return \Lib\AutoLoader
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            $className = get_called_class();
            self::$instance = new $className();
        }
        return self::$instance;
    }

    /**
     * autoload调用逻辑通过命名空间查找类文件
     * @param string $className 包含命名空间的类名
     * @return \Lib\AutoLoader
     */
    public function loadByNameSpace($className) {
        $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
        $file = $this->prefix[__FUNCTION__] . $className . '.php';
        if (is_file($file)) {
            require_once $file;
        }
        return $this;
    }

    /**
     * autoload调用通过类名查找类文件
     * @param string $className 类名
     * @return \Lib\AutoLoader
     */
    public function loadByName($className) {
        $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
        $file = $this->prefix[__FUNCTION__] . $className . '.php';
        if (is_file($file)) {
            require_once $file;
        }
        return $this;
    }

    /**
     * autoload调用通过命名空间类名查找controller类文件
     * @param string $className 类名
     * @return \Lib\AutoLoader
     */
    public function loadByControllerName($className) {
        $className = str_replace('\\', DIRECTORY_SEPARATOR, lcfirst($className));
        $file = $this->prefix[__FUNCTION__] . $className . '.php';
        if (is_file($file)) {
            require_once $file;
        }
        return $this;
    }

    /**
     * @param string $prefix 类文件的路径前缀
     * @param string $method 需要注册的方法名称
     * @return boolean 注册成功返回true,失败返回false
     */
    public function register($prefix, $method) {
        $this->prefix[$method] = $prefix;
        return spl_autoload_register(array($this, $method));
    }
}

?>
