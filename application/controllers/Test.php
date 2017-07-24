<?php

/**
 * Created by PhpStorm.
 * User: khuen
 * Date: 06-Jul-17
 * Time: 15:21
 */
class Test extends CI_Controller
{
    const GOOGLE_GEOCODE_URL = 'https://maps.googleapis.com/maps/api/geocode/json';
    const GOOGLE_GEOCODE_KEY = 'AIzaSyD-LeZbM9zpw3fLXsQC7uUcigLcg1cSiYM';
    const SERVICE_VERIFY_SSL = false;

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('file');
        $this->load->helper('url');
        $this->load->helper('string');
        $this->load->library('curl');
        $this->load->model('page_model');
        $this->load->model('seed_model');
        $this->load->model('queue_model');
        $this->load->model('image_model');
        $this->load->database();
    }

    public function main()
    {
        $exif = $this->read_exif_data('E:\\Projects\\mopsicrawler\\testexif.jpg');
        $rawData = $exif->getRawData();
        $latitude = isset($rawData['GPS:GPSLatitude']) ? $rawData['GPS:GPSLatitude'] : null;
        $longitude = isset($rawData['GPS:GPSLongitude']) ? $rawData['GPS:GPSLongitude'] : null;
        $this->determine_location($latitude, $longitude);
    }

    /**
     * Determine location from GPS information
     * obtained by extracting image's EXIF
     * metadata
     * @param float $lat
     * @param float $lng
     */
    protected function determine_location($lat, $lng)
    {
        $client = new \GuzzleHttp\Client([
            'base_uri' => self::GOOGLE_GEOCODE_URL,
            'verify' => self::SERVICE_VERIFY_SSL
        ]);

        $response = $client->request('GET', '', [
            'query' => [
                'latlng' => "{$lat},{$lng}",
                'key' => self::GOOGLE_GEOCODE_KEY,
                'result_type' => "street_address|country"
            ]
        ]);
        $results = $response->getBody()->getContents();
        $resultObj = \GuzzleHttp\json_decode($results);

        if ($resultObj->status === "OK" && is_array($resultObj->results)) {
            echo $resultObj->results[0]->formatted_address;
        }
    }

    /**
     * Get exif data
     * @param $filename
     * @return \PHPExif\Exif
     */
    protected function read_exif_data($filename)
    {
        $tool_path = FCPATH . "/exiftool/";
        switch (strtoupper(substr(PHP_OS, 0, 3))) {
            case "WIN": // Windows
                $tool_path .= "exiftool.exe";
                break;
            case "LIN": // Linux
                $tool_path .= "exiftool";
                break;
            case "UNI": // Unix
                $tool_path .= "exiftool";
                break;
            case "DAR": // MacOS
                $tool_path .= "exiftool.dmg";
                break;
        }
        $adapter = new PHPExif\Adapter\Exiftool(
            array(
                'toolPath' => $tool_path,
            )
        );
        $reader = new PHPExif\Reader\Reader($adapter);
        $exif = $reader->read($filename);
        return $exif;
    }
}