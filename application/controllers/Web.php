<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Web extends CI_Controller {

    function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $this->view->render();
    }
}
