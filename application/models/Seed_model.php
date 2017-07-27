<?php

/**
 * Created by PhpStorm.
 * User: khuen
 * Date: 07-Jul-17
 * Time: 12:32
 */
class Seed_model extends Core_model
{
    protected $table_name = 'seed_pages';
    public $url = '';
    public $url_desc = '';
    public $finished = false;

    /**
     * Page_model constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get unfinished seed's url
     * @return bool|array
     */
    public function get_unfinished_seed()
    {
        $seed = $this->db->get_where($this->table_name, array('finished' => 0))->row_array();

        if ($seed) {
            return $seed;
        }

        return false;
    }

    /**
     * @param array $seed_data
     */
    public function mark_as_finish($seed_data)
    {
        $this->db->where('url', $seed_data['url']);
        $this->db->update($this->table_name, array('finished' => 1));
    }

    /**
     * Get all seed pages info
     * @return mixed
     */
    public function get_seeds()
    {
        return $this->db->get($this->table_name)->result();
    }
}