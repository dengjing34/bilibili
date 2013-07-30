<?php
namespace Lib;

class Uploader {
    /**
     * 创建文件夹的格式 按天创建
     */
    const DIR_TYPE_DAY = 1;
    /**
     * 创建文件夹的格式 按月创建
     */
    const DIR_TYPE_MONTH = 2;
    /**
     * 默认的表单控件名字
     */
    const INPUT_NAME = 'fileData';

    /**
     * 创建文件夹的格式
     * @var array
     */
    public static $dirTypes = array(
        self::DIR_TYPE_DAY => 'Ymd',
        self::DIR_TYPE_MONTH => 'Ym',
    );
    /**
     * 输出信息的种类,1为直接输出上传成功的图片url
     */
    const MSG_TYPE_URL = 1;

    /**
     * 输出信息的种类, 2为输出json的信息
     */
    const MSG_TYPE_JSON = 2;

    const UPLOAD_ERR_NOT_IMAGE = 9000;
    const UPLOAD_ERR_MAX_FILE_SIZE = 9001;
    const UPLOAD_ERR_NOT_ALLOW_FILE = 9002;
    const UPLOAD_ERR_MOVE_FILE_FAILED = 9003;
    public static $uploadErr = array(
        UPLOAD_ERR_INI_SIZE => '上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值',
        UPLOAD_ERR_FORM_SIZE => '上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值',
        UPLOAD_ERR_PARTIAL => '文件只有部分被上传',
        UPLOAD_ERR_NO_FILE => '没有文件被上传',
        UPLOAD_ERR_NO_TMP_DIR => '找不到临时文件夹',
        UPLOAD_ERR_CANT_WRITE => '文件写入失败',
        self::UPLOAD_ERR_NOT_IMAGE => '文件不是图片格式',
        self::UPLOAD_ERR_MAX_FILE_SIZE => '上传文件大小超过限制',
        self::UPLOAD_ERR_NOT_ALLOW_FILE => '不允许上传此扩展名的文件',
        self::UPLOAD_ERR_MOVE_FILE_FAILED => '移动文件时失败',
    );

    private $inputName = self::INPUT_NAME;
    private $dirType = self::DIR_TYPE_MONTH;
    private $rootDir;
    private $attachDir;
    private $uploadDir;
    /**
     * 最大上传文件大小默认2M
     * @var int
     */
    private $maxSize = 2097152;
    private $msgType = self::MSG_TYPE_JSON;
    /**
     * 允许上传的文件格式
     * @var array
     */
    private $allowFiles = array('txt', 'rar', 'zip', 'jpg', 'jpeg', 'gif', 'png', 'swf', 'wmv', 'avi', 'wma', 'mp3', 'mid');
    /**
     * 需要生成缩略图的样式
     * @var array
     */
    private $thumbs = array();
    /**
     * 设置基于宽高比例的放大、缩小的样式
     * @var array
     */
    private $scale = array();
    /**
     * 水印文字
     * @var string
     */
    private $maskText;

    /**
     * 水印图片地址
     * @var string 
     */
    private $maskImage;

    const WATER_MARK_TEXT = 'text', WATER_MARK_IMAGE = 'image';
    
    function __construct() {
        $this->rootDir = $_SERVER['DOCUMENT_ROOT'];//webserver(apache,nginx)配置的站点根目录
        $this->uploadDir = defined('UPLOAD_PATH') ? UPLOAD_PATH : 'files';
        $this->attachDir = $this->rootDir . $this->uploadDir; //上传文件保存路径，结尾不要带/
    }

    /**
     * 指定缩略图的尺寸,多次设定会产生多个
     * @param int $width 宽度
     * @param int $height 高度
     * @return \Lib\Uploader
     */
    public function setThumb($width, $height) {
        if (ctype_digit((string)$width) && ctype_digit((string)$height)) {
            $this->thumbs[] = array(
                'width' => $width,
                'height' => $height,
            );
        }
        return $this;
    }

    /**
     * 设置基于宽高比例的放大、缩小,多次设定会产生多个
     * @param string $direction 基准边
     * @param int $base 基准值
     * @return \Lib\Uploader
     */
    public function setScale($direction, $base) {
        if (in_array($direction, array('x', 'y')) && ctype_digit((string)$base) && $base > 0) {
            $this->scale[] = array('direction' => $direction, 'base' => $base);
        }
        return $this;
    }

