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
        $this->prependStatic(array(
            'css' => array('bootstrap.min.css', 'bootstrap-responsive.min.css'),
        ))->appendStatic(array(
            'js' => array('jquery-1.9.1.min.js', 'bootstrap.min.js'),
        ));
    }

    /**
     * 调用模版文件
     * @param array $data 需要传入模版的参数
     * @param boolean $print 是否打印出内容
     * @param string $tpl 模版文件路径,只需要传递*.tpl.php中的*, 不传递则自动检测
     */
    protected function render(array $data = array(), $print = true, $tpl = null) {
        $header = new \Lib\View(array(
            'prependStatic' => $this->getPrependStatic()
        ));
        $header->setPrint(true)->render('header');
        parent::render($data, $print, $tpl);
        $footer = new \Lib\View(array(
            'appendStatic' => $this->getAppentStatic()
        ));
        $footer->setPrint(true)->render('footer');
    }
}

?>
