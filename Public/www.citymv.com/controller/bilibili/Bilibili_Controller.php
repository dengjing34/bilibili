<?php
namespace Controller;
class Bilibili_Controller extends Admin_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function index() {
        $data = array(
            'enabled' => '<i class="icon-ok-circle"></i>',
            'disabled' => '<i class="icon-ban-circle"></i>',
        );
        $this->render($data);
    }

    public function auth() {
        $error = $nickname = '';
        $url = $this->url();
        if ($url->isPostMethod()) {
            $nickname = $url->post('nickname');
            try {
                if (($user = \Lib\Mysql\User::validateLogin($url->post()))) {
                    $url->redirect($url->link('bilibili'));
                }
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        }
        $this->setTitle('管理登录')->getView(array(
            'title' => $this->getTitle(),
            'prependStatic' => $this->getPrependStatic(),
            'appendStatic' => $this->getAppentStatic(),
            'meta' => $this->getMeta(),
            'error' => $error,
            'nickname' => $nickname
        ))->setPrint(true)->render('bilibili/auth');
    }

    public function quit() {
        \Lib\Mysql\User::logout();
        $url = $this->url();
        $url->redirect($url->link('bilibili/auth'));
    }

    public function profile() {
        $loginUser = $this->getLoginUser();
        try {
            $user = new \Lib\Mysql\User();
            $user->load($loginUser['id']);
        } catch (\Exception $e) {
            $this->tipDanger(array('msg' => $e->getMessage()));
        }
        $url = $this->url();
        if ($url->isPostMethod()) {
            foreach (array('nickname', 'email', 'password', 'status') as $attr) {
                $user->{$attr} = $url->post($attr);
            }
            try {
                $user->save();
                $this->tipSuccess(array('msg' => '保存成功'));
                \Lib\Mysql\User::setLoginUser(array('id' => $user->id, 'nickname' => $user->nickname));
            } catch (\Exception $e) {
                $this->tipAlert(array('msg' => $e->getMessage()));
            }
        }
        $this->prependTitle($loginUser['nickname'])
                ->assign('user', $user)
                ->render();
    }
}

?>
