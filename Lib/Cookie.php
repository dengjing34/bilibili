<?php
namespace Lib;

class Cookie {

    public static function set($name, $value = null, $expire = 0, $disableJs = false) {
        if ($expire > 0)
            $expire = time() + $expire;
        $domain = '';
        if (isset($_SERVER['HTTP_HOST'])) {
            if (preg_match("/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/", $_SERVER['HTTP_HOST'])) {
                return setcookie($name, $value, $expire, '/');
            }
            if (preg_match("/([\w-]+)?(\.[\w-]+\.\w+\.?\w{0,})/", $_SERVER['HTTP_HOST'], $matches)) {
                $domain = $matches[2];
            }
        }
        if (PHP_SAPI == 'cli')
            return;
        else
            return setcookie($name, $value, $expire, '/', $domain, false, $disableJs);
    }

    public static function get($name) {
        return isset($_COOKIE[$name]) ? trim($_COOKIE[$name]) : null;
    }

    public static function delete($name) {
        return self::set($name, '', -8640000); //部分用户系统的时钟可能不准
    }

}

?>