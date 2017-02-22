<?php
namespace Tk;

/**
 * Class Response
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2016 Michael Mifsud
 */
class ResponseJson extends Response
{
    public static function createJson($json = null, $status = 200, $headers = array()) {
        
        if (!self::isJson($json)) {
            $json = json_encode($json);
            if ($json === false)
                throw new \Tk\Exception('Cannot conver value to JSON string.');
        }
        
        $obj = new self($json, $status, $headers);
        $obj->addHeader('Cache-Control', 'no-cache, must-revalidate');
        $obj->addHeader('Expires', 'Mon, 26 Jul 1997 05:00:00 GMT');
        $obj->addHeader('Content-type', 'application/json');
        return $obj;
    }


    public static function isJson($string) 
    {
        if (!is_string($string)) return false;
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

}