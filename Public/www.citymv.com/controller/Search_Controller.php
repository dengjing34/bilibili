<?php
namespace Controller;
class Search_Controller extends Base_Controller{

    public function __construct() {
        parent::__construct();
    }

    public function index() {
        $url = $this->url();
        $q = $url->get('q');
        \Lib\Pager::$pagerExt = false;
        $postsSearcher = new \Lib\Solr\Posts();
        $pageResult = $postsSearcher->defaultQuery($q)
                ->query('status', \Lib\Mysql\Posts::STATUS_ACTIVE)
                ->setPage($url->get('page'))
                ->sort(array('insertedTime' => 'desc'))
                ->search();
        $this->render(compact('pageResult', 'q'), true, 'search');
    }
}

?>
