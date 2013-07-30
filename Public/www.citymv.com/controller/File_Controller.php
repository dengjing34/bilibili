<?php
namespace Controller;
class File_Controller extends \Lib\Controller{
    function __construct() {
        parent::__construct();
    }

    /**
     * 接收post过来的name为Filedata文件进行保存,可以通过get(inputName)自定义表单file控件名
     * @return void json格式的上传结果信息
     */
    public function upload() {
        $uploader = new \Lib\Uploader();
        $url = $this->url();
        $inputName = $url->get('inputName');
        $this->showJson($uploader->setInputName($inputName)->save());
    }

    public function form() {
        $url = $this->url();
        $query = $url->get();
        $action = !empty($query) ? $url->link('file/upload?' . http_build_query($query)) : $url->link('file/upload');
        $view = new View('base/uplodForm', compact('action'));
        $view->render(true);
    }

    /**
     * kindeditor上传文件入口
     */
    public function editor() {
        $uploader = new \Lib\Uploader();
        try {
            $imagickConfig = \Lib\Config::load('imagick.' . IMAGICK_EDITOR);
        } catch (\Exception $e) {
            $imagickConfig = array();
        }
        if (isset($imagickConfig['thumb'])) {
            foreach ($imagickConfig['thumb'] as $thumbConfig) {
                $uploader->setThumb($thumbConfig['width'], $thumbConfig['height']);
            }
        }
        if (isset($imagickConfig['scale'])) {
            foreach ($imagickConfig['scale'] as $scaleConfig) {
                $uploader->setScale($scaleConfig['direction'], $scaleConfig['base']);
            }
        }
        if (isset($imagickConfig['mask']['text'])) {
            $uploader->setMaskText($imagickConfig['mask']['text']);
        }
        if (isset($imagickConfig['mask']['image'])) {
            $uploader->setMaskImage($imagickConfig['mask']['image']);
        }
        $this->showJson($uploader->setInputName('imgFile')->setMsgTypeUrl()->save());
    }
}
?>
