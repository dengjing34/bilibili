<?php
namespace Controller;
/**
 * 后台的基类,不能实例化
 */
abstract class Admin_Controller extends \Lib\Controller {

    /**
     * 登录用户
     * @var array
     */
    private $_user_ = array();

    /**
     * 各种成功,失败,警告信息
     * @var array
     */
    private $_tip_ = array();

    public function __construct() {
        parent::__construct();
        $this->prependStatic(array(
            'css' => array('bootstrap.min.css', 'bootstrap-responsive.min.css', 'bilibili/main.css'),
            'js' => array('jquery-1.9.1.min.js'),
        ))->appendStatic(array(
            'js' => array('bootstrap.min.js'),
        ))->setTitle(' - 控制面板');        
        $url = $this->url();
        $authUri = 'bilibili/auth';
        $uri = $url->uri();
        $isLogin = \Lib\Mysql\User::isLogin();
        if (!$isLogin && $uri != '/' . $authUri) {
            $url->redirect($url->link($authUri));
        } elseif ($isLogin && $uri == '/' . $authUri) {
            $url->redirect($url->link('bilibili'));
        }
        if ($isLogin) {
            $this->setLoginUser(\Lib\Mysql\User::getLoginUser());
        }
    }

    /**
     * 获取登录用户
     * @return array 包含id, nickname的数组
     */
    protected function getLoginUser() {
        return $this->_user_;
    }


    /**
     * 设置当前登录用户
     * @param array $user
     * @return \Controller\Admin_Controller
     */
    protected function setLoginUser(array $user) {
        $this->_user_ = $user;
        return $this;
    }

    /**
     * 调用模版文件
     * @param array $data 需要传入模版的参数
     * @param boolean $print 是否打印出内容
     * @param string $tpl 模版文件路径,只需要传递*.tpl.php中的*, 不传递则自动检测
     */
    protected function render(array $data = array(), $print = true, $tpl = null) {
        $menu = array(
            'user' => array(
                'text' => '用户管理',
                'sub' => array('user/add' => '添加用户', 'user' => '用户列表'),
            ),
            'category' => array(
                'text' => '分类管理',
                'sub' => array('category/add' => '添加分类', 'category' => '分类列表'),
            ),
            'post' => array(
                'text' => '文章管理',
                'sub' => array('post/add' => '添加文章', 'post' => '文章列表'),
            ),
        );
        $subSegments = '';
        $segments = $this->url()->segments();
        if (count($segments) > 1) {
            $site = $this->getSite();
            $subSegments = str_replace('bilibili/', '', implode('/', $segments));
            foreach ($menu as $menuKey => $eachMenu) {
                if (ucfirst($menuKey) . '_Controller' == $site->getController()) {
                    $menu[$menuKey]['current'] = true;
                }
                if (isset($eachMenu['sub'][$subSegments])) {
                    $this->prependTitle($eachMenu['sub'][$subSegments]);
                }
            }
        } else {
            $this->prependTitle('DashBoard');
        }
        $tip = $this->getTip();
        $header = new \Lib\View(array(
            'prependStatic' => $this->getPrependStatic(),
            'menu' => $menu,
            'subSegments' => $subSegments,
            'title' => $this->getTitle(),
            'meta' => $this->getMeta(),
            'user' => $this->getLoginUser(),
            'tip' => $tip,
        ));
        $header->setPrint(true)->render('bilibili/header');
        //没有危险危险提示才render, 比如id没有找到可以设置tipDanger
        if (!isset($tip['danger'])) {
            parent::render($data, $print, $tpl);
        }
        $footer = new \Lib\View(array(
            'appendStatic' => $this->getAppentStatic(),
        ));
        $footer->setPrint(true)->render('bilibili/footer');
    }

    /**
     * 获取警告,提示,成功,失败信息
     * @return array
     */
    private function getTip() {
        return $this->_tip_;
    }

    /**
     * 设置tip的内容
     * @param array $msg 信息
     * @param string $type 信息类型, 可以是alert,danger,success,info
     * @return \Controller\Admin_Controller
     */
    private function setTip(array $msg, $type = 'alert') {
        $this->_tip_[$type][] = $msg;
        return $this;
    }

    /**
     * 设置警告信息
     * @param array $msg 提示信息内容,包含键msg的数组
     * @return \Controller\Admin_Controller
     */
    protected function tipAlert(array $msg) {
        return $this->setTip($msg);
    }

    /**
     * 设置危险提示信息
     * @param array $msg 提示信息内容,包含键msg的数组
     * @return \Controller\Admin_Controller
     */
    protected function tipDanger(array $msg) {
        return $this->setTip($msg, 'danger');
    }

    /**
     * 设置成功提示信息
     * @param array $msg 提示信息内容,包含键msg的数组
     * @return \Controller\Admin_Controller
     */
    protected function tipSuccess(array $msg) {
        return $this->setTip($msg, 'success');
    }

    /**
     * 设置一般提示信息
     * @param array $msg 提示信息内容,包含键msg的数组
     * @return \Controller\Admin_Controller
     */
    protected function tipInfo(array $msg) {
        return $this->setTip($msg, 'info');
    }
}

?>
