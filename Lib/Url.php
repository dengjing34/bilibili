<?php
namespace Lib;
class Url {
    const _RP_ = '_rp_';
    const _AJAX_ = '_ajax_';
    private $get;
    private $post;
    private $request;
    private $segments = array();
    private static $instances = array();
    private $host;
    
    private function __construct() {
        $this->get = $_GET;
        $this->post = $_POST;
        $this->request = $_REQUEST;
        $this->host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
    }

    private function __clone() {
        ;
    }

    /**
     * 获取\Lib\Url的实例
     * @return \Lib\Url
     */
    public static function instance() {
        $className = get_called_class();
        if (!isset(self::$instances[$className])) {
            self::$instances[$className] = new $className();
        }
        return self::$instances[$className];
    }

    /**
     * 生成链接地址 不传递$uri则是首页地址
     * @param string $uri 链接的uri段
     * @return string 生成的url
     */
    public function link($uri = '') {
        return $this->host ? 'http://' . $this->host . '/' . $uri : '/';
    }

    /**
     * 静态文件夹的url
     * @param string $subfolder 静态文件子文件夹
     * @return string 
     */
    public function staticUrl($subfolder = '') {
        return $this->link('static/' . $subfolder);
    }

    /**
     * css文件夹的url
     * @param string $css css文件名或子路径
     * @return sring
     */
    public function cssUrl($css = '') {
        return $this->staticUrl('css/' . $css);
    }

    /**
     * js文件夹的url
     * @param sring $js js文件名或子路径
     * @return sring
     */
    public function jsUlr($js = '') {
        return $this->staticUrl('js/' . $js);
    }

    /**
     * 获取referer url
     * @return string
     */
    public function getRefer() {
        return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    }

    /**
     * 给$_GET,$_POST,$_REQUEST数组指定value
     * @param string $type
     * @param string $name
     * @param mixed $value
     * @return \Lib\Url
     */
    private function setValue($type, $name, $value) {
        $this->{$type}[$name] = $value;
        $this->request[$name] = $value;
        return $this;
    }

    /**
     * 为$_GET指定value
     * @param string $name
     * @param mixed $value
     * @return \Lib\Url
     */
    public function setGet($name, $value) {
        return $this->setValue('get', $name, $value);
    }

    /**
     * 为$_POST指定value
     * @param string $name
     * @param mixed $value
     * @return \Lib\Url
     */
    public function setPost($name, $value) {
        return $this->setValue('post', $name, $value);
    }

    /**
     * 根据name获取url的get参数
     * @param string $name 要从$_GET中获取的name, 如果为空则获取$_GET
     * @param mixed $default $_GET中没有name的时候的默认值
     * @return string||array $name为空才会出现array
     */
    public function get($name = null, $default = null) {
        return $this->getValue($this->get, $name, $default = null);
    }

    /**
     * 根据name获取url的post参数
     * @param string $name 要从$_POST中获取的name, 如果为空则获取$_POST
     * @param mixed $default $_POST中没有name的时候的默认值
     * @return string||array $name为空才会出现array
     */
    public function post($name = null, $default = null) {
        return $this->getValue($this->post, $name, $default = null);
    }

    /**
     * 根据name获取url的request参数
     * @param string $name 要从$_REQUEST中获取的name, 如果为空则获取$_REQUEST
     * @param mixed $default $_REQUEST中没有name的时候的默认值
     * @return string||array $name为空才会出现array
     */
    public function request($name = null, $default = null) {
        return $this->getValue($this->request, $name, $default = null);
    }

    /**
     * 获取根据$type来获取$_GET, $_POST, $_REQUST中的数据
     * @param array $type $this->get||$this->post||$this->request
     * @param string $name 键名
     * @param mixed $default 没有找到时的默认值
     * @return string||array $name为空才会出现array
     */
    private function getValue(array $type, $name = null, $default = null) {
        if (!$name) {
            $result = $type;
        } else {
            $result = isset($type[$name]) ? $type[$name] : $default;
        }
        return $result;
    }

    /**
     * 重定向uri
     * @param string $uri uri地址
     * @param string $method
     */
    public function redirect($uri = '', $method = '302') {
        $uri = str_replace("\n", '', $uri);
        $codes = array(
            '300' => 'Multiple Choices',
            '301' => 'Moved Permanently',
            '302' => 'Found',
            '303' => 'See Other',
            '304' => 'Not Modified',
            '305' => 'Use Proxy',
            '307' => 'Temporary Redirect'
        );
        $method = isset($codes[$method]) ? $method : '302';
        header('HTTP/1.1 ' . $method . ' ' . $codes[$method]);
        header('Location: ' . $uri);
        exit('<a href="' . $uri . '">' . $uri . '</a>'); // Last resort, exit and display the URL
    }

    /**
     * 获取当前页面完整url地址
     * @return string url地址
     */
    public function getFullUrl() {
        $result = '';
        if (PHP_SAPI == 'cli') {
            $result = implode(' ', $_SERVER['argv']);
        } else {
            $port = $_SERVER['SERVER_PORT'] == '80' ? '' : ':' . $_SERVER['SERVER_PORT'];
            $http = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https' : 'http';
            $result = "{$http}://{$_SERVER['SERVER_NAME']}{$port}{$this->uri()}";
        }
        return $result;
    }

    /**
     * 通过参数获取 REQUEST_URI
     * @return string
     */
    public function uri() {
        return $this->get(self::_RP_);
    }

    /**
     * 获取url中_ajax_参数的值
     * @return string||null
     */
    public function ajax() {
        return $this->get(self::_AJAX_);
    }

    /**
     * 获取请求方式
     * @return string GET||POST
     */
    public function requestMethod() {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * 是否是POST请求
     * @return boolean 是返回true, 不是返回false
     */
    public function isPostMethod() {
        return $this->requestMethod() == 'POST';
    }

    /**
     * 用户请求的客户端信息
     * @return string
     */
    public function userAgern() {
        return $_SERVER['HTTP_USER_AGENT'];
    }

    /**
     * 获取不包含self::_RP_键的QUERY_STRING
     * @return string
     */
    public function queryString() {
        return http_build_query(array_diff_key($this->get, array(self::_RP_ => 1)));
    }

    /**
     * 获取uri各段购成的数组
     * @return array
     */
    public function segments() {
        $this->segments = array_filter(explode('/', current(explode('?', $this->uri()))));
        return $this->segments;
    }
}

?>
