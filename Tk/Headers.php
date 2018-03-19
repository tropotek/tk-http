<?php
namespace Tk;

/**
 * Class Headers
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2016 Michael Mifsud
 * @see http://git.snooey.net/Mirrors/php-slim/
 */
class Headers extends Collection
{
    /**
     * Special HTTP headers that do not have the "HTTP_" prefix
     * @var array
     */
    protected static $special = [
        'CONTENT_TYPE' => 1,
        'CONTENT_LENGTH' => 1,
        'PHP_AUTH_USER' => 1,
        'PHP_AUTH_PW' => 1,
        'PHP_AUTH_DIGEST' => 1,
        'AUTH_TYPE' => 1,
    ];
    
    
    /**
     * Creates the headers collection from given array or
     * from environment if null.
     * 
     * @param null $headers
     * @return Headers
     */
    public static function create($headers = null)
    {
        if ($headers instanceof Headers) return $headers;
        if (!is_array($headers)) {
            $headers = array();
            if (function_exists('getallheaders')) {
                $headers = getallheaders();
            } else {
                foreach ($_SERVER as $key => $value) {
                    $key = strtoupper($key);
                    if (isset(static::$special[$key]) || strpos($key, 'HTTP_') === 0) {
                        if ($key !== 'HTTP_CONTENT_LENGTH') {
                            $headers[$key] = $value;
                        }
                    }
                }
            }
        }
        return new static($headers);
    }

    /**
     * Return array of HTTP header names and values.
     * This method returns the _original_ header name
     * as specified by the end user.
     *
     * @param null $regex
     * @return array
     */
    public function all($regex = null)
    {
        $all = parent::all($regex);
        $out = array();
        foreach ($all as $key => $props) {
            $out[$props['originalKey']] = $props['value'];
        }
        return $out;
    }

    /**
     * Set HTTP header value
     *
     * This method sets a header value. It replaces
     * any values that may already exist for the header name.
     *
     * @param string $key   The case-insensitive header name
     * @param string $value The header value
     * @return $this
     */
    public function set($key, $value)
    {
        if (!is_array($value)) {
            $value = [$value];
        }
        return parent::set($this->normalizeKey($key), [
            'value' => $value,
            'originalKey' => $key
        ]);
    }

    /**
     * Get HTTP header value
     *
     * @param  string  $key     The case-insensitive header name
     * @param  mixed   $default The default value if key does not exist
     * @return string[]
     */
    public function get($key, $default = null)
    {
        if ($this->has($key)) {
            return parent::get($this->normalizeKey($key))['value'];
        }

        return $default;
    }

    /**
     * Get HTTP header key as originally specified
     *
     * @param  string   $key     The case-insensitive header name
     * @param  mixed    $default The default value if key does not exist
     * @return string
     */
    public function getOriginalKey($key, $default = null)
    {
        if ($this->has($key)) {
            return parent::get($this->normalizeKey($key))['originalKey'];
        }
        return $default;
    }

    /**
     * Add HTTP header value
     *
     * This method appends a header value. Unlike the set() method,
     * this method _appends_ this new value to any values
     * that already exist for this header name.
     *
     * @param string       $key   The case-insensitive header name
     * @param array|string $value The new header value(s)
     */
    public function add($key, $value)
    {
        $oldValues = $this->get($key, []);
        $newValues = is_array($value) ? $value : [$value];
        $this->set($key, array_merge($oldValues, array_values($newValues)));
    }

    /**
     * Does this collection have a given header?
     *
     * @param  string $key The case-insensitive header name
     * @return bool
     */
    public function has($key)
    {
        return parent::has($this->normalizeKey($key));
    }

    /**
     * Remove header from collection
     *
     * @param  string $key The case-insensitive header name
     * @return $this
     */
    public function remove($key)
    {
        return parent::remove($this->normalizeKey($key));
    }

    /**
     * Normalize header name
     *
     * This method transforms header names into a
     * normalized form. This is how we enable case-insensitive
     * header names in the other methods in this class.
     *
     * @param  string $key The case-insensitive header name
     * @return string Normalized header name
     */
    public function normalizeKey($key)
    {
        $key = strtr(strtolower($key), '_', '-');
        if (strpos($key, 'http-') === 0) {
            $key = substr($key, 5);
        }
        return $key;
    }
    
}