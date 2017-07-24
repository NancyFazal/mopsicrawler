<?php

/**
 * Created by PhpStorm.
 * User: khuen
 * Date: 07-Jul-17
 * Time: 13:34
 */
class Core_model extends CI_Model
{
    /**
     * Name of table attached to this model
     * @var string
     */
    protected $table_name = '';

    /**
     * Core_model constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

        /**
     * @return mixed
     */
    public function get_instance()
    {
        $class_name = get_class($this);
        return new $class_name();
    }
}