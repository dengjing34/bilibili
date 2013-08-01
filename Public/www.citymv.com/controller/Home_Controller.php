<?php
namespace Controller;
class Home_Controller extends Base_Controller{
    public function __construct() {
        parent::__construct();
    }

    public function index() {
        $url = $this->url();
        $postSearcher = new \Lib\Solr\Posts();
        $pageResult = $postSearcher->sort(array('insertedTime' => 'desc'))
                ->setRows(24)
                ->search();
        $this->render(compact('pageResult'));
    }

    public function category() {
        $postsSearcher = new \Lib\Solr\Posts();
        \Lib\Pager::$pagerExt = false;
        $url = $this->url();
        $categoryEnglishName = $url->get('categoryEnglishName');
        $category = $this->loadCategoryByEnglishName($categoryEnglishName);
        $this->prependBreadcrumb(array($category->name => $categoryEnglishName));
        if ($category->parentId > 0) {
            $parentCategory = $this->loadCategoryById($category->parentId);
            $this->prependBreadcrumb(array($parentCategory->name => $parentCategory->englishName));
        }
        if ($category) {
            if (isset($category->children)) {
                $postsSearcher->query('parentCategoryId', $category->id);
            } else {
                $postsSearcher->query('categoryId', $category->id);
            }
            $pageResult = $postsSearcher->query('status', \Lib\Mysql\Posts::STATUS_ACTIVE)
                    ->setPage($url->get('page'))
                    ->sort(array('insertedTime' => 'desc'))
                    ->search();
            $this->prependTitle($category->name . ' - ')->render(compact('pageResult'));
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
            $this->setBreadcrumb(array($post->title => $post->postUrl()))
                    ->prependBreadcrumb(array($post->categoryName => $post->categoryEnglishName))
                    ->prependTitle($post->title . ' - ' . $post->categoryName . ' - ');
            if ($post->parentCategoryId > 0) {
                $this->prependBreadcrumb(array($post->parentCategoryName => $post->parentCategoryEnglishName));
            }
            $this->render(compact('categoryEnglishName', 'post'));
        } catch (\Exception $e) {
            $this->pageNotFound();
        }
    }
}

?>
