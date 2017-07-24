<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class CI_Curl
{
    protected $CI;
    protected $errorMessage;

    public $timeout = 60;
    public $return_transfer = true;
    public $include_header = false;
    public $ignore_ssl = true;

    public function __construct($params = array())
    {
        $this->CI =& get_instance();
    }

    public function get($url, $options = array())
    {
        try {
            $ch = curl_init();
            $opts = array(
                CURLOPT_HEADER => $this->include_header,
                CURLOPT_SSL_VERIFYHOST => !$this->ignore_ssl,
                CURLOPT_SSL_VERIFYPEER => !$this->ignore_ssl,
                CURLOPT_CONNECTTIMEOUT => $this->timeout,
                CURLOPT_RETURNTRANSFER => $this->return_transfer,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_AUTOREFERER => true,
                CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/59.0.3071.115 Safari/537.36",
                CURLOPT_URL => $url,
            );
            curl_setopt_array($ch, $opts + $options);
            $data = curl_exec($ch);

            if (!$data) {
                $this->errorMessage = curl_error($ch);
            }

            curl_close($ch);
            return $data;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        return false;
    }

    public function getErrorMessage()
    {
        return $this->errorMessage;
    }
}