    /**
     * 设置水印文字
     * @param string $text 水印文字
     * @return \Lib\Uploader
     */
    public function setMaskText($text) {
        if (strlen($text) > 0) {
            $this->maskText = $text;
        }
        return $this;
    }

    /**
     * 设置水印图片路径
     * @param string $imagePath 水印图片路径
     * @return \Lib\Uploader
     */
    public function setMaskImage($imagePath) {
        if (is_file($imagePath)) {
            $this->maskImage = $imagePath;
        }
        return $this;
    }

    /**
     * 设定按照哪种日期格式创建上传目录
     * @param int $type 目前有1和2可选
     * @return \Lib\Uploader
     */
    public function setDirType($type) {
        if (in_array($type, array_keys(self::$dirTypes))) {
            $this->dirType = $type;
        }
        return $this;
    }

    /**
     * 获取按照哪种日期格式创建目录
     * @return string
     */
    private function getDirType() {
        return date(self::$dirTypes[$this->dirType]);
    }

    /**
     * 指定上传文件在表单中的控件名字
     * @param string $inputName 若为空则还是为默认的
     * @return \Lib\Uploader
     */
    public function setInputName($inputName) {
        if (strlen($inputName) > 0) {
            $this->inputName = $inputName;
        }
        return $this;
    }

    /**
     * 重新设置允许上传的文件后缀名称
     * @param string||array $fileExtension 字符或数组构成的多个文件后缀
     * @return \Lib\Uploader
     */
    public function setAllowFiles($fileExtension) {
        if (is_array($fileExtension)) {
            $this->allowFiles = $fileExtension;
        } else {
            $this->allowFiles = array($fileExtension);
        }
        return $this;
    }
    /**
     * 设置为只允许上传图片
     * @return \Lib\Uploader
     */
    public function setAllowImages() {
        $this->setAllowFiles(array('jpg', 'jpeg', 'gif', 'png'));
        return $this;
    }

    /**
     * 新增一些允许上传的文件类型
     * @param string||array $fileExtension 字符或数组构成的多个文件后缀
     * @return \Lib\Uploader
     */
    public function addAllowFiles($fileExtension) {
        if (is_array($fileExtension)) {
            $this->allowFiles = array_merge($this->allowFiles, $fileExtension);
        } else {
            $this->allowFiles[] = $fileExtension;
        }
        return $this;
    }

    /**
     * 指定msgtype为直接返回url
     * @return \Lib\Uploader
     */
    public function setMsgTypeUrl() {
        $this->msgType = self::MSG_TYPE_URL;
        return $this;
    }
    /**
     * 指定msgtype为返回json
     * @return \Lib\Uploader
     */
    public function setMsgTypeJson() {
        $this->msgType = self::MSG_TYPE_JSON;
        return $this;
    }

    /**
     * 获取上传文件出错的信息
     * @param int $erroNo 错误号
     * @return string
     */
    private function getError($erroNo) {
        return isset(self::$uploadErr[$erroNo]) ? self::$uploadErr[$erroNo] : '未知错误:' . $erroNo;
    }

