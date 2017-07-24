<?php

/**
 * Created by PhpStorm.
 * User: khuen
 * Date: 05-Jul-17
 * Time: 13:14
 */
class Page_model extends Core_model
{
    protected $table_name = 'pages';

    const REL_MODE_PARENT = 1;
    const REL_MODE_SEED = 2;
    const REL_MODE_BOTH = 3;

    /**
     * @var array
     */
    public $page_data;

    /**
     * @var array
     */
    public $seed_data;

    /**
     * Raw HTML content of a page
     * @var
     */
    public $html;

    /**
     * Document Object Model of the page
     * @var DOMDocument
     */
    public $dom;

    /**
     * Children of a page node
     * @var array
     */
    public $links;

    /**
     * Relevance mode
     * Default to 0 to crawl all links regardless
     * of their relevance to the seed or its
     * direct parent
     * @var int
     */
    public $relevance_mode = 0;

    /**
     * HTML elements that contains rich texts
     * that describe page's content
     * @var array
     */
    protected $text_elements = array('title', 'h1', 'h2', 'h3', 'h4', 'h5', 'p');

    protected $property_list = array('id', 'parent_id', 'seed_id', 'url', 'url_desc', 'host', 'title', 'keywords', 'text', 'relevance');

    /**
     * Page_model constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->library('curl');
        $this->load->helper('webpage');
        $this->load->model('image_model');
    }

    /**
     * Get a page data by its url
     * @param $url
     * @return null|array
     */
    public function get_page_data_by_url($url)
    {
        $this->db->where('url', $url);
        $page = $this->db->get($this->table_name)->row_array();

        if ($page) {
            return $page;
        }

        return null;
    }

    /**
     * @param $seed_data
     * @return $this
     */
    public function set_seed_data($seed_data)
    {
        if (!$this->seed_data) {
            $this->seed_data = $seed_data;
            $this->page_data['seed_id'] = $seed_data['id'];
        }
        return $this;
    }

    /**
     * @param $page_data
     * @return $this
     */
    public function set_page_data($page_data)
    {
        $this->page_data = $page_data;
        return $this;
    }

    /**
     * Fetch the page
     * @throws Exception
     */
    public function fetch()
    {
        $this->html = $this->curl->get($this->page_data['url']);

        if (!$this->html) {
            throw new Exception($this->curl->getErrorMessage());
        }

        // Parse the html as DOM for processing
        $this->dom = get_dom_object($this->html);

        // Get page's title
        $this->page_data['title'] = $this->dom->getElementsByTagName('title')->item(0)->textContent;

        // Extract page's texts
        $this->page_data['text'] = extract_texts($this->dom, $this->text_elements);

        // Extract page's keywords
        $this->page_data['keywords'] = extract_keywords($this->dom, $this->page_data['text']);

        // Download page's images
        $this->download_images();

        return $this;
    }

    /**
     * Save the page to the database
     */
    public function save()
    {
        // Get page info from the database if it exists
        $page = $this->get_page_data_by_url($this->page_data['url']);

        $data = array_filter($this->page_data, function($k) {
            return in_array($k, $this->property_list);
        }, ARRAY_FILTER_USE_KEY);

        if (is_null($page)) {
            // Insert to database
            $this->db->insert($this->table_name, $data);
            $this->page_data['id'] = $this->db->insert_id();
        } else {
            // Update database
            $this->db->where('id', $page['id']);
            $this->db->update($this->table_name, $data);
            $this->page_data['id'] = $page['id'];
        }

        // If the current page is actually the seed then we can assign seed's data to saved page's data
        if ($this->page_data['url'] === $this->seed_data['url']) {
            $this->seed_data = $this->page_data;
        }

        return $this;
    }

    public function clear()
    {
        $this->page_data = null;
        $this->html = null;
        $this->dom = null;
        $this->links = null;
        $this->relevance_mode = 0;
    }

