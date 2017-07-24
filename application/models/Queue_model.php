<?php

/**
 * Created by PhpStorm.
 * User: khuen
 * Date: 08-Jul-17
 * Time: 09:10
 */
class Queue_model extends Core_Model
{
    protected $table_name = 'url_queue';

    protected $property_list = array('id', 'parent_id', 'seed_id', 'url', 'url_desc', 'host', 'relevance');

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Return the most relevant url from the queue table
     * @return array|bool
     */
    public function get_most_relevant()
    {
        $this->db->select('id, parent_id, seed_id, url, url_desc, host');
        $this->db->select_max('relevance');
        $result = $this->db->get($this->table_name)->row_array();
        if ($result) {
            return $result;
        }
        return false;
    }

    /**
     * Remove a link from queue
     * @param $id
     * @return $this
     */
    public function remove_from_queue($id)
    {
        try {
            $this->db->where('id', $id);
            $item = $this->db->get($this->table_name)->row();
            if (!is_null($item)) {
                $this->db->delete($this->table_name, array('id' => $id));
            }
        } catch (Exception $e) {

        }
        return $this;
    }

    /**
     * Save url data to queue
     * @param $data
     * @return $this
     */
    public function save_to_queue($data)
    {
        $data = array_filter($data, function($k) {
            return in_array($k, $this->property_list);
        }, ARRAY_FILTER_USE_KEY);

        try {
            $this->db->where('url', $data['url']);
            $item = $this->db->get($this->table_name)->row();
            if (is_null($item)) {
                $this->db->insert($this->table_name, $data);
            }
        } catch (Exception $e) {

        }
        return $this;
    }

    /**
     * Check if the queue is empty
     * @return bool
     */
    public function queue_is_empty()
    {
        $this->db->select('COUNT(*) AS queue_size');
        $result = $this->db->get($this->table_name)->row();
        return ($result->queue_size <= 0);
    }
}