<?php

/**
 * Created by PhpStorm.
 * User: khuen
 * Date: 16-Aug-17
 * Time: 09:52
 */
class Verifier extends CI_Controller
{
    const NO_OF_IMG = 100;

    public function __construct()
    {
        parent::__construct();

        $this->load->database();
        $this->load->helper('url');
        $this->load->library('session');
        $this->load->model('image_model');
    }

    public function index()
    {
        $this->session->set_userdata(array(
            'count' => 0,
            'correct' => 0
        ));

        $page_data = array(
            'content_template' => 'verifier/index',
            'content_data' => array(
                'total' => self::NO_OF_IMG
            ),
            'title' => 'Manually Verify GeoParser Accuracy'
        );
        $this->load->view('master', $page_data);
    }

    public function verify($correct = NULL)
    {
        // Add to count
        $count = $this->session->userdata('count');
        $this->session->set_userdata('count', $count + 1);

        // Add to correct result
        if ($correct == 1) {
            $correct = $this->session->userdata('correct');
            $this->session->set_userdata('correct', $correct + 1);
        }

        // Initialize page data
        $page_data = array(
            'content_template' => 'verifier/verify',
            'title' => 'Verify the image geo location accuracy'
        );

        // Check if we finished verifying all images and display results
        if ($this->session->userdata('count') === self::NO_OF_IMG) {
            $page_data = array(
                'content_template' => 'verifier/results',
                'content_data' => array(
                    'correct' => $this->session->userdata('correct'),
                    'total' => self::NO_OF_IMG
                ),
                'title' => 'Results of Manual Verification'
            );
            $this->session->set_userdata(array(
                'count' => 0,
                'correct' => 0
            ));
        } else {
            // Get random image content
            $image = $this->image_model->get_random_image();
            $page_data['content_data'] = array(
                'image' => $image,
                'correct' => $this->session->userdata('correct'),
                'total' => self::NO_OF_IMG,
                'count' => $this->session->userdata('count')
            );
        }

        $this->load->view('master', $page_data);
    }
}