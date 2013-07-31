<?php
namespace Controller;
class Search_Controller extends Base_Controller{

    public function __construct() {
        parent::__construct();
    }

    public function index() {
        $url = $this->url();
        $q = $url->get('q');
        $postsSearcher = new \Lib\Solr\Posts();
        $pageResult = $postsSearcher->defaultQuery($q)->query('status', \Lib\Mysql\Posts::STATUS_ACTIVE)->search();
        $this->render(compact('pageResult'), true, 'search');
    }
}

?>
