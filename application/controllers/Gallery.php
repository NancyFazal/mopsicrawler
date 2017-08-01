<?php

/**
 * Created by PhpStorm.
 * User: khuen
 * Date: 01-Aug-17
 * Time: 15:37
 */
class Gallery extends CI_Controller
{
    protected $table_name = 'images';

    public function __construct() {
        parent::__construct();
        $this->load->helper('url');
        $this->load->database();
    }

    public function index()
    {
        /**
         * @var CI_DB_mysqli_driver $db
         */
        $db =& $this->db;
        $images = $db->get($this->table_name, 50)->result_array();
        $page_data = array(
            'content_template' => 'gallery/index',
            'content_data' => array(
                'images' => $images
            ),
            'css_files' => array(
                base_url() . 'assets/css/gallery/font-awesome.min.css',
                base_url() . 'assets/css/gallery/gallery.css',
            ),
            'title' => 'Gallery'
        );
        $this->load->view('master', $page_data);
    }

    public function view($id)
    {

    }

    public function update($data)
    {

    }
}