<?php
namespace Controller;
class SiteMap_Controller extends \Lib\Controller{
    const PAGE_LIMIT = 1000;

    public function __construct() {
        parent::__construct();
    }

    public function index() {
        $url = $this->url();
        $indexes = array(
            $url->link('sitemap_category.xml'),
        );
        $post = new \Lib\Mysql\Posts();
        $total = $post->setActive()->count();
        $maxPage = ceil($total / self::PAGE_LIMIT);
        for ($i = 1; $i <= $maxPage; $i++) {
            $indexes[] = $url->link('sitemap_post_' . $i . '.xml');
        }
        $siteMap = new \Lib\SiteMap();
        $this->showXml($siteMap->generateIndex($indexes));
    }

    public function category() {
        $url = $this->url();
        $category = new \Lib\Mysql\Category();
        $categories = $category->categories();
        $urls = array(
            array('loc' => $url->link(), 'changefreq' => 'daily', 'priority' => 0.9)
        );
        foreach ($categories as $first) {
            $urls[] = array(
                'loc' => $url->link($first->englishName),
                'changefreq' => 'daily',
                'priority' => 0.8,
            );
            foreach ($first->children as $second) {
                $urls[] = array(
                    'loc' => $url->link($second->englishName),
                    'changefreq' => 'daily',
                    'priority' => 0.8,
                );
            }
        }
        $siteMap = new \Lib\SiteMap();
        $this->showXml($siteMap->generateSiteMap($urls));
    }

    public function post() {
        $url = $this->url();
        $page = $url->get('page');
        if (!ctype_digit($page)) {
            $page = 1;
        }
        $postSearcher = new \Lib\Solr\Posts();
        $pageResult = $postSearcher->setPage($page)->setRows(self::PAGE_LIMIT)->sort(array('id' => 'desc'))->search();
        $urls = array();
        /*@var $doc \Lib\Mysql\Posts*/
        foreach ($pageResult['docs'] as $doc) {
            $urls[] = array(
                'loc' => $url->link($doc->postUrl()),
                'lastmod' => $doc->updatedTime('Y-m-d'),
                'changefreq' => 'weekly',
                'priority' => 0.8,
            );
        }
        $siteMap = new \Lib\SiteMap();
        $this->showXml($siteMap->generateSiteMap($urls));
    }
}

?>
