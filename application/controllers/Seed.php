<?php

/**
 * Created by PhpStorm.
 * User: khuen
 * Date: 27-Jul-17
 * Time: 10:43
 */
class Seed extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->database();
        $this->load->helper('url');

        $this->load->library('grocery_CRUD');
    }

    /**
     * Seed page manager
     */
    public function index()
    {
        try {
            // Initialize CRUD instance
            $crud = new grocery_CRUD();

            $crud->set_theme('datatables');
            $crud->set_table('seed_pages');
            $crud->set_subject('Seed');
            $crud->required_fields('url', 'url_desc', 'host', 'finished');
            $crud->columns('url', 'url_desc', 'host', 'finished');

            // Labeling fields
            $crud->display_as('url','URL');
            $crud->display_as('url_desc','Description');
            $crud->display_as('host','Host');
            $crud->display_as('finished','Crawled?');

            // Set rules

            $crud = $crud->render();

            $page_data = array(
                'title' => 'Seed Index',
                'content_template' => 'seed/index',
                'js_files' => $crud->js_files,
                'css_files' => $crud->css_files,
                'content_data' => (array)$crud,
            );

            $this->load->view('master', $page_data);

        } catch (Exception $e) {
            show_error($e->getMessage() . ' --- ' . $e->getTraceAsString());
        }
    }
}