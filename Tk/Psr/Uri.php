<?php
namespace Tk\Psr;

use Psr\Http\Message\UriInterface;

/** A URI class.
 *
 * <b>[[&lt;scheme&gt;://][[&lt;user&gt;[:&lt;password&gt;]@]&lt;host&gt;[:&lt;port&gt;]]][/[&lt;path&gt;][?&lt;query&gt;][#&lt;fragment&gt;]]</b>
 *
 * Where:
 *
 * - __scheme__ defaults to http
 * - __host__ defaults to the current host
 * - __port__ defaults to 80
 *
 * <code>
 * echo Uri::create('/full/uri/path/index.html')->__toString();
 * // Result:
 * //  http://localhost/full/uri/path/index.html
 * </code>
 *
 * If the static $BASE_URL_PATH is set this will be prepended to all relative paths
 * when creating a URI
 * 
 * 
 * 
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @link http://www.php-fig.org/psr/psr-7/#3-6-psr-http-message-uploadedfileinterface
 * @license Copyright 2007 Michael Mifsud
 */
class Uri extends \Tk\Uri implements UriInterface
{    

    /**
     * Return an instance with the specified scheme.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified scheme.
     *
     * Implementations MUST support the schemes "http" and "https" case
     * insensitively, and MAY accommodate other schemes if required.
     *
     * An empty scheme is equivalent to removing the scheme.
     *
     * @param string $scheme The scheme to use with the new instance.
     * @return Uri A new instance with the specified scheme.
     * @throws \InvalidArgumentException for invalid or unsupported schemes.
     */
    public function withScheme($scheme)
    {
        $uri = clone $this;
        return $uri->setScheme($scheme);
    }

    /**
     * Return an instance with the specified user information.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified user information.
     *
     * Password is optional, but the user information MUST include the
     * user; an empty string for the user is equivalent to removing user
     * information.
     *
     * @param string $username The user name to use for authority.
     * @param null|string $password The password associated with $user.
     * @return Uri A new instance with the specified user information.
     */
    public function withUserInfo($username, $password = null)
    {
        $uri = clone $this;
        $uri->setUsername($username);
        $uri->setPassword($password);
        return $uri;
    }

    /**
     * Return an instance with the specified host.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified host.
     *
     * An empty host value is equivalent to removing the host.
     *
     * @param string $host The hostname to use with the new instance.
     * @return Uri A new instance with the specified host.
     * @throws \InvalidArgumentException for invalid hostnames.
     */
    public function withHost($host)
    {
        $uri = clone $this;
        return $uri->setHost($host);
    }

    /**
     * Return an instance with the specified port.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified port.
     *
     * Implementations MUST raise an exception for ports outside the
     * established TCP and UDP port ranges.
     *
     * A null value provided for the port is equivalent to removing the port
     * information.
     *
     * @param null|int $port The port to use with the new instance; a null value
     *     removes the port information.
     * @return Uri A new instance with the specified port.
     * @throws \InvalidArgumentException for invalid ports.
     */
    public function withPort($port)
    {
        $uri = clone $this;
        return $uri->setPort($port);
    }

    /**
     * Return an instance with the specified path.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified path.
     *
     * The path can either be empty or absolute (starting with a slash) or
     * rootless (not starting with a slash). Implementations MUST support all
     * three syntaxes.
     *
     * If the path is intended to be domain-relative rather than path relative then
     * it must begin with a slash ("/"). Paths not starting with a slash ("/")
     * are assumed to be relative to some base path known to the application or
     * consumer.
     *
     * Users can provide both encoded and decoded path characters.
     * Implementations ensure the correct encoding as outlined in getPath().
     *
     * @param string $path The path to use with the new instance.
     * @return Uri A new instance with the specified path.
     * @throws \InvalidArgumentException for invalid paths.
     */
    public function withPath($path)
    {
        $uri = clone $this;
        return $uri->setPath($path);
    }

    /**
     * Return an instance with the specified query string.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified query string.
     *
     * Users can provide both encoded and decoded query characters.
     * Implementations ensure the correct encoding as outlined in getQuery().
     *
     * An empty query string value is equivalent to removing the query string.
     *
     * @param string $query The query string to use with the new instance.
     * @return Uri A new instance with the specified query string.
     * @throws \InvalidArgumentException for invalid query strings.
     */
    public function withQuery($query)
    {
        $uri = clone $this;
        if ($query) {
            parse_str($query, $uri->query);
        } else {
            $uri->query = array();
        }
        return $uri;
    }

    /**
     * Return an instance with the specified URI fragment.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified URI fragment.
     *
     * Users can provide both encoded and decoded fragment characters.
     * Implementations ensure the correct encoding as outlined in getFragment().
     *
     * An empty fragment value is equivalent to removing the fragment.
     *
     * @param string $fragment The fragment to use with the new instance.
     * @return Uri A new instance with the specified fragment.
     */
    public function withFragment($fragment)
    {
        $uri = clone $this;
        return $uri->setFragment($fragment);
    }

    
}