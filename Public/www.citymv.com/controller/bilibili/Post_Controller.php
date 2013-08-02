<?php
namespace Controller;
class Post_Controller extends Admin_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function index() {
        $postSearcher = new \Lib\Solr\Posts();
        $url = $this->url();
        $q = $url->get('q');
        if ($q) {
            $postSearcher->defaultQuery($q);
        }
        $filter = array('categoryId', 'parentCategoryId');
        foreach ($filter as $field) {
            if ((${$field} = $url->get($field)) != '') {
                $postSearcher->query($field, ${$field});
            }
        }
        $pageResult = $postSearcher->sort(array('id' => 'desc'))->setPage($url->get('page'))->search();
        $this->render(compact('pageResult', 'q', 'categoryId', 'parentCategoryId'));
    }

    public function add() {
        $url = $this->url();
        $this->appendStatic(array(            
            'js' => array('editor/kindeditor-min.js'),
        ));
        $post = new \Lib\Mysql\Posts();
        if ($url->isPostMethod()) {
            $properties = array('title', 'categoryId', 'tags', 'status', 'content');
            foreach ($properties as $property) {
                $post->{$property} = $url->post($property);
            }
            $user = $this->getLoginUser();
            $post->userId = $user['id'];
            $post->userNickname = $user['nickname'];
            try {
                $post->save();
                $this->tipSuccess(array('msg' => '保存成功,文章id:' . $post->id));
            } catch (\Exception $e) {
                $this->tipAlert(array('msg' => $e->getMessage()));
            }
        }
        $category = new \Lib\Mysql\Category();
        $categories = $category->categoriesFormSelect('categoryId', $post->categoryId);
        $this->render(compact('categories', 'post'));
    }

    public function edit() {
        $this->appendStatic(array(
            'js' => array('editor/kindeditor-min.js', 'prettify/prettify.js'),
        ))->prependTitle('编辑文章');
        $url = $this->url();
        $id = $url->get('id');
        $post = new \Lib\Mysql\Posts();
        try {
            $post->load($id);
        } catch (\Exception $e) {
            $this->tipDanger(array('msg' => $e->getMessage()));
        }
        if ($url->isPostMethod()) {
            $properties = array('title', 'categoryId', 'tags', 'status', 'content');
            foreach ($properties as $property) {
                $post->{$property} = $url->post($property);
            }
            try {
                $post->setAutoUpdatedTime(true)->save();
                $this->tipSuccess(array('msg' => '保存成功'));
            } catch (\Exception $e) {
                $this->tipAlert(array('msg' => $e->getMessage()));
            }
        }
        $category = new \Lib\Mysql\Category();
        $categories = $category->categoriesFormSelect('categoryId', $post->categoryId);
        $this->render(compact('categories','post'));
    }
}

?>
