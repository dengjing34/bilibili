<?php
namespace Controller;
/**
 * 前端的contrller基类
 *
 * @author dengjing
 */
abstract class Base_Controller extends \Lib\Controller{

    private $categories = array();
    
    public function __construct() {
        parent::__construct();
        $this->prependStatic(array(
            'css' => array('bootstrap.min.css', 'bootstrap-responsive.min.css', 'main.css'),
        ))->appendStatic(array(
            'js' => array('jquery-1.9.1.min.js', 'bootstrap.min.js'),
        ))->setTitle('citymv')->setCategories();
    }

    /**
     * 调用模版文件
     * @param array $data 需要传入模版的参数
     * @param boolean $print 是否打印出内容
     * @param string $tpl 模版文件路径,只需要传递*.tpl.php中的*, 不传递则自动检测
     */
    protected function render(array $data = array(), $print = true, $tpl = null) {
        $url = $this->url();
        $categoryEnglishName = $url->get('categoryEnglishName');
        $header = new \Lib\View(array(
            'prependStatic' => $this->getPrependStatic(),
            'categories' => $this->getCategories(),
            'categoryEnglishName' => $categoryEnglishName,
            'title' => $this->getTitle(),
            'breadcrumb' => $this->getBreadcrumb(),
        ));
        $header->setPrint(true)->render('header');
        parent::render($data, $print, $tpl);
        $footer = new \Lib\View(array(
            'appendStatic' => $this->getAppentStatic(),
            'analyticsCodes' => $this->analyticsCodes(),
        ));
        $footer->setPrint(true)->render('footer');
    }

    /**
     * 把所有所有的分类数据放入属性中
     * @return \Controller\Base_Controller
     */
    protected function setCategories() {
        if (empty($this->categories)) {
            $category = new \Lib\Mysql\Category();
            $this->categories = $category->categories();
        }
        return $this;
    }

    /**
     * 获取所有分类数据
     * @return array
     */
    protected function getCategories() {
        return $this->categories;
    }

    /**
     * 通过英文名字在apc中遍历查找对应的分类
     * @param string $englishName 分类英文名
     * @return \Lib\Mysql\Category 分类对象 找不到返回false
     */
    protected function loadCategoryByEnglishName($englishName) {
        $result = false;
        foreach ($this->getCategories() as $first) {
            if ($first->englishName == $englishName) {
                $result = $first;
                break;
            }
            foreach ($first->children as $second) {
                if ($second->englishName == $englishName) {
                    $result = $second;
                    break 2;
                }
            }
        }
        return $result;
    }

    /**
     * 通过id在apc中遍历查找对应的分类
     * @param int $id 分类id
     * @return \Lib\Mysql\Category 分类对象 找不到返回false
     */
    protected function loadCategoryById($id) {
        $result = false;
        foreach ($this->getCategories() as $first) {
            if ($first->id == $id) {
                $result = $first;
                break;
            }
            foreach ($first->children as $second) {
                if ($second->id == $id) {
                    $result = $second;
                    break 2;
                }
            }
        }
        return $result;
    }

    /**
     * 统计代码,本地环境不会出现
     * @return string
     */
    protected function analyticsCodes() {
        try {
            $onlineIps = \Lib\Config::load('ip.' . SERVER_IP_LIST);
        } catch (\Exception $e) {
            $onlineIps = array();
        }
        $codes = '';
        if (in_array($_SERVER['SERVER_ADDR'], $onlineIps)) {
            $codes = <<<EOT
<script>
var _hmt = _hmt || [];
(function() {
    var hm = document.createElement("script");
    hm.src = "//hm.baidu.com/hm.js?4432751931a37ee478ddbfa0de51a3c5";
    var s = document.getElementsByTagName("script")[0];
    s.parentNode.insertBefore(hm, s);
})();
var _gaq = _gaq || [];
_gaq.push(['_setAccount', 'UA-23785848-1']);
_gaq.push(['_trackPageview']);

(function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
})();
</script>
EOT;
        }
        return $codes;
    }
}

?>