    /**
     * 保存文件并返回文件路径信息
     * @return array err为0则没有错误
     */
    function save() {
        $err = $msg = '';
        $upfile = isset($_FILES[$this->inputName]) ? $_FILES[$this->inputName] : null;
        if (is_null($upfile)) {
            $err = UPLOAD_ERR_NO_FILE;
            $msg = $this->getError($err);
        } elseif ($upfile['error'] != UPLOAD_ERR_OK) {
            $err = $upfile['error'];
            $msg = $this->getError($upfile['error']);
        } else {
            $originFileInfo = pathinfo($upfile['name']);
            $extension = $originFileInfo['extension'];
            if (in_array(strtolower($extension), $this->allowFiles)) {
//                if (in_array($extension, array('jpg', 'jpeg', 'gif', 'png')) && strtolower(current(explode('/', $upfile['type']))) != 'image') {
//                    $err = self::UPLOAD_ERR_NOT_IMAGE;
//                    $msg = $this->getError($err);
//                } else
                if ($upfile['size'] > $this->maxSize) {
                    $err = self::UPLOAD_ERR_MAX_FILE_SIZE;
                    $msg = $this->getError($err);
                } else {
                    if (!is_dir($this->attachDir)) {
                        mkdir($this->attachDir, 0777);
                    }
                    $createdDir = $this->attachDir . $this->getDirType();
                    if (!is_dir($createdDir)) {
                        mkdir($createdDir, 0777);
                    }
                    $filename = date("Ymd") . '_' . date('His') . '_' . rand(1000, 9999) . '.' . $extension;
                    $target = $this->rootDir ? str_replace($this->rootDir, '', $createdDir . '/' . $filename) : $createdDir . '/' . $filename; //插入编辑器的文件路径 若使用绝对路径则插入编辑器的内容要去掉$this->rootdir
                    $targetfile = $createdDir . '/' . $filename; //上传的文件地址
                    //move_uploaded_file($upfile['tmp_name'],$target);
                    if (move_uploaded_file($upfile['tmp_name'], $targetfile) === false) {
                        $err = self::UPLOAD_ERR_MOVE_FILE_FAILED;
                        $msg = $this->getError($err);
                    } else {
                        $err = UPLOAD_ERR_OK;
                        if ($this->msgType == self::MSG_TYPE_URL) {
                            $msg = $target;
                        } else {
                            $msg = array(
                                'url' => $target,
                                'originFile' => $upfile['name'],
                                'dbPath' => str_replace($this->uploadDir, '', $target)
                            );
                        }
                        //如有设置,生成缩略图
                        foreach ($this->thumbs as $eachThumb) {
                            $this->thumb($targetfile, $eachThumb);
                        }
                        //如有设置,生成基于宽高比例的放大、缩小图片
                        foreach ($this->scale as $eachScale) {
                            $this->scale($targetfile, $eachScale);
                        }
                        //如有设置,生成设置的文字或图片水印
                        $this->mask($targetfile, $this->maskText, $this->maskImage);
                    }

                }
            } else {
                $err = self::UPLOAD_ERR_NOT_ALLOW_FILE;
                $msg = $this->getError($err);
            }
            //@unlink($temppath);
        }
        if ($this->msgType == self::MSG_TYPE_URL) {
            $result = array('error' => $err, 'url' => $msg);
        } else {
            $result = array('error' => $err, 'msg' => $msg);
        }
        return $result;
    }

    /**
     * 生成缩略图
     * @param string $originFile 原始文件路径
     * @param array $thumbSize 设置的缩略图尺寸
     * @return \Lib\Uploader
     */
    private function thumb($originFile, array $thumbSize) {
        if (!empty($thumbSize) && is_file($originFile)) {
            $pathInfo = pathinfo($originFile);
            $thumbFile = "{$pathInfo['dirname']}/{$pathInfo['filename']}_{$thumbSize['width']}_{$thumbSize['height']}.{$pathInfo['extension']}";
            $imagick = new ImageImagick($originFile);
            $imagick->thumb($thumbSize['width'], $thumbSize['height'])->save($thumbFile);
        }
        return $this;
    }

    /**
     * 基于宽高比例的放大、缩小
     * @param string $originFile 原始文件路径
     * @param array $scale 设置的缩放基准边和基准值构成的数组
     * @return \Lib\Uploader
     */
    private function scale($originFile, array $scale) {
        if (!empty($scale) && is_file($originFile)) {
            $pathInfo = pathinfo($originFile);
            $scaleFile = "{$pathInfo['dirname']}/{$pathInfo['filename']}_{$scale['base']}.{$pathInfo['extension']}";
            $imagick = new ImageImagick($originFile);
            $imagick->scale($scale['direction'], $scale['base'])->save($scaleFile);
        }
        return $this;
    }

    /**
     * 生成文字水印
     * @param string $originFile 原始文件路径
     * @param string $maskText 水印文字
     * @param string $maskImage 图片水印文件地址
     * @return \Lib\Uploader
     */
    private function mask($originFile, $maskText, $maskImage) {
        if ((strlen($maskText) > 0 || strlen($maskImage) > 0) && is_file($originFile)) {
            $imagick = new ImageImagick($originFile);
            if (strlen($maskText) > 0) {
                $text = new ImageImagick();
                $text->text($maskText, 'Helvetica.ttf');
                $imagick->water($text, 'left-bottom');
            }
            if (strlen($maskImage) > 0 && is_file($maskImage)) {
                $image = new ImageImagick($maskImage);
                $imagick->water($image);
            }
            $imagick->save();
        }
        return $this;
    }

}

?>