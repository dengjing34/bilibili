<?php
namespace Lib;
/**
 * 获取config目录下配置文件的信息
 */
class Config {
    private static $conf = array();
    /**
     * 获取CONFIG_PATH目录下配置文件的信息 配置文件用return array();的方式来实现
     * @param string $key 配置文件的文件名以及数组的key, 如luckybox.products.12表示查找JM_APP_ROOT目录中config.luckybox.php里面的['product'][11]的value
     * @return mixed 返回可能是string或者array(),根据配置文件而定
     */
    public static function load($key) {
        if (!defined('CONFIG_PATH')) throw new \Exception('CONFIG_PATH is undefined');
        $keys = explode('.', $key);
        $file = current($keys);
        if (isset(self::$conf[$file])) {
            $rs = self::$conf[$file];
        } elseif (is_file(CONFIG_PATH . "{$file}.php")) {
            $rs = self::$conf[$file] = include_once(CONFIG_PATH . "{$file}.php");
        } else {
            throw new \Exception("config file [{$file}] not found");
        }
        array_shift($keys); //shift the file
        foreach ($keys as $k) {
            if (array_key_exists($k, (array)$rs)) $rs = $rs[$k];
            else throw new \Exception("[{$k}] is undefined in config file [{$file}]");
        }
        return $rs;
    }
}

?>
