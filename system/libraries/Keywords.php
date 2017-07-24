<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Created by PhpStorm.
 * User: khuen
 * Date: 05-Jul-17
 * Time: 22:41
 */
class CI_Keywords
{
    protected $CI;

    public $no_of_keywords = 10;
    public $keywords;
    public $text;

    protected $tokens;
    protected $tf;
    protected $idf;
    protected $tf_idf;
    protected $stop_words;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->database();
        $this->CI->load->helper('url');
        $this->CI->load->helper('file');
    }

    /**
     * Extract keywords
     */
    public function extract()
    {
        $this->clean_text(); // clean the text from noisy elements
        $this->tokenize(); // tokenize the text
        $this->normalize();
        $this->calculate_tf();
        $this->keywords = array_keys(array_slice($this->tf, 0, $this->no_of_keywords, true));
        return $this;
    }

    /**
     * Get keywords as string
     * @return string
     */
    public function get_keywords_as_string()
    {
        return implode(",", $this->keywords);
    }

    /**
     * Tokenize the text
     */
    protected function tokenize()
    {
        $tokens = preg_split('/[\W]+/', $this->text, -1, PREG_SPLIT_NO_EMPTY);
        $tokens = array_map('trim', $tokens);
        $this->tokens = array_filter($tokens);
    }

    /**
     * Clean the text from special symbols and digits
     */
    protected function clean_text()
    {
        if (!$this->stop_words) {
            $data_file_path = base_url('data/stop_words.csv');
            $content = read_file($data_file_path);
            $this->stop_words = str_getcsv($content, "\n");
            foreach ($this->stop_words as &$word) {
                $word = '/\b' . preg_quote($word, '/i') . '\b/';
            }
        }

        // Remove symbols
        $utf8 = array(
            '/\&lt;/' => '',
            '/\&lg;/' => '',
            '/\&nbsp;/' => ' ',
            '/\&quot;/' => '',
            '/\&amp;/' => '',
            '/\&lsquo;/' => '',
            '/\&rsquo;/' => '',
            '/\&ldquo;/' => '',
            '/\&rdquo;/' => '',
            '/–/' => '-', // UTF-8 hyphen to "normal" hyphen
            '/[’‘‹›‚]/u' => '', // Literally a single quote
            '/[“”«»„]/u' => '', // Double quote
            '/ /' => ' ', // nonbreaking space (equiv. to 0x160)
            '/\*/' => ' ',
            '/\:/' => ' ',
            '/\,/' => ' ',
            '/\“‘/' => ' ',
            '/\“/' => ' ',
            '/\”/' => ' ',
            '/\’/' => ' ',
            '/\d/' => ' ',
            '/\\\/' => '',
            '/\+/' => ' ',
            '/\#/' => ' ',
            '/\{/' => ' ',
            '/\}/' => ' ',
            '/&/' => ' ',
            '/\~/' => ' ',
            '/\>/' => ' ',
            '/\</' => ' ',
            '/\=/' => ' ',
            '/\@/' => ' ',
            '/\`/' => ' ',
            '/\$/' => ' ',
            '/\//' => ' ',
            '/\£/' => ' ',
            '/\^/' => ' ',
            '/\%/' => ' ',
            '/\|/' => ' ',
            '/\t/' => ' ',
            '/\n/' => ' ',
            '/\r/' => ' ',
            '/\d+[s]/' => ' ',
            '/ \w{1,3} /' => ' ',
            '/(\b.{1,2}\s)/' => ' ' // Removing short words
        );

        $text = preg_replace($this->stop_words, '', $this->text);

        $this->text = preg_replace(array_keys($utf8), array_values($utf8), $text);
    }

    /**
     * Normalize all words in the text (convert to lower-cased string)
     */
    function normalize()
    {
        foreach ($this->tokens as &$word) {
            $word = strtolower($word);
        }
    }

    /**
     * Calculate term frequency in a bag of tokens
     */
    protected function calculate_tf()
    {
        $freq = array_count_values($this->tokens);
        arsort($freq);
        $this->tf = $freq;
    }

    protected function calculate_idf()
    {

    }
}