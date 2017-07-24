<?php
/**
 * Created by PhpStorm.
 * User: khuen
 * Date: 10-Jul-17
 * Time: 19:39
 */

defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('current_memory_usage')) {
    /**
     * Get the DOM object from HTML string
     * @param bool $real_usage
     * @return string
     */
    function current_memory_usage($real_usage = false)
    {
        $size = memory_get_usage($real_usage);
        $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
    }
}