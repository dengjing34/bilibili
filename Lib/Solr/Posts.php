<?php
namespace Lib\Solr;
class Posts extends Searcher {
    public function __construct() {
        $options = array(
            'core' => SOLR_CORE_JM_CITYMV_POSTS,
            'fieldList' => array(
                'id',
            ),
            'dbObject' => '\Lib\Mysql\Posts',
            'uniqueKey' => 'id',
        );
        parent::init($options);
    }
}

?>
