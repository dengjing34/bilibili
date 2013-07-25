<?php
namespace Lib;

abstract class Controller {
    /**
     *
     * @var \Lib\Url
     */
    private static $_url_;

    /**
     *
     * @var \Lib\Site
     */
    private $_site_;

    /**
     *
     * @var \Lib\View
     */
    private $_view_;

    private $prependStatic = array(), $appendStatic = array();

    public function __construct() {
        
    }

    /**
     *
     * @return \Lib\Url
     */
    protected function url() {
        if (is_null(self::$_url_)) {
            self::$_url_ = Url::instance();
        }
        return self::$_url_;
    }

    /**
     * controller的404
     */
    public function pageNotFound() {
        header("HTTP/1.0 404 Not Found");
        echo 'page not found!';
    }

    /**
     * 把\Lib\Site对象赋给$this->_site_
     * @param \Lib\Site $site
     * @return \Lib\Controller
     */
    public function init(Site $site) {
        $this->_site_ = $site;
        return $this;
    }

    /**
     * 
     * @return \Lib\Site
     */
    protected function getSite() {
        return $this->_site_;
    }


    /**
     * 获取controller需要的模版对象
     * @param array $data 初始化时模版时给模版赋值
     * @return \Lib\View
     */
    protected function getView(array $data = array()) {
        if (is_null($this->_view_)) {
            $this->_view_ = new View($data);
        }
        return $this->_view_;
    }

    /**
     * 设置顶部静态文件
     * @param array $files 静态文件数组
     * <pre>
     * array(
     * &nbsp;&nbsp;'css' => array('x.css', 'y.css', 'z.css'),
     * &nbsp;&nbsp;'js' => array('a.js', 'b.js', 'c.css'),
     * )
     * </pre>
     * @return \Lib\Controller
     */
    protected function prependStatic(array $files) {
        foreach ($files as $type => $file) {
            foreach ($file as $eachFile) {
                $this->prependStatic[$type][] = $eachFile;
            }
        }
        return $this;
    }

    /**
     * 获取顶部静态文件配置
     * @return array 静态文件数组
     * <pre>
     * array(
     * &nbsp;&nbsp;'css' => array('x.css', 'y.css', 'z.css'),
     * &nbsp;&nbsp;'js' => array('a.js', 'b.js', 'c.css');
     * )
     * </pre>
     */
    protected function getPrependStatic() {
        return $this->prependStatic;
    }

    /**
     * 设置底部静态文件
     * @param array $files 静态文件数组
     * <pre>
     * array(
     * &nbsp;&nbsp;'css' => array('x.css', 'y.css', 'z.css'),
     * &nbsp;&nbsp;'js' => array('a.js', 'b.js', 'c.css'),
     * )
     * </pre>
     * @return \Lib\Controller
     */
    protected function appendStatic(array $files) {
        foreach ($files as $type => $file) {
             foreach ($file as $eachFile) {
                $this->appendStatic[$type][] = $eachFile;
            }
        }
        return $this;
    }

    /**
     * 获取底部静态文件配置
     * @return array 静态文件数组
     * <pre>
     * array(
     * &nbsp;&nbsp;'css' => array('x.css', 'y.css', 'z.css'),
     * &nbsp;&nbsp;'js' => array('a.js', 'b.js', 'c.css');
     * )
     * </pre>
     */
    protected function getAppentStatic() {
        return $this->appendStatic;
    }

    /**
     * 调用模版文件
     * @param array $data 需要传入模版的参数
     * @param boolean $print 是否打印出内容
     * @param string $tpl 模版文件路径,只需要传递*.tpl.php中的*
     */
    protected function render(array $data = array(), $print = true, $tpl = null) {
        if (!$tpl) {
            $folders = $this->_site_->getFolder();
            $foldersStr = implode('/', $folders);
            $lastFolder = !empty($folders) ? end($folders) : '';
            $controllClassName = $this->_site_->getController();
            if (empty($folders)) {
                $tpl = $this->_site_->getMethod();
            } elseif (ucfirst($lastFolder) . '_Controller' == $controllClassName) {
                $tpl = $foldersStr . '/' . $this->_site_->getMethod();
            } else {
                $tpl = $foldersStr . '/' . lcfirst(str_replace('_Controller', '', $controllClassName)) . '/' . $this->_site_->getMethod();
            }
        }
        $view = $this->getView()->set($data)->setPrint($print);
        if ($print) {
            $view->render($tpl);
        } else {
            return $view->render($tpl);
        }
    }

    /**
     * 向模版对象传入变量
     * @param string $name 变量名 只传第一个则$name需要是array
     * @param mixed $value 变量值 传递$value的话$name需要是string
     * @return \Lib\Controller
     */
    protected function assign($name, $value = null) {
        $this->getView()->set($name, $value);
        return $this;
    }

    /**
     * 输出json或jsonp数据, 当url中又callback参数时会输出jsonp
     * @param array $data 要转化的数据
     */
    protected function showJson($data) {
        $json = json_encode($data);
        $callback = $this->url()->get('callback');
        ob_clean();
        if (strlen($callback) > 0) {
            header('Content-type: application/x-javascript;charset=utf-8');
            echo "{$callback}({$json});";
        } else {
            header('Content-type: application/json;charset=utf-8');
            echo $json;
        }
    }
}

?>
