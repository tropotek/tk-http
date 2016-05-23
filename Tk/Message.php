<?php
namespace Tk;

/**
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Message
{
    
    /**
     * @var string
     */
    protected $protocolVersion = '1.1';

    /**
     * @var Headers
     */
    protected $headers = array();

    /**
     * @var string
     */
    protected $body = '';
    
    

    /**
     * Get request content length, if known.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return int|null
     */
    public function getContentLength()
    {
        $result = $this->headers->get('Content-Length');

        return $result ? (int)$result[0] : null;
    }

    /**
     * Get request content type.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return string|null The request content type, if known
     */
    public function getContentType()
    {
        $result = $this->getHeader('Content-Type');

        return $result ? $result[0] : null;
    }

    /**
     * Get request media type, if known.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return string|null The request media type, minus content-type params
     */
    public function getMediaType()
    {
        $contentType = $this->getContentType();
        if ($contentType) {
            $contentTypeParts = preg_split('/\s*[;,]\s*/', $contentType);

            return strtolower($contentTypeParts[0]);
        }

        return null;
    }
    
    /**
     * Is this an XHR request?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isXhr()
    {
        return $this->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';
    }



    /**
     * Set a header in the message
     *
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function setHeader($name, $value)
    {
        $this->headers->set($name, $value);
        return $this;
    }

    /**
     * This method appends a header value. Unlike the set() method,
     * this method _appends_ this new value to any values
     * that already existed for this header name.
     *
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function addHeader($name, $value)
    {
        $this->headers->add($name, $value);
        return $this;
    }

    /**
     * Checks if a header exists by the given case-insensitive name.
     *
     * @param string $name Case-insensitive header field name.
     * @return bool Returns true if any header names match the given header
     *     name using a case-insensitive string comparison. Returns false if
     *     no matching header name is found in the message.
     */
    public function hasHeader($name) 
    {
        return $this->headers->has($name);    }

    /**
     * Retrieves a message header value by the given case-insensitive name.
     *
     * This method returns an array of all the header values of the given
     * case-insensitive header name.
     *
     * If the header does not appear in the message, this method MUST return an
     * empty array.
     *
     * @param string $name Case-insensitive header field name.
     * @return string[] An array of string values as provided for the given
     *    header. If the header does not appear in the message, this method MUST
     *    return an empty array.
     */
    public function getHeader($name) 
    {
        if ($this->hasHeader($name)) {
            return $this->getHeader($name);
        }
        return [];
    }

    /**
     * Retrieves a comma-separated string of the values for a single header.
     *
     * This method returns all of the header values of the given
     * case-insensitive header name as a string concatenated together using
     * a comma.
     *
     * NOTE: Not all header values may be appropriately represented using
     * comma concatenation. For such headers, use getHeader() instead
     * and supply your own delimiter when concatenating.
     *
     * If the header does not appear in the message, this method MUST return
     * an empty string.
     *
     * @param string $name Case-insensitive header field name.
     * @return string A string of values as provided for the given header
     *    concatenated together using a comma. If the header does not appear in
     *    the message, this method MUST return an empty string.
     */
    public function getHeaderLine($name) 
    {
        return implode(',', $this->getHeader($name));
    }
    
    /**
     * Retrieves all message header values.
     *
     * The keys represent the header name as it will be sent over the wire, and
     * each value is an array of strings associated with the header.
     *
     *     // Represent the headers as a string
     *     foreach ($message->getHeaders() as $name => $values) {
     *         echo $name . ": " . implode(", ", $values);
     *     }
     *
     *     // Emit headers iteratively:
     *     foreach ($message->getHeaders() as $name => $values) {
     *         foreach ($values as $value) {
     *             header(sprintf('%s: %s', $name, $value), false);
     *         }
     *     }
     *
     * While header names are not case-sensitive, getHeaders() will preserve the
     * exact case in which headers were originally specified.
     *
     * @return array Returns an associative array of the message's headers. Each
     *     key MUST be a header name, and each value MUST be an array of strings
     *     for that header.
     */
    public function getHeaders() 
    {
        return $this->headers->all();
    }
    
    /**
     * Normalize header name
     *
     * This method transforms header names into a
     * normalized form. This is how we enable case-insensitive
     * header names in the other methods in this class.
     *
     * @param  string $key The case-insensitive header name
     *
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

    /**
     * Retrieves the HTTP protocol version as a string.
     *
     * The string MUST contain only the HTTP version number (e.g., "1.1", "1.0").
     *
     * @return string HTTP protocol version.
     */
    public function getProtocolVersion() 
    {
        return $this->protocolVersion;
    }

    /**
     * @param string $version
     * @return $this
     */
    public function setProtocolVersion($version)
    {
        static $valid = [
            '1.0' => true,
            '1.1' => true,
            '2.0' => true,
        ];
        if (!isset($valid[$version])) {
            throw new \InvalidArgumentException('Invalid HTTP Protocol version. Must be one of: 1.0, 1.1, 2.0');
        }
        $this->protocolVersion = $version;
        return $this;
    }

    /**
     * Gets the body of the message.
     *
     * @return mixed|string
     */
    public function getBody() 
    {
        return $this->body;
    }

    /**
     * setBody
     * 
     * @param mixed|null $body
     */
    public function setBody($body)
    {
        $this->body = $body;
    }
    
}
