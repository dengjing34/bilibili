<?php
namespace Controller;
class User_Controller extends Admin_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function index() {
        $user = new \Lib\Mysql\User();
        $pageResult = $user->pageResult(array(
            'page' => $this->url()->get('page'),
        ));
        $this->render(array('pageResult' => $pageResult));
    }

    public function add() {
        $url = $this->url();
        $user = new \Lib\Mysql\User();
        if ($url->isPostMethod()) {            
            foreach (array('nickname', 'email', 'status', 'password') as $property) {
                $user->{$property} = $url->post($property);
            }
            try {
                $user->save();
                $this->tipSuccess(array('msg' => '保存成功'));
            } catch (\Exception $e) {
                $this->tipAlert(array('msg' => $e->getMessage()));
            }
        }
        $this->render(array('user' => $user));
    }

    public function edit() {
        $url = $this->url();
        $id = $url->get('id');
        $user = new \Lib\Mysql\User();
        try {
            $user->load($id);
        } catch (\Exception $e) {
            $this->tipDanger(array('msg' => $e->getMessage()));
        }
        if ($url->isPostMethod()) {
            foreach (array('nickname', 'email', 'status', 'password') as $property) {
                $user->{$property} = $url->post($property);
            }
            try {
                $user->save();
                $this->tipSuccess(array('msg' => '保存成功'));
            } catch (\Exception $e) {
                $this->tipAlert(array('msg' => $e->getMessage()));
            }
        }
        $this->prependTitle('编辑用户')->render(compact('user'));
    }
}

?>
