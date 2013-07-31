<?php
namespace Controller;
class Home_Controller extends Base_Controller{
    public function __construct() {
        parent::__construct();
    }

    public function index() {
        $this->render();
    }

    public function category() {
        $postsSearcher = new \Lib\Solr\Posts();
        $url = $this->url();
        $categoryEnglishName = $url->get('categoryEnglishName');
        $category = $this->loadCategoryByEnglishName($categoryEnglishName);
        if ($category) {
            if (isset($category->children)) {
                $postsSearcher->query('parentCategoryId', $category->id);
            } else {
                $postsSearcher->query('categoryId', $category->id);
            }
            $pageResult = $postsSearcher->query('status', \Lib\Mysql\Posts::STATUS_ACTIVE)
                    ->search();
            $this->render(compact('pageResult'));
        } else {
            $this->pageNotFound();
        }
    }

    public function post() {
        $url = $this->url();
        $categoryEnglishName = $url->get('englishName');
        $id = $url->get('id');
        try {
            $post = new \Lib\Mysql\Posts();
            $post->load($id);
            $this->render(compact('categoryEnglishName', 'post'));
        } catch (\Exception $e) {
            $this->pageNotFound();
        }
    }
}

?>
