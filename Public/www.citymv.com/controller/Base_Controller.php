<?php
namespace Controller;
/**
 * 前端的contrller基类
 *
 * @author dengjing
 */
class Base_Controller extends \Lib\Controller{

    public function __construct() {
        parent::__construct();
    }

    protected function render(array $data = array(), $print = true, $tpl = null) {
        $header = new \Lib\View();
        $header->setPrint(true)->render('header');
        parent::render($data, $print, $tpl);
        $footer = new \Lib\View();
        $footer->setPrint(true)->render('footer');
    }
}

?>
