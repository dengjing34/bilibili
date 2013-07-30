<?php
namespace Lib;
/**
 * apc缓存
 */
class Apc {
    /**
     * apc缓存实例
     * @var \Lib\Apc
     */
    const FORCE_REFRESH = '_force_refresh';
    private static $instance;

    private function __construct() {
        ;
    }

    private function __clone() {
        ;
    }

    /**
     * 是否强制刷新
     * @return boolean true则不读取apc数据, false则读取
     */
    public function forceRefresh() {
        return isset($_GET[self::FORCE_REFRESH]) && $_GET[self::FORCE_REFRESH] ? true : false;
    }
    
    /**
     * 获取apc缓存实例
     * @return \Lib\Apc
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            $className = get_called_class();
            self::$instance = new $className();
        }
        return self::$instance;
    }


    /**
     * 从缓存中取出存储的变量
     * @param mixed $key key是使用 apc_store() 存储的键名。 如果传递的是一个数组，则数组中的每个元素的值都被返回
     * @return mixed 存储一个变量或者一个数组失败时返回FALSE
     */
    public function get($key) {
        if ($this->forceRefresh()) {
            if (is_scalar($key)) {
                return false;
            } elseif (is_array($key)) {
                return array();
            }
        }
        return apc_fetch($key);
    }

    /**
     * 缓存一个变量到APC中
     * @param string $key 存储缓存变量使用的名称.key是唯一的，所以 两个值使用同一个名称，原来的将被新的值覆盖
     * @param mixed $data 要存储的数据
     * @param int $ttl 生存时间;在缓存中存储var共ttl秒, 在ttl秒过去后,存储的变量将会从缓存中擦除(在下一次请求时), 如果没有设置ttl(或者ttl是0), 变量将一直存活到被手动移除为止,除此之外不在缓存中的可能原因是， 缓存系统使用clear，或者restart等
     * @return boolean 成功时返回 TRUE， 或者在失败时返回 FALSE。
     */
    public function set($key, $data, $ttl = 0) {
        return apc_store($key, $data, $ttl);
    }

    /**
     * 从数据存储里删除某个变量
     * @param string $key 即是你用 apc_store() 存储数据时所设定的标记名称。
     * @return boolean 成功时返回 TRUE， 或者在失败时返回 FALSE。
     */
    public function delete($key) {
        return apc_delete($key);
    }

    /**
     * 检测keys是否存在, keys可以是字符或数组
     * @param mixed $keys A string, or an array of strings, that contain keys.
     * @return mixed TRUE if the key exists, otherwise FALSE Or if an array was passed to keys, then an array is returned that contains all existing keys, or an empty array if none exist.
     */
    public function exists($keys) {
        if ($this->forceRefresh()) {
            if (is_scalar($key)) {
                return false;
            } elseif (is_array($key)) {
                return array();
            }
        }
        return apc_exists($keys);
    }

    /**
     * 清除用户或者系统APC缓存
     * @return boolean
     */
    public function flush() {
        return apc_clear_cache();
    }

}

?>
