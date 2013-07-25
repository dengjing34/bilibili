<?php
namespace Lib\Mysql;
class Product extends Data {
    public $productId, $productShortName, $categoryId;
    const TABLE_NAME = 'products';
    public function __construct() {
        $options = array(
            'db' => MYSQL_DBNAME_KB,
            'table' => self::TABLE_NAME,
            'key' => 'productId',
            'columns' => array(
                'productId' => 'product_id',
                'productShortName' => 'product_short_name',
                'categoryId' => 'category_id',
            ),
            'saveNeeds' => array(),
            //'searcher' => 'JMProductSearcher',//指定searcher的类名
        );
        parent::init($options);
    }
}

?>
