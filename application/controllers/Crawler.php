<?php

/**
 * Created by PhpStorm.
 * User: khuen
 * Date: 05-Jul-17
 * Time: 10:00
 */
class Crawler extends CI_Controller
{
    /**
     * @var Seed_model
     */
    protected $seed_data = null;

    /**
     * Number of pages to be crawl
     * each time the crawler runs
     */
    const NO_OF_PAGES = 999999999;

    public function __construct()
    {
        parent::__construct();
        $this->load_models();
        $this->load->helper('performance');
        $this->load->database();
    }

    /**
     * Main crawling entry point
     */
    public function crawl()
    {
        // Set memory limit
        ini_set('memory_limit', '2G');

        // Set page relevance calculation mode
        $this->page_model->relevance_mode = 3;

        // Initialize seed data for crawling
        $this->initialize_seed_data();
        $num_page = 0;

        // Main crawler loop
        while (!$this->queue_model->queue_is_empty() && $num_page < self::NO_OF_PAGES) {

            try {

                // Get the most relevant url from the queue
                $this->page_model->set_page_data($this->get_next_page())
                    ->set_seed_data($this->seed_data)
                    ->fetch()
                    ->save();

                // Get page's links and extract relevance for crawling
                $this->process_page_links();

                echo '[Success]' . $this->page_model->page_data['url'] . "\n";

                $num_page++;
            } catch (Exception $e) {
                echo '[ Error ]' . $this->page_model->page_data['url'] . "\n";
                continue;
            }

            // Monitor memory usage for each iteration
            echo "[Usage]" . current_memory_usage(true) . "\n";

        }

        if ($this->queue_model->queue_is_empty()) {
            // Mark a seed as finish when all related pages are crawled
            $this->seed_model->mark_as_finish($this->seed_data);
        }

    }

    /**
     * Initialize seed data for crawling
     * @return $this
     */
    protected function initialize_seed_data()
    {
        // Load the next unfinished seed from the database
        $this->seed_data = $this->seed_model->get_unfinished_seed();

        // If all seeds are finished no further crawling is needed (at the moment)
        if (!$this->seed_data) {
            echo 'No more seeds for crawling!';
            exit(0);
        }

        $seed_page = $this->page_model->get_page_data_by_url($this->seed_data['url']);
        if (is_null($seed_page)) {
            // Save the seed & add the seed page to queue
            $this->seed_data['relevance'] = 1;

            // Unset the id that was obtained from seed_pages table
            unset($this->seed_data['id']);

            // Save the seed as a page and retrieve page data
            $this->seed_data = $this->page_model
                ->set_page_data($this->seed_data)
                ->save()
                ->page_data;

            // Add page_id of the seed to seed page's seed_id
            $this->seed_data['seed_id'] = $this->seed_data['id'];

            // Add the seed to the queue
            $this->queue_model->save_to_queue($this->seed_data);
        } else {
            $this->seed_data = $seed_page;
        }

        $this->page_model->clear();

        return $this;
    }

    /**
     * Process links on page
     * @return $this
     */
    protected function process_page_links()
    {
        $links = $this->page_model->get_links();
        foreach ($links as $link) {
            $page_record = $this->page_model->get_page_data_by_url($link['url']);
            if ($link['relevance'] > 0 && is_null($page_record)) {
                $link['parent_id'] = $this->page_model->page_data['id'];
                $link['seed_id'] = $this->page_model->page_data['seed_id'];
                $this->queue_model->save_to_queue($link);
            }
        }

        return $this;
    }

    /**
     * Get the most relevant page out of the queue
     * @return mixed
     */
    protected function get_next_page()
    {
        // Get the most relevant url from the queue
        $page_data = $this->queue_model->get_most_relevant();
        $this->queue_model->remove_from_queue($page_data['id']);
        return $page_data;
    }

    /**
     * Load all models
     */
    protected function load_models()
    {
        $this->load->model(array(
            'queue_model',
            'seed_model',
            'page_model'
        ));
    }
}