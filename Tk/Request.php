<?php
namespace Tk;

/**
 * This is a rewritten Tk\Request object with the PSR7 interfaces taken into consideration,
 * However you will need to extend these objects to make them completely PSR7 compatible.
 * 
 * The object uses the \ArrayAccess interface so that the request object can be used like the $_REQUEST array
 * in situations that do not have the Tk\Request object.
 * 
 * 
 * @thought: I am not 100% sure that our libs currently needs to support PSR7 is its entirety.
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Request extends ClientRequest implements \ArrayAccess, \IteratorAggregate, \Countable
//class Request extends ClientRequest implements \ArrayAccess, \Countable
{
    
    /**
     * Set this if you want to extend the Request's sanitizer method
     * This will be called after the existing sanitizer method is run.
     * The callback will supply the request as a single argument passed to the callback.
     * 
     * @var callable
     */
    public static $sanitizerCallback = null;

    /**
     * @var array
     */
    protected $cookies = array();

    /**
     * @var array
     */
    protected $params = array();
    
    /**
     * @var array
     */
    protected $serverParams = array();

    /**
     * @var array
     */
    protected $uploadedFiles = array();

    /**
     * @var array
     */
    protected $attributes = array();

    /**
     * @var null|array|object
     */
    protected $bodyParsed;

    /**
     * @var callable[]
     */
    protected $bodyParsers = array();


    /**
     * Request constructor.
     *
     * @param Uri $uri The Request Uri
     * @param string $method The request method ('GET', 'POST', etc)
     * @param array $headers An array of the request headers
     * @param array $params The request params ($_REQUEST) This contains all $_GET, $_POST, $_COOKIES.
     * @param array $serverParams The server/Environment array variables ($_SERVER, $_ENV)
     * @param array $cookies Generally this is the $_COOKIES global
     * @param array $uploadedFiles Generally this is the $_FILES global
     * @param mixed|string $body The body of the request if available
     */
    public function __construct($uri, $method = 'GET', $headers = array(), $params = array(), $serverParams = array(), $cookies = array(), $uploadedFiles = array(), $body = '')
    {
        parent::__construct($uri, $method, $body);
        $this->headers = Headers::create($headers);
        $this->params = $params;
        $this->serverParams = $serverParams;
        $this->cookies = $cookies;
        $this->uploadedFiles = $uploadedFiles;
        
        $this->sanitize($this->params);
        $this->sanitize($this->serverParams);
        
        

        $this->registerMediaTypeParser('application/json', function ($input) {
            return json_decode($input, true);
        });

        $this->registerMediaTypeParser('application/xml', function ($input) {
            return simplexml_load_string($input);
        });

        $this->registerMediaTypeParser('application/x-www-form-urlencoded', function ($input) {
            parse_str($input, $data);
            return $data;
        });
        
    }

    /**
     * Create a default instance from the server environment variables.
     * 
     * @param null|array $params
     * @param null|array $serverParams
     * @param null|array $uploadedFiles
     * @return static
     */
    public static function create($params = null, $serverParams = null, $uploadedFiles = null)
    {
        $uri = Uri::create();
        $method = 'GET';
        if (isset($_SERVER['REQUEST_METHOD'])) {
            $method = $_SERVER['REQUEST_METHOD'];
        }
        
        $headers = Headers::create();
        
        if ($params === null) {
            $params = $_REQUEST;
        }

        if ($serverParams === null) {
            $serverParams = $_SERVER;
        }
        
        $cookies = $_COOKIE;
        
        if ($uploadedFiles === null) {
            $uploadedFiles = array();
            if (!empty($_FILES)) {
                $uploadedFiles = UploadedFile::parseUploadedFiles($_FILES);
            }
        }
        
        $request = new static($uri, $method, $headers, $params, $serverParams, $cookies, $uploadedFiles);        
        return $request;
    }

    /**
     * Sanitize Globals
     *
     * This function does the following:
     *
     * Unsets $_GET data (if query strings are not enabled)
     *
     * Unsets all globals if register_globals is enabled
     *
     * Standardizes newline characters to \n
     *
     * @access    private
     * @param $array
     */
    private function sanitize($array)
    {
        try {   // Need a catch statement here as it could be run outside a try catch.
            // Clean $_REQUEST data
            if (is_array($array) && count($array) > 0) {
                foreach ($array as $key => $val) {
                    $array[$this->cleanKey($key)] = $this->cleanData($val);
                }
            }
            if (is_callable(static::$sanitizerCallback)) {
                call_user_func_array(static::$sanitizerCallback, array($this));
            } 
        } catch (\Exception $e) {
            error_log(print_r($e->__toString(), true));
        }
    }

    /**
     * Clean Input Data
     *
     * This is a helper function. It escapes data and
     * standardizes newline characters to \n
     *
     * @param	string|array $str
     * @return	string
     */
    private function cleanData($str)
    {
        if (is_array($str)) {
            $new_array = array();
            foreach ($str as $key => $val) {
                $new_array[$this->cleanKey($key)] = $this->cleanData($val);
            }
            return $new_array;
        }
        // Standardize newlines
        return preg_replace("/\015\012|\015|\012/", "\n", $str);
    }

    /**
     * Clean Keys
     *
     * This is a helper function. To prevent malicious users
     * from trying to exploit keys we make sure that keys are
     * only named with alpha-numeric text and a few other items.
     *
     * @param	string $str
     * @return	string
     * @throws \Tk\Exception
     */
    private function cleanKey($str)
    {
        if (!preg_match("/^[a-z0-9:_\[\]\/-]+$/i", $str)) {
            throw new Exception('Disallowed Key Characters.');
        }
        return $str;
    }

    // --------------------------------------------------------------------
    
    /**
     * Get a request param
     * 
     * @param $key
     * @param null $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if ($this->has($key))
            return $this->params[$key];
        return $default;
    }

    /**
     * Return all the request params 
     * 
     * @return array
     */
    public function all()
    {
        return $this->params;
    }

    /**
     * Check if a request param exists
     * 
     * @param $key
     * @return bool
     */
    public function has($key)
    {
        return isset($this->params[$key]);
    }
    

    /**
     * Retrieve cookies.
     *
     * Retrieves cookies sent by the client to the server.
     *
     * The data MUST be compatible with the structure of the $_COOKIE
     * superglobal.
     *
     * @return array
     */
    public function getCookieParams()
    {
        return $this->cookies;
    }
    
    
    /**
     * Retrieve query string arguments.
     *
     * Retrieves the deserialized query string arguments, if any.
     *
     * Note: the query params might not be in sync with the URI or server
     * params. If you need to ensure you are only getting the original
     * values, you may need to parse the query string from `getUri()->getQuery()`
     * or from the `QUERY_STRING` server param.
     *
     * @return array
     */
    public function getQueryParams()
    {
        if ($this->getUri() === null) {
            return [];
        }
        return $this->getUri()->getQueryArray();
    }
    
    /**
     * Retrieve normalized file upload data.
     *
     * This method returns upload metadata in a normalized tree, with each leaf
     * an instance of Psr\Http\Message\UploadedFileInterface.
     *
     * These values MAY be prepared from $_FILES or the message body during
     * instantiation, or MAY be injected via withUploadedFiles().
     *
     * @return array An array tree of UploadedFileInterface instances; an empty
     *     array MUST be returned if no data is present.
     */
    public function getUploadedFiles()
    {
        return $this->uploadedFiles;
    }

    /**
     *
     * @param $name
     * @return UploadedFile|array|null
     */
    public function getUploadedFile($name)
    {
        if (isset($this->uploadedFiles[$name]))
            return $this->uploadedFiles[$name];
    }
    
    
    /**
     * Retrieve server parameters.
     *
     * Retrieves data related to the incoming request environment,
     * typically derived from PHP's $_SERVER superglobal. The data IS NOT
     * REQUIRED to originate from $_SERVER.
     *
     * @return array
     */
    public function getServerParams()
    {
        return $this->serverParams;
    }

    /**
     * 
     * 
     * @param $name
     * @param null $default
     * @return null
     */
    public function getServerParam($name, $default = null)
    {
        if (isset($this->serverParams[$name]))
            return $this->serverParams[$name];
        return $default;
    }
    
    /**
     * Retrieve attributes derived from the request.
     *
     * The request "attributes" may be used to allow injection of any
     * parameters derived from the request: e.g., the results of path
     * match operations; the results of decrypting cookies; the results of
     * deserializing non-form-encoded message bodies; etc. Attributes
     * will be application and request specific, and CAN be mutable.
     *
     * @return array Attributes derived from the request.
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Retrieve a single derived request attribute.
     *
     * Retrieves a single derived request attribute as described in
     * getAttributes(). If the attribute has not been previously set, returns
     * the default value as provided.
     *
     * This method obviates the need for a hasAttribute() method, as it allows
     * specifying a default value to return if the attribute is not found.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @param mixed $default Default value to return if the attribute does not exist.
     * @return mixed
     */
    public function getAttribute($name, $default = null)
    {
        if ($this->hasAttribute($name)) {
            return $this->attributes[$name];
        }
        return $default;
    }

    /**
     * Set an attribute
     * 
     * @param $name
     * @param $value
     * @return $this
     */
    public function setAttribute($name, $value) 
    {
        $this->attributes[$name] = $value;
        return $this;
    }

    /**
     * Remove an attribute key from the list
     * 
     * @param $name
     * @return $this
     */
    public function removeAttribute($name)
    {
        if ($this->hasAttribute($name))
            unset($this->attributes[$name]);
        return $this;
    }

    /**
     * Check if an attribute key exists
     * 
     * @param $name
     * @return bool
     */
    public function hasAttribute($name)
    {
        return isset($this->attributes[$name]);
    }

    /**
     * Add a list of items to the attribute array
     *
     * @param array $items Key-value array of data to append to this collection
     */
    public function replaceAttribute(array $items)
    {
        foreach ($items as $key => $value) {
            $this->setAttribute($key, $value);
        }
    }
    
    
    /**
     * Retrieve any parameters provided in the request body.
     *
     * If the request Content-Type is either application/x-www-form-urlencoded
     * or multipart/form-data, and the request method is POST, this method MUST
     * return the contents of $_POST.
     *
     * Otherwise, this method may return any results of deserializing
     * the request body content; as parsing returns structured content, the
     * potential types MUST be arrays or objects only. A null value indicates
     * the absence of body content.
     *
     * @return null|array|object The deserialized body parameters, if any.
     *     These will typically be an array or object.
     * @throws \RuntimeException if the request body media type parser returns an invalid value
     */
    public function getParsedBody()
    {
        if ($this->bodyParsed) {
            return $this->bodyParsed;
        }

        if (!$this->body) {
            return null;
        }

        $mediaType = $this->getMediaType();
        $body = (string)$this->getBody();

        if (isset($this->bodyParsers[$mediaType]) === true) {
            $parsed = $this->bodyParsers[$mediaType]($body);
            if (!is_null($parsed) && !is_object($parsed) && !is_array($parsed)) {
                throw new \RuntimeException('Request body media type parser return value must be an array, an object, or null');
            }
            $this->bodyParsed = $parsed;
        }

        return $this->bodyParsed;
    }

    /**
     * Register media type parser.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @param string   $mediaType A HTTP media type (excluding content-type
     *     params).
     * @param callable $callable  A callable that returns parsed contents for
     *     media type.
     */
    public function registerMediaTypeParser($mediaType, callable $callable)
    {
        if ($callable instanceof \Closure) {
            $callable = $callable->bindTo($this);
        }
        $this->bodyParsers[(string)$mediaType] = $callable;
    }
    
    
    /**
     * Get the client IP address.
     *
     * @return string|null IP address or null if none found.
     */
    public function getIp()
    {
        if ($this->hasHeader('X-Forwarded-For')) {
            return trim(current(explode(',', $this->getHeaderLine('X-Forwarded-For'))));
        }
        return $this->getServerParam('REMOTE_ADDR', null);
    }


    /**
     * Returns the referring \Tk\Uri if available.
     *
     * @return null|Uri Returns null if there was no referer.
     */
    public function getReferer()
    {
        $referer = $this->getServerParam('HTTP_REFERER');
        if ($referer) {
            $referer = Uri::create($referer);
        }
        return $referer;
    }

    /**
     * Check that this request came from our hosting server
     * as opposed to a remote request.
     *
     * @return bool
     */
    public function checkReferer()
    {
        $referer = $this->getReferer();
        $request = $this->getUri();
        if ($referer && $referer->getHost() == $request->getHost()) {
            return true;
        }
        return false;
    }


    /**
     * Get the browser userAgent string
     *
     * @return string
     */
    public function getUserAgent()
    {
        return $this->getServerParam('HTTP_USER_AGENT', '');
    }
    
    /**
     * Returns the raw post data.
     *
     * 
     * note: In general, php://input should be used instead of $HTTP_RAW_POST_DATA.
     * @return string
     * @link http://php.net/manual/en/reserved.variables.httprawpostdata.php
     * @deprecated This could be removed in future versions in favor of the PSR7 stream system.
     */
    public function getRawPostData()
    {
        return file_get_contents("php://input");
    }










    /**
     * Does this collection have a given key?
     *
     * @param  string $key The data key
     *
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->has($key);
    }

    /**
     * Get collection item for key
     *
     * @param string $key The data key
     *
     * @return mixed The key's value, or the default value
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * Set collection item
     *
     * @param string $key The data key
     * @param mixed $value The data value
     * @throws Exception
     * @todo This should not be allowed technically
     */
    public function offsetSet($key, $value)
    {
        throw new Exception('Data is read only, use attributes array.');
        //$this->params[$key] = $value;
    }

    /**
     * Remove item from collection
     *
     * @param string $key The data key
     * @throws Exception
     * @todo This should not be allowed technically
     */
    public function offsetUnset($key)
    {
        throw new Exception('Data is read only, use attributes array.');
        //unset($this->params[$key]);
    }

    /**
     * Get number of items in collection
     *
     * @return int
     */
    public function count()
    {
        return count($this->params);
    }

    /**
     * Get collection iterator
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->params);
    }
    
}
