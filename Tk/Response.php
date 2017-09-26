<?php
namespace Tk;

/**
 * Class Response
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2016 Michael Mifsud
 */
class Response extends Message
{

    /**
     * Status codes and reason phrases
     *
     * @var array
     */
    protected static $messages = [
        //Informational 1xx
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        //Successful 2xx
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        //Redirection 3xx
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => '(Unused)',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        //Client Error 4xx
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        //Server Error 5xx
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];
    
    /**
     * Status code
     *
     * @var int
     */
    protected $status = 200;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reasonPhrase = '';
    
    
    
    /**
     * Create new HTTP response.
     *
     * @param string|null           $body    The response body.
     * @param int                   $status  The response status code.
     * @param array                 $headers The response headers.
     */
    public function __construct($body = null, $status = 200, $headers = array())
    {
        parent::__construct($headers);
        $this->body = $body ? $body : '';
        $this->status = $this->filterStatus($status);
    }

    /**
     * @param null|string $body
     * @param int $status
     * @param array $headers
     * @return Response
     */
    public static function create($body = null, $status = 200, $headers = array()) {
        $obj = new static($body, $status, $headers);
        return $obj;
    }
    
    /**
     * Sends HTTP headers.
     *
     * @return Response
     */
    public function sendHeaders()
    {
        // headers have already been sent by the developer
        if (headers_sent()) {
            return $this;
        }

        // status
        header(sprintf('HTTP/%s %s %s', $this->getProtocolVersion(), $this->getStatusCode(),
            self::$messages[$this->getStatusCode()]), true, $this->getStatusCode());

        // headers
        foreach ($this->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header($name.': '.$value, false, $this->getStatusCode());
            }
        }

        // cookies ?? Do not think we need this...
//        foreach ($this->headers->getCookies() as $cookie) {
//            setcookie($cookie->getName(), $cookie->getValue(), $cookie->getExpiresTime(), $cookie->getPath(), $cookie->getDomain(), $cookie->isSecure(), $cookie->isHttpOnly());
//        }

        return $this;
    }

    /**
     * Sends content for the current web response.
     *
     * @return Response
     */
    public function sendContent()
    {
        echo $this->getBody();
        return $this;
    }
    
    /**
     * Sends HTTP headers and content.
     * tkLib v1 equivalent to flush()
     *
     * @return Response
     */
    public function send()
    {
        $this->sendHeaders();
        $this->sendContent();
        
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } 
//        elseif ('cli' !== PHP_SAPI) {
//            static::closeOutputBuffers(0, true);
//        }
        return $this;
    }
    
    
    /**
     * Write data to the response body.
     *
     * @param string $data
     * @return self
     */
    public function write($data)
    {
        $this->body .= $data;
        return $this;
    }

    
    
    
    /**
     * Filter HTTP status code.
     *
     * @param  int $status HTTP status code.
     * @return int
     * @throws \InvalidArgumentException If an invalid HTTP status code is provided.
     */
    protected function filterStatus($status)
    {
        if (!is_integer($status) || !isset(static::$messages[$status])) {
            throw new \InvalidArgumentException('Invalid HTTP status code');
        }

        return $status;
    }
    
    /**
     * Gets the response status code.
     *
     * The status code is a 3-digit integer result code of the server's attempt
     * to understand and satisfy the request.
     *
     * @return int Status code.
     */
    public function getStatusCode()
    {
        return $this->status;
    }

    /**
     * @param $status
     */
    public function setStatusCode($status)
    {
        $this->status = (int)$status;
    }

    /**
     * Gets the response reason phrase associated with the status code.
     *
     * Because a reason phrase is not a required element in a response
     * status line, the reason phrase value MAY be null. Implementations MAY
     * choose to return the default RFC 7231 recommended reason phrase (or those
     * listed in the IANA HTTP Status Code Registry) for the response's
     * status code.
     *
     * @link http://tools.ietf.org/html/rfc7231#section-6
     * @link http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     * @return string Reason phrase; must return an empty string if none present.
     */
    public function getReasonPhrase()
    {
        if ($this->reasonPhrase) {
            return $this->reasonPhrase;
        }
        return static::$messages[$this->status];
    }
    
    
    
    /**
     * Convert response to string.
     *
     * @return string
     */
    public function __toString()
    {
        $output = sprintf(
            'HTTP/%s %s %s',
            $this->getProtocolVersion(),
            $this->getStatusCode(),
            $this->getReasonPhrase()
        );
        $output .= PHP_EOL;
        foreach ($this->getHeaders() as $name => $values) {
            $output .= sprintf('%s: %s', $name, $this->getHeaderLine($name)) . PHP_EOL;
        }
        $output .= PHP_EOL;
        $output .= $this->getBody();

        return $output;
    }
    
}