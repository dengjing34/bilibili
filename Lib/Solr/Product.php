<?php
namespace Lib\Solr;
class Product extends Searcher {
    public function __construct() {
        $options = array(
            'core' => SOLR_CORE_JM_PRODUCTS,
            'fieldList' => array(
                'product_id'
            ),
            'dbObject' => '\Lib\Mysql\Product',
            'uniqueKey' => 'product_id',
        );
        parent::init($options);
    }
}

?>
