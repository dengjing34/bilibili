<?php
namespace Lib;
/**
 * sitemap类
 *
 * @author jingd
 */
class SiteMap {
    const BAIDU = 'Baiduspider';
    const GOOGLE = 'Googlebot';
    const SOSO = 'Sosospider';
    const SOGOU = 'Sogou';
    const GOOGLEMEDIA = 'Mediapartners-Google';
    const GOOGLEADS = 'AdsBot-Google';
    const SITEMAP_INDEX_TAG_OPEN = "<sitemapindex xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">";
    const SITEMAP_INDEX_TAG_CLOSE = "\n</sitemapindex>";
    const URLSET_TYPE_1 = <<<EOT
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">;
EOT;
    const URLSET_TYPE_2 = <<<EOT
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
EOT;
    const URLSET_TYPE_3 = '<urlset>';
    
    public static $spiderTagMapping = array(
        self::BAIDU => self::URLSET_TYPE_3,
        self::GOOGLE => self::URLSET_TYPE_2,
        self::GOOGLEADS => self::URLSET_TYPE_2,
        self::GOOGLEMEDIA => self::URLSET_TYPE_2,
        self::SOSO => self::URLSET_TYPE_1,
        self::SOGOU => self::URLSET_TYPE_2
    );
    private $spider;

    public function __construct() {
        
    }

    /**
     * 根据ua获取爬虫名称
     * @return string
     */
    public function getSpider() {
        if (is_null($this->spider)) {
            $this->spider = preg_match("/(" . implode('|', array_keys(self::$spiderTagMapping)) .")/i", $_SERVER['HTTP_USER_AGENT'], $matches) ? $matches[1] : null;
        }
        return $this->spider;
    }

    /**
     * 生成sitemap的索引文件
     * @param array $indexes sitemap的具体地址
     * <pre>
     * array(
     * &nbsp;&nbsp;'http://www.**.com/sitemap/category/sitemap.xml'
     * &nbsp;&nbsp;...
     * &nbsp;&nbsp;...
     * )
     * </pre>
     * @return string
     */
    public function generateIndex(array $indexes) {
        $xml = self::SITEMAP_INDEX_TAG_OPEN;
        $lastmod = date('Y-m-d');
        foreach ($indexes as $index) {
            $xml .= <<<EOT

<sitemap>
    <loc>{$index}</loc>
    <lastmod>{$lastmod}</lastmod>
</sitemap>
EOT;
        }
        $xml .= self::SITEMAP_INDEX_TAG_CLOSE;
        return $xml;
    }

    /**
     * 根据爬虫ua来决定使用对应的urlset标签信息
     * @return type
     */
    private function getUrlSetOpenTagBySpider() {
        $spider = $this->getSpider();
        return isset(self::$spiderTagMapping[$spider]) ? self::$spiderTagMapping[$spider] : self::URLSET_TYPE_2;
    }

    /**
     * 生成sitemap的xml内容
     * @param array $sitemap 各链接的地址,修改频率,优先级,构成的数组
     * <pre>
     * array(
     * &nbsp;&nbsp;array(
     * &nbsp;&nbsp;&nbsp;&nbsp;'loc' => 'http://www.***.com/xyz.html',
     * &nbsp;&nbsp;&nbsp;&nbsp;'lastmod' => 'YYYY-MM-DD',
     * &nbsp;&nbsp;&nbsp;&nbsp;'changefreq' => 'hourly|weekly|daily|monthly',
     * &nbsp;&nbsp;&nbsp;&nbsp;'priority' => '0.1-0.9'
     * &nbsp;&nbsp;)
     * &nbsp;&nbsp;...
     * &nbsp;&nbsp;...
     * )
     * </pre>
     * @return string
     */
    public function generateSiteMap(array $sitemap) {
        $xml = $this->getUrlSetOpenTagBySpider();
        $defaultLastmod = date('Y-m-d');
        foreach ($sitemap as $eachSitemap) {
            $lastmod = isset($eachSitemap['lastmod']) ? $eachSitemap['lastmod'] : $defaultLastmod;
            $xml .= <<<EOT

<url>
    <loc>{$eachSitemap['loc']}</loc>
    <lastmod>{$lastmod}</lastmod>
    <changefreq>{$eachSitemap['changefreq']}</changefreq>
    <priority>{$eachSitemap['priority']}</priority>
</url>
EOT;
        }
        $xml .= "\n</urlset>";
        return $xml;
    }
}

?>
