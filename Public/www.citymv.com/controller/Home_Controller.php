<?php
namespace Controller;
class Home_Controller extends Base_Controller{
    public function __construct() {
        parent::__construct();
    }

    public function index() {
        $this->render();
    }
}

?>
