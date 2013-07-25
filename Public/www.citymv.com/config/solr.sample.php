<?php
return array(
    SOLR_CORE_JM_CITYMV_POSTS => array(
        'master' => array(
            'host' => 'http://localhost:8080/solr-multi-cores/',
        ),
        'slave' => array(
            'host' => 'http://127.0.0.1:8080/solr-multi-cores/',
        ),
    ),
);
?>