    /**
     * Collect all links on the page
     */
    public function get_links()
    {
        // Get all link elements from the DOM object
        $link_elements = $this->dom->getElementsByTagName('a');

        // Initialize a list of urls collected
        $links = array();

        /** @var DOMElement $link_element */
        foreach ($link_elements as $link_element) {
            $href = $link_element->getAttribute('href');
            $url = parse_link($this->page_data['url'], $href);
            echo "SAVE: " . $url . "\n";
            if (!empty($url)) {
                // Initialize link data
                $link_data = array(
                    'url' => $url,
                    'url_desc' => trim(strip_tags($link_element->textContent)),
                    'host' => parse_url($url)['host'] ? parse_url($url)['host'] : ""
                );
                $link_data['relevance'] = $this->compute_relevance($link_data);

                // Add link to the list
                if (!in_array($link_data, $links)) {
                    $links[] = $link_data;
                }
            }
        }

        $this->links = $links;

        return $this->links;
    }

    /**
     * Download images
     */
    protected function download_images()
    {
        /** @var Image_model $img */
        $img =& $this->image_model;

        // Get all image links
        $imageElements = $this->dom->getElementsByTagName('img');
        if ($imageElements->length > 0) {
            /** @var DOMElement $imageElement */
            foreach($imageElements as $imageElement) {

                // Text content associated with the image
                $related_texts = "";

                /*Get the current image element's nearest HTML tag that is before and after it
                then collect the text content of the tags to have image's related texts
                that can later be used for determining location*/
                $prev = $imageElement->previousSibling;
                if (get_class($prev) === 'DOMElement' && $prev->tagName !== 'script') {
                    $related_texts .= trim(strip_tags($prev->textContent));
                }
                $next = $imageElement->nextSibling;
                if (get_class($next) === 'DOMElement' && $next->tagName !== 'script') {
                    $related_texts .= trim(strip_tags($next->textContent));
                }

                $src = parse_link($this->page_data['url'], $imageElement->getAttribute('src'));

                if (!empty($src)) {
                    // Set image's essential information
                    $img->set_source($this->page_data['host'])
                        ->set_image_src($src)
                        ->set_image_alt($imageElement->getAttribute('alt'))
                        ->set_related_texts($related_texts)
                        ->save();
                }
            }
        }

    }

    /**
     * Calculate relevance score of current page to another page
     * @param array $link_data
     * @return float
     */
    protected function compute_relevance($link_data)
    {
        switch($this->relevance_mode) {
            case self::REL_MODE_PARENT:
                return $this->get_relevance_between($this->page_data, $link_data);
            case self::REL_MODE_SEED:
                return $this->get_relevance_between($this->seed_data, $link_data);
            case self::REL_MODE_BOTH:
                $parent_rel = $this->get_relevance_between($this->page_data, $link_data);
                $seed_rel = $this->get_relevance_between($this->seed_data, $link_data);
                return ($parent_rel + $seed_rel) / 2;
            default:
                return 1;
        }
    }

    /**
     * Get relevance score between two pages
     * @param $source_page_data
     * @param $target_page_data
     * @return float
     */
    protected function get_relevance_between($source_page_data, $target_page_data)
    {
        $total_score = 0;
        $total_criteria = 0;

        // 1. Host domain relevance
        $total_criteria++;
        if ($source_page_data['host'] === $target_page_data['host']) {
            $total_score++;
        }

        // 2. Link topic relevance
        // Split the url description into word tokens
        $url_desc_tokens = preg_split('/[\W]+/', $target_page_data['url_desc'], -1, PREG_SPLIT_NO_EMPTY);
        $url_desc_tokens = array_map('trim', $url_desc_tokens);
        $total_criteria += count($url_desc_tokens);
        foreach ($url_desc_tokens as $word) {
            $word_count = substr_count($source_page_data['text'], $word);
            if ($word_count > 1) {
                $total_score++;
            }
        }

        return (float)($total_score / $total_criteria);
    }
}