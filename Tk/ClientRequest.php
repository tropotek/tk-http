<?php
namespace Tk;

/**
 *
 * Representation of an outgoing, client-side request.
 *
 * Per the HTTP specification, this interface includes properties for
 * each of the following:
 *
 * - Protocol version
 * - HTTP method
 * - URI
 * - Headers
 * - Message body
 *
 * During construction, implementations MUST attempt to set the Host header from
 * a provided URI if no Host header is provided.
 *
 * 
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class ClientRequest extends Message
{

    /**
     * Valid request methods
     *
     * @var string[]
     */
    protected $validMethods = [
        'CONNECT' => 1,
        'DELETE' => 1,
        'GET' => 1,
        'HEAD' => 1,
        'OPTIONS' => 1,
        'PATCH' => 1,
        'POST' => 1,
        'PUT' => 1,
        'TRACE' => 1,
    ];
    
    /**
     * The request method
     *
     * @var string
     */
    protected $method = 'GET';

    /**
     * The request URI object
     *
     * @var Uri
     */
    protected $uri;
    

    /**
     * ClientRequest constructor.
     *
     * @param Uri $uri
     * @param string $method
     * @param string $body
     */
    public function __construct($uri, $method = 'GET', $body = '')
    {
        parent::__construct();
        $this->setUri($uri);
        $this->setMethod($method);
        $this->setBody($body);
    }


    /**
     * @param $method
     */
    public function setMethod($method)
    {
        if (!isset($this->validMethods[$method])) {
            throw new \InvalidArgumentException('Invalid HTTP Protocol method.');
        }
        $this->method = $method;
    }

    /**
     * Retrieves the HTTP method of the request.
     *
     * @return string Returns the request method.
     */
    public function getMethod()
    {
        return $this->method;
    }
    
    /**
     * Retrieves the message's request target.
     *
     * Retrieves the message's request-target either as it will appear (for
     * clients), as it appeared at request (for servers), or as it was
     * specified for the instance (see withRequestTarget()).
     *
     * In most cases, this will be the origin-form of the composed URI,
     * unless a value was provided to the concrete implementation (see
     * withRequestTarget() below).
     *
     * If no URI is available, and no request-target has been specifically
     * provided, this method MUST return the string "/".
     *
     * @return string
     * @see http://tools.ietf.org/html/rfc7230#section-2.7
     */
    public function getRequestTarget()
    {
        if (!$this->uri)
            return '/';

        $uri = $this->getUri()->getPath();
        $query = $this->getUri()->getQuery();
        if ($query != '') {
            $uri .= '?' . $query;
        }
        return $uri;
    }
    

    /**
     * Retrieves the URI instance.
     * This method MUST return a Uri instance.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-4.3
     * @return Uri Returns a Uri instance
     *     representing the URI of the request.
     */
    public function getUri() 
    {
        return $this->uri;
    }

    /**
     * @param Uri $uri
     * @return $this
     */
    public function setUri(Uri $uri)
    {
        $this->uri = $uri;
        return $this;
    }
    
    
}
