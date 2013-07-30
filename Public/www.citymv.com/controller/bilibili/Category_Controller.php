<?php
namespace Controller;
class Category_Controller extends Admin_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function index() {
        $category = new \Lib\Mysql\Category();
        $queries = array('level', 'parentId', 'englishName');
        $url = $this->url();
        $where = array();
        foreach ($queries as $property) {
            if ($property == 'englishName' && (${$property} = $url->get($property))) {
                $where[] = array($property, "like '%{${$property}}%'");
                continue;
            }
            if (strlen($propertyVal = $url->get($property)) > 0) {
                $category->{$property} = $propertyVal;
            }
        }
        $pageResult = $category->pageResult(array(
            'page' => $this->url()->get('page'),
            'whereAnd' => $where,
        ));
        $this->render(compact('pageResult', 'category', 'englishName'));
    }

    public function add() {
        $url = $this->url();
        $category = new \Lib\Mysql\Category();
        if ($url->isPostMethod()) {
            foreach (array('name', 'englishName', 'status', 'parentId') as $property) {
                $category->{$property} = $url->post($property);
            }
            try {
                $category->save();
                $this->tipSuccess(array('msg' => '保存成功'));
            } catch (\Exception $e) {
                $this->tipAlert(array('msg' => $e->getMessage()));
            }
        }
        $this->render(compact('category'));
    }

    public function edit() {
        $url = $this->url();
        $id = $url->get('id');
        $category = new \Lib\Mysql\Category();
        try {
            $category->load($id);
        } catch (\Exception $e) {
            $this->tipDanger(array('msg' => $e->getMessage()));
        }
        if ($url->isPostMethod()) {
            foreach (array('name', 'englishName', 'status', 'parentId') as $property) {
                $category->{$property} = $url->post($property);
            }
            try {
                $category->save();
                $this->tipSuccess(array('msg' => '保存成功'));
            } catch (\Exception $e) {
                $this->tipAlert(array('msg' => $e->getMessage()));
            }
        }
        $this->prependTitle('编辑分类')->render(compact('category'));
    }
}

?>
