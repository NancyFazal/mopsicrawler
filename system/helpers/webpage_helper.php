<?php
/**
 * Created by PhpStorm.
 * User: khuen
 * Date: 08-Jul-17
 * Time: 09:48
 */

defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('get_dom_object')) {
    /**
     * Get the DOM object from HTML string
     * @param $html
     * @return DOMDocument
     */
    function get_dom_object($html)
    {
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        return $dom;
    }
}

if (!function_exists('extract_texts')) {
    /**
     * Extract text from a DOM document
     * @param DOMDocument $dom
     * @param $allowed_text_elements
     * @return string
     */
    function extract_texts($dom, $allowed_text_elements)
    {
        // Initialize the text
        $text = "";

        // Meta description
        $metas = $dom->getElementsByTagName('meta');
        /** @var DOMElement $meta */
        foreach ($metas as $meta) {
            if ($meta->getAttribute('name') === 'description') {
                $text .= str_replace("\n", " ", strip_tags($meta->getAttribute('content'))) . " ";
                break;
            }
        }

        foreach ($allowed_text_elements as $element_tag_name) {
            $elements = $dom->getElementsByTagName($element_tag_name);
            if ($elements->length > 0) {
                /** @var DOMElement $element */
                foreach ($elements as $element) {
                    $text .= str_replace("\n", " ", strip_tags($element->textContent));
                }
            }
        }

        return $text;
    }
}

if (!function_exists('extract_keywords')) {
    /**
     * Extract keywords from a web page using its DOM and extracted text
     * @param DOMDocument $dom
     * @param string $text
     * @return string
     */
    function extract_keywords($dom, $text)
    {
        // Get keywords from metadata
        $metas = $dom->getElementsByTagName('meta');
        $meta_keywords = "";
        /** @var DOMElement $meta */
        foreach ($metas as $meta) {
            if ($meta->getAttribute('name') === 'keywords') {
                $meta_keywords = $meta->getAttribute('content');
                break;
            }
        }

        // Get CI controller instance
        $CI =& get_instance();
        $CI->load->library('keywords');
        /** @var CI_Keywords $keyword_extractor */
        $keyword_extractor = $CI->keywords;

        // Assign current page's texts to keywords model
        $keyword_extractor->text = $text;
        $keywords = $keyword_extractor->extract()->get_keywords_as_string();

        if (!empty($meta_keywords)) {
            $keywords .= "," . $meta_keywords;
        }

        return $keywords;
    }
}

if (!function_exists('parse_link')) {
    function parse_link($page_url, $link)
    {
        // Remove query strings
        $link = preg_replace('/\?.*/', '', $link);

        // Get URL's components
        $parse_page_url = parse_url($page_url);
        $base_url = $parse_page_url['scheme'] . '://' . $parse_page_url['host'];

        if (strpos($link, '#') !== FALSE) { // Handle anchor link
            return ''; // No crawling at all!
        } elseif (substr($link, 0, 11) == 'javascript:') { // Handle link contains javascript code
            return ''; // No crawling at all!
        } elseif (substr($link, 0, 7) == 'mailto:') { // Handle link contains javascript code
            return ''; // No crawling at all!
        } elseif (substr($link, 0, 1) == '/' && strlen($link) == 1) {
            return ''; // No crawling at all!
        } else if (substr($link, 0, 3) == 'data') {
            return ''; // No crawling at all!
        } else if ($link === 'http://' || $link === 'https://') {
            return ''; // No crawling at all!
        } elseif (substr($link, 0, 2) == '//') { // Handle link with double slashes, e.g. //test.html
            return $parse_page_url['scheme'] . ':' . $link;
        } elseif (substr($link, 0, 1) == '/' && substr($link, 0, 2) != '/') {
            return $base_url . $link;
        } elseif (substr($link, 0, 1) == './') { // Handle link with a point and a slash, e.g. ./test.html
            return $base_url . dirname($parse_page_url['path']) . substr($link, 1);
        } elseif (substr($link, 0, 3) == '../') { // Handle link with double dots and a slash, e.g. ../test.html
            return $base_url . '/' . $link;
        } elseif (substr($link, 0, 5) != 'https' && substr($link, 0, 4) != 'http') { // Handle link without URL scheme
            return $base_url . '/' . $link;
        }

        // Return original link if it's a standard url
        return $link;
    }
}