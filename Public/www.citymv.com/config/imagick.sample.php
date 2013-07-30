<?php
//上传图片时缩放和裁剪配置
return array(
    IMAGICK_EDITOR => array(
        'thumb' => array(
            'small' => array('width' => 64, 'height' => 64),
            'middle' => array('width' => 200, 'height' => 200),
        ),
        'scale' => array(
            'middle' => array('direction' => 'x', 'base' => 200),
        ),
        'mask' => array(
            'text' => 'www.citymv.com',
            'image' => 'waterImage.png',
        ),
    ),
)
?>
