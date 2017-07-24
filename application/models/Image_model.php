<?php

/**
 * Created by PhpStorm.
 * User: khuen
 * Date: 11-Jul-17
 * Time: 11:38
 */
class Image_model extends Core_model
{
    protected $table_name = 'images';

    const IMG_DIR = "images";

    const GEO_PARSER_URL = 'https://geocode.xyz/';
    const GOOGLE_GEOCODE_URL = 'https://maps.googleapis.com/maps/api/geocode/json';
    const GOOGLE_GEOCODE_KEY = 'AIzaSyD-LeZbM9zpw3fLXsQC7uUcigLcg1cSiYM';
    const SERVICE_VERIFY_SSL = false;


    const MIN_WIDTH = 400;
    const MAX_WIDTH = 400;

    protected $allowed_image_extension = array('jpg', 'jpeg', 'png');
    protected $allowed_aspect_ratios;

    public $source = '';
    public $filename = '';
    public $path = '';
    public $width = 0;
    public $height = 0;
    public $type = '';
    public $related_texts = '';
    public $alt = '';
    public $src = '';
    public $hash = '';
    public $copyright = '';
    public $date_acquired = '';
    public $date_taken = '';
    public $author = '';
    public $latitude = 0.0;
    public $longitude = 0.0;
    public $location_name = '';
    public $is_exif_location = false;

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('string');
        $this->load->database();
        $this->initialize_aspect_ratios();
    }

    public function clear()
    {
        $this->filename = '';
        $this->source = '';
        $this->path = '';
        $this->width = 0;
        $this->height = 0;
        $this->type = '';
        $this->related_texts = '';
        $this->alt = '';
        $this->src = '';
        $this->hash = '';
        $this->copyright = '';
        $this->date_acquired = '';
        $this->date_taken = '';
        $this->author = '';
        $this->latitude = 0.0;
        $this->longitude = 0.0;
        $this->location_name = '';
        $this->is_exif_location = false;
    }

    /**
     * @param $source
     * @return $this
     */
    public function set_source($source)
    {
        $this->source = $source;
        return $this;
    }

    /**
     * @param $alt
     * @return $this
     */
    public function set_image_alt($alt)
    {
        $this->alt = $alt;
        return $this;
    }

    /**
     * @param $src
     * @return $this
     */
    public function set_image_src($src)
    {
        $this->src = $src;
        return $this;
    }

    /**
     * @param $text
     * @return $this
     */
    public function set_related_texts($text)
    {
        $this->related_texts = $text;
        return $this;
    }

    /**
     * Save image to server storage and its
     * metadata to the database
     * @return $this
     */
    public function save()
    {
        $filename = $this->save_image();

        if (!empty($filename)) {

            $this->db->where('hash', $this->hash);
            $image = $this->db->get($this->table_name)->row_array();

            if (!$image) {
                $data = array(
                    'filename' => $this->filename,
                    'source' => $this->source,
                    'hash' => $this->hash,
                    'src' => $this->src,
                    'path' => $this->path,
                    'width' => $this->width,
                    'height' => $this->height,
                    'type' => $this->type,
                    'alt' => $this->alt,
                    'author' => $this->author,
                    'copyright' => $this->copyright,
                    'related_texts' => $this->related_texts,
                    'latitude' => $this->latitude,
                    'longitude' => $this->longitude,
                    'location_name' => $this->location_name,
                    'is_exif_location' => $this->is_exif_location,
                    'date_taken' => $this->date_taken,
                    'date_acquired' => $this->date_acquired
                );

                $this->db->insert($this->table_name, $data);
            }

        }

        // Clear object property values
        $this->clear();

        return $this;

    }

    /**
     * Save physical image file to server storage
     * @return string
     */
    protected function save_image()
    {
        try {

            // Split the image url to get its path information
            $imagePath = pathinfo($this->src);

            if (!isset($imagePath['filename']) || !isset($imagePath['extension']) || !in_array($imagePath['extension'], $this->allowed_image_extension)) {
                return "";
            }

            // Initialize cURL object
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->src);
            curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.81 Safari/537.36");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            // Request image content
            $response = curl_exec($ch);
            curl_close($ch);

            $savePath = $this->prepare_directories();

            if (empty($savePath)) {
                return "";
            }

            // Write image content to server storage
            $filename = $savePath . $imagePath['filename'] . "." . $imagePath['extension'];
            $file = file_put_contents($filename, $response);

            if ($file === false) {
                return "";
            }

            list($width, $height) = @getimagesize($filename);

            // Only proceed further if image size is larger than 400px in each dimension and
            // follow standard photography aspect ratios
            if (($width < self::MIN_WIDTH && $height < self::MAX_WIDTH)
                || !in_array(floatval($width / $height), $this->allowed_aspect_ratios)
            ) {
                unlink($filename);
                return "";
            }

            $this->filename = $imagePath['filename'];
            $this->hash = sha1($this->src);
            $this->path = $filename;
            $this->width = $width;
            $this->height = $height;
            $this->type = $imagePath['extension'];

            $exifData = $this->read_exif_data($filename);
            $rawData = $exifData->getRawData();

            if ($exifData->getGPS() === false) { // When GPS info is not available in image's metadata
                // Try to determine image location using associated texts
                list($latitude, $longitude) = $this->determine_gps_info();
                $this->is_exif_location = false;
            } else {
                // Otherwise, get latitude and longitude from image's metadata
                $latitude = isset($rawData['GPS:GPSLatitude']) ? $rawData['GPS:GPSLatitude'] : null;
                $longitude = isset($rawData['GPS:GPSLongitude']) ? $rawData['GPS:GPSLongitude'] : null;

                // Use Google map API for determining location with latitude and longitude information known
                $this->determine_location($latitude, $longitude);
                $this->is_exif_location = true;
            }

            if (!empty($exifData)) {
                $this->copyright = $exifData->getCopyright();
                $this->date_taken = $exifData->getCreationDate() ? $exifData->getCreationDate()->format('Y-m-d H:i:s') : null;
                $this->date_acquired = date('Y-m-d H:i:s');
                $this->author = $exifData->getAuthor();
                $this->latitude = $latitude;
                $this->longitude = $longitude;
            }

            echo "GET: {$this->src}\n";

            return $filename;
        } catch (Exception $e) {
            return "";
        }
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
            $this->location_name = $resultObj->results[0]->formatted_address;
        }
    }

    /**
     * Try to determine GPS information from
     * image associated texts and descriptions
     * using external image GIR API
     * @return array
     */
    protected function determine_gps_info()
    {
        // Gather a short text from image's alt and filename
        $filename = tokenize($this->filename);
        $image_description = implode(' ', $filename);

        if (!empty($this->alt)) {
            $image_description .= ' ' . $this->alt;
        }

        if (!empty($this->related_texts)) {
            $image_description .= ' ' . $this->related_texts;
        }

        $locationInfo = $this->geo_parse($image_description);
        if (is_array($locationInfo)) {
            return $locationInfo;
        }

        if (is_array($locationInfo)) {
            return $locationInfo;
        }

        return array(null, null);
    }

    /**
     * Call to a 3rd party API
     * to get location information by
     * parsing image's text description text
     * @param $image_description
     * @return array|bool
     */
    protected function geo_parse($image_description)
    {
        $client = new \GuzzleHttp\Client([
            'base_uri' => self::GEO_PARSER_URL,
            'verify' => self::SERVICE_VERIFY_SSL
        ]);

        $response = $client->request('GET', '', [
            'query' => [
                'scantext' => $image_description,
                'json' => 1
            ]
        ]);
        $results = $response->getBody()->getContents();
        $resultObj = \GuzzleHttp\json_decode($results);

        if ($resultObj->matches !== null) {
            if (is_array($resultObj->match)) {
                $this->location_name = $resultObj->match[0]->location;
            } else {
                $this->location_name = $resultObj->match->location;
            }

            return array(
                $resultObj->latt,
                $resultObj->longt
            );
        }

        return false;
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

    /**
     * Prepare to create necessary directories to store images
     * @return string
     */
    protected function prepare_directories()
    {
        $save_path = FCPATH . "/crawled/" . self::IMG_DIR . "/" . $this->source . "/";

        if (!file_exists($save_path)) {
            $can_mkdir = mkdir($save_path);
            if (!$can_mkdir) {
                return "";
            }
        }

        return $save_path;
    }

    /**
     * Initialize standard aspect ratios list
     */
    protected function initialize_aspect_ratios()
    {
        $this->allowed_aspect_ratios = array(
            floatval(1 / 1),
            floatval(5 / 4),
            floatval(4 / 5),
            floatval(4 / 3),
            floatval(3 / 4),
            floatval(3 / 2),
            floatval(2 / 3),
            floatval(5 / 3),
            floatval(3 / 5),
            floatval(16 / 9),
            floatval(9 / 16),
            floatval(3 / 1),
            floatval(1 / 3),
        );
    }
}