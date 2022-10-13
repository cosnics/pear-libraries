<?php
/** @noinspection PhpComposerExtensionStubsInspection */
/**
 * Class for performing HTTP requests
 * PHP versions 4 and 5
 * LICENSE:
 * Copyright (c) 2002-2007, Richard Heyes
 * All rights reserved.
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 * o Redistributions of source code must retain the above copyright
 *   notice, this list of conditions and the following disclaimer.
 * o Redistributions in binary form must reproduce the above copyright
 *   notice, this list of conditions and the following disclaimer in the
 *   documentation and/or other materials provided with the distribution.
 * o The names of the authors may not be used to endorse or promote
 *   products derived from this software without specific prior written
 *   permission.
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category    HTTP
 * @package     HTTP_Request
 * @author      Richard Heyes <richard@phpguru.org>
 * @author      Alexey Borzov <avb@php.net>
 * @copyright   2002-2007 Richard Heyes
 * @license     http://opensource.org/licenses/bsd-license.php New BSD License
 * @version     CVS: $Id: Request.php,v 1.63 2008/10/11 11:07:10 avb Exp $
 * @link        http://pear.php.net/package/HTTP_Request/
 */

/**
 * Constants for HTTP request methods
 */
const HTTP_REQUEST_METHOD_GET = 'GET';
const HTTP_REQUEST_METHOD_HEAD = 'HEAD';
const HTTP_REQUEST_METHOD_POST = 'POST';
const HTTP_REQUEST_METHOD_PUT = 'PUT';
const HTTP_REQUEST_METHOD_DELETE = 'DELETE';
const HTTP_REQUEST_METHOD_OPTIONS = 'OPTIONS';
const HTTP_REQUEST_METHOD_TRACE = 'TRACE';
/**#@-*/

/**
 * Constants for HTTP request error codes
 */
const HTTP_REQUEST_ERROR_FILE = 1;
const HTTP_REQUEST_ERROR_URL = 2;
const HTTP_REQUEST_ERROR_PROXY = 4;
const HTTP_REQUEST_ERROR_REDIRECTS = 8;
const HTTP_REQUEST_ERROR_RESPONSE = 16;
const HTTP_REQUEST_ERROR_GZIP_METHOD = 32;
const HTTP_REQUEST_ERROR_GZIP_READ = 64;
const HTTP_REQUEST_ERROR_GZIP_DATA = 128;
const HTTP_REQUEST_ERROR_GZIP_CRC = 256;

/**
 * Constants for HTTP protocol versions
 */
const HTTP_REQUEST_HTTP_VER_1_0 = '1.0';
const HTTP_REQUEST_HTTP_VER_1_1 = '1.1';

if (extension_loaded('mbstring') && (2 & ini_get('mbstring.func_overload')))
{
    /**
     * Whether string functions are overloaded by their mbstring equivalents
     */
    define('HTTP_REQUEST_MBSTRING', true);
}
else
{
    /**
     * @ignore
     */
    define('HTTP_REQUEST_MBSTRING', false);
}

/**
 * Class for performing HTTP requests
 * Simple example (fetches yahoo.com and displays it):
 * <code>
 * $a = &new HTTP_Request('http://www.yahoo.com/');
 * $a->sendRequest();
 * echo $a->getResponseBody();
 * </code>
 *
 * @category    HTTP
 * @package     HTTP_Request
 * @author      Richard Heyes <richard@phpguru.org>
 * @author      Alexey Borzov <avb@php.net>
 * @version     Release: 1.4.4
 */
class HTTP_Request
{

    protected bool $_allowRedirects;

    protected string $_body;

    /**
     * A list of methods that MUST NOT have a request body, per RFC 2616
     */
    protected array $_bodyDisallowed = ['TRACE'];

    /**
     * Methods having defined semantics for request body
     * Content-Length header (indicating that the body follows, section 4.3 of
     * RFC 2616) will be sent for these methods even if nobody was added
     */
    protected array $_bodyRequired = ['POST', 'PUT'];

    /**
     * HTTP Version
     */
    protected string $_http;

    protected int $_maxRedirects;

    /**
     * Type of request
     */
    protected string $_method;

    protected string $_pass;

    protected ?array $_postData;

    protected array $_postFiles = [];

    protected string $_proxy_host;

    protected string $_proxy_pass;

    protected ?int $_proxy_port;

    protected string $_proxy_user;

    /**
     * Timeout for reading from socket (array(seconds, microseconds))
     */
    protected ?array $_readTimeout = null;

    /**
     * Current number of redirects
     */
    protected int $_redirects;

    protected array $_requestHeaders;

    protected ?HTTP_Response $_response;

    /**
     * Whether to save response body in response object property
     */
    protected bool $_saveBody = true;

    protected Net_Socket $_sock;

    /**
     * Options to pass to Net_Socket::connect. See stream_context_create
     */
    protected ?array $_socketOptions = null;

    /**
     * Connection timeout.
     */
    protected ?float $_timeout;

    protected Net_URL $_url;

    /**
     * Whether to append brackets [] to array variables
     */
    protected bool $_useBrackets = true;

    protected string $_user;

    /**
     * @param array $params Associative array of parameters which can have the following keys:
     *                      <ul>
     *                      <li>method         - Method to use, GET, POST etc (string)</li>
     *                      <li>http           - HTTP Version to use, 1.0 or 1.1 (string)</li>
     *                      <li>user           - Basic Auth username (string)</li>
     *                      <li>pass           - Basic Auth password (string)</li>
     *                      <li>proxy_host     - Proxy server host (string)</li>
     *                      <li>proxy_port     - Proxy server port (integer)</li>
     *                      <li>proxy_user     - Proxy auth username (string)</li>
     *                      <li>proxy_pass     - Proxy auth password (string)</li>
     *                      <li>timeout        - Connection timeout in seconds (float)</li>
     *                      <li>allowRedirects - Whether to follow redirects or not (bool)</li>
     *                      <li>maxRedirects   - Max number of redirects to follow (integer)</li>
     *                      <li>useBrackets    - Whether to append [] to array variable names (bool)</li>
     *                      <li>saveBody       - Whether to save response body in response object property (bool)</li>
     *                      <li>readTimeout    - Timeout for reading / writing data over the socket (array (seconds,
     *                      microseconds))</li>
     *                      <li>socketOptions  - Options to pass to Net_Socket object (array)</li>
     *                      </ul>
     */
    public function __construct(string $url = '', array $params = [])
    {
        $this->_method = HTTP_REQUEST_METHOD_GET;
        $this->_http = HTTP_REQUEST_HTTP_VER_1_1;
        $this->_requestHeaders = [];
        $this->_postData = [];
        $this->_body = null;

        $this->_user = null;
        $this->_pass = null;

        $this->_proxy_host = null;
        $this->_proxy_port = null;
        $this->_proxy_user = null;
        $this->_proxy_pass = null;

        $this->_allowRedirects = false;
        $this->_maxRedirects = 3;
        $this->_redirects = 0;

        $this->_timeout = null;
        $this->_response = null;

        foreach ($params as $key => $value)
        {
            $this->{'_' . $key} = $value;
        }

        if (!empty($url))
        {
            $this->setURL($url);
        }

        // Default useragent
        $this->addHeader('User-Agent', 'PEAR HTTP_Request class ( http://pear.php.net/ )');

        // We don't do keep-alives by default
        $this->addHeader('Connection', 'close');

        // Basic authentication
        if (!empty($this->_user))
        {
            $this->addHeader('Authorization', 'Basic ' . base64_encode($this->_user . ':' . $this->_pass));
        }

        // Proxy authentication (see bug #5913)
        if (!empty($this->_proxy_user))
        {
            $this->addHeader(
                'Proxy-Authorization', 'Basic ' . base64_encode($this->_proxy_user . ':' . $this->_proxy_pass)
            );
        }

        // Use gzip encoding if possible
        if (HTTP_REQUEST_HTTP_VER_1_1 == $this->_http && extension_loaded('zlib'))
        {
            $this->addHeader('Accept-Encoding', 'gzip');
        }
    }

    /**
     * Recursively applies the callback function to the value
     *
     * @param mixed $callback Callback function
     * @param mixed $value    Value to process
     *
     * @return   mixed   Processed value
     */
    protected function _arrayMapRecursive($callback, $value)
    {
        if (!is_array($value))
        {
            return call_user_func($callback, $value);
        }
        else
        {
            $map = [];

            foreach ($value as $k => $v)
            {
                $map[$k] = $this->_arrayMapRecursive($callback, $v);
            }

            return $map;
        }
    }

    /**
     * Builds the request string
     *
     * @return string The request string
     */
    protected function _buildRequest(): string
    {
        $separator = ini_get('arg_separator.output');
        ini_set('arg_separator.output', '&');
        $querystring = ($querystring = $this->_url->getQueryString()) ? '?' . $querystring : '';
        ini_set('arg_separator.output', $separator);

        $host = isset($this->_proxy_host) ? $this->_url->protocol . '://' . $this->_url->host : '';
        $port = (isset($this->_proxy_host) and $this->_url->port != 80) ? ':' . $this->_url->port : '';
        $path = $this->_url->path . $querystring;
        $url = $host . $port . $path;

        if (!strlen($url))
        {
            $url = '/';
        }

        $request = $this->_method . ' ' . $url . ' HTTP/' . $this->_http . "\r\n";

        if (in_array($this->_method, $this->_bodyDisallowed) || (0 == strlen($this->_body) &&
                (HTTP_REQUEST_METHOD_POST != $this->_method || (empty($this->_postData) && empty($this->_postFiles)))))
        {
            $this->removeHeader('Content-Type');
        }
        elseif (empty($this->_requestHeaders['content-type']))
        {
            // Add default content-type
            $this->addHeader('Content-Type', 'application/x-www-form-urlencoded');
        }
        elseif ('multipart/form-data' == $this->_requestHeaders['content-type'])
        {
            $boundary = 'HTTP_Request_' . md5(uniqid('request') . microtime());
            $this->addHeader('Content-Type', 'multipart/form-data; boundary=' . $boundary);
        }

        // Request Headers
        if (!empty($this->_requestHeaders))
        {
            foreach ($this->_requestHeaders as $name => $value)
            {
                $canonicalName = implode('-', array_map('ucfirst', explode('-', $name)));
                $request .= $canonicalName . ': ' . $value . "\r\n";
            }
        }

        // Method does not allow a body, simply add a final CRLF
        if (in_array($this->_method, $this->_bodyDisallowed))
        {

            $request .= "\r\n";
            // Post data if it's an array
        }
        elseif (HTTP_REQUEST_METHOD_POST == $this->_method && (!empty($this->_postData) || !empty($this->_postFiles)))
        {

            // "normal" POST request
            if (!isset($boundary))
            {
                $postdata = implode(
                    '&', array_map(
                        create_function('$a', 'return $a[0] . \'=\' . $a[1];'),
                        $this->_flattenArray('', $this->_postData)
                    )
                );
                // multipart request, probably with file uploads
            }
            else
            {
                $postdata = '';
                if (!empty($this->_postData))
                {
                    $flatData = $this->_flattenArray('', $this->_postData);

                    foreach ($flatData as $item)
                    {
                        $postdata .= '--' . $boundary . "\r\n";
                        $postdata .= 'Content-Disposition: form-data; name="' . $item[0] . '"';
                        $postdata .= "\r\n\r\n" . urldecode($item[1]) . "\r\n";
                    }
                }
                foreach ($this->_postFiles as $name => $value)
                {
                    if (is_array($value['name']))
                    {
                        $varname = $name . ($this->_useBrackets ? '[]' : '');
                    }
                    else
                    {
                        $varname = $name;
                        $value['name'] = [$value['name']];
                    }

                    foreach ($value['name'] as $key => $filename)
                    {
                        $fp = fopen($filename, 'r');
                        $basename = basename($filename);
                        $type = is_array($value['type']) ? $value['type'][$key] : $value['type'];

                        $postdata .= '--' . $boundary . "\r\n";
                        $postdata .= 'Content-Disposition: form-data; name="' . $varname . '"; filename="' . $basename .
                            '"';
                        $postdata .= "\r\nContent-Type: " . $type;
                        $postdata .= "\r\n\r\n" . fread($fp, filesize($filename)) . "\r\n";
                        fclose($fp);
                    }
                }
                $postdata .= '--' . $boundary . "--\r\n";
            }
            $request .= 'Content-Length: ' .
                (HTTP_REQUEST_MBSTRING ? mb_strlen($postdata, 'iso-8859-1') : strlen($postdata)) . "\r\n\r\n";
            $request .= $postdata;
            // Explicitly set request body
        }
        elseif (0 < strlen($this->_body))
        {

            $request .= 'Content-Length: ' .
                (HTTP_REQUEST_MBSTRING ? mb_strlen($this->_body, 'iso-8859-1') : strlen($this->_body)) . "\r\n\r\n";
            $request .= $this->_body;

            // No body: send a Content-Length header nonetheless (request #12900),
            // but do that only for methods that require a body (bug #14740)
        }
        else
        {

            if (in_array($this->_method, $this->_bodyRequired))
            {
                $request .= "Content-Length: 0\r\n";
            }

            $request .= "\r\n";
        }

        return $request;
    }

    /**
     * Helper function to change the (probably multidimensional) associative array
     * into the simple one.
     *
     * @param string $name  name for item
     * @param mixed $values item's values
     *
     * @return   array   array with the following items: array('item name', 'item value');
     */
    protected function _flattenArray(string $name, $values): array
    {
        if (!is_array($values))
        {
            return [[$name, $values]];
        }
        else
        {
            $ret = [];

            foreach ($values as $k => $v)
            {
                if (empty($name))
                {
                    $newName = $k;
                }
                elseif ($this->_useBrackets)
                {
                    $newName = $name . '[' . $k . ']';
                }
                else
                {
                    $newName = $name;
                }
                $ret = array_merge($ret, $this->_flattenArray($newName, $v));
            }

            return $ret;
        }
    }

    /**
     * Generates a Host header for HTTP/1.1 requests
     *
     * @return string
     */
    protected function _generateHostHeader(): string
    {
        if ($this->_url->port != 80 and strcasecmp($this->_url->protocol, 'http') == 0)
        {
            $host = $this->_url->host . ':' . $this->_url->port;
        }
        elseif ($this->_url->port != 443 and strcasecmp($this->_url->protocol, 'https') == 0)
        {
            $host = $this->_url->host . ':' . $this->_url->port;
        }
        elseif ($this->_url->port == 443 and
            strcasecmp($this->_url->protocol, 'https') == 0 and strpos($this->_url->url, ':443') !== false)
        {
            $host = $this->_url->host . ':' . $this->_url->port;
        }
        else
        {
            $host = $this->_url->host;
        }

        return $host;
    }

    /**
     * Appends a cookie to "Cookie:" header
     *
     * @param string $name  cookie name
     * @param string $value cookie value
     */
    public function addCookie(string $name, string $value)
    {
        $cookies = isset($this->_requestHeaders['cookie']) ? $this->_requestHeaders['cookie'] . '; ' : '';
        $this->addHeader('Cookie', $cookies . $name . '=' . $value);
    }

    /**
     * Adds a file to form-based file upload
     * Used to emulate file upload via a HTML form. The method also sets
     * Content-Type of HTTP request to 'multipart/form-data'.
     * If you just want to send the contents of a file as the body of HTTP
     * request you should use setBody() method.
     *
     * @param string $inputName  name of file-upload field
     * @param mixed $fileName    file name(s)
     * @param mixed $contentType content-type(s) of file(s) being uploaded
     *
     * @return bool      true on success
     * @throws \RequestException
     */
    public function addFile(string $inputName, $fileName, $contentType = 'application/octet-stream'): bool
    {
        if (!is_array($fileName) && !is_readable($fileName))
        {
            throw new RequestException("File '$fileName' is not readable");
        }
        elseif (is_array($fileName))
        {
            foreach ($fileName as $name)
            {
                if (!is_readable($name))
                {
                    throw new RequestException("File '$name' is not readable");
                }
            }
        }
        $this->addHeader('Content-Type', 'multipart/form-data');
        $this->_postFiles[$inputName] = [
            'name' => $fileName,
            'type' => $contentType
        ];

        return true;
    }

    /**
     * Adds a request header
     *
     * @param string $name  Header name
     * @param string $value Header value
     */
    public function addHeader(string $name, string $value)
    {
        $this->_requestHeaders[strtolower($name)] = $value;
    }

    /**
     * Adds postdata items
     *
     * @param string $name     Post data name
     * @param string $value    Post data value
     * @param bool $preencoded Whether data is already urlencoded or not, default = not
     */
    public function addPostData(string $name, string $value, bool $preencoded = false)
    {
        if ($preencoded)
        {
            $this->_postData[$name] = $value;
        }
        else
        {
            $this->_postData[$name] = $this->_arrayMapRecursive('urlencode', $value);
        }
    }

    /**
     * Adds a querystring parameter
     *
     * @param string $name     Querystring parameter name
     * @param string $value    Querystring parameter value
     * @param bool $preencoded Whether the value is already urlencoded or not, default = not
     */
    public function addQueryString(string $name, string $value, bool $preencoded = false)
    {
        $this->_url->addQueryString($name, $value, $preencoded);
    }

    /**
     * Adds raw postdata (DEPRECATED)
     *
     * @param string $postdata The data
     * @param bool $preencoded Whether data is preencoded or not, default = already encoded
     *
     * @deprecated       deprecated since 1.3.0, method setBody() should be used instead
     */
    public function addRawPostData(string $postdata, bool $preencoded = true)
    {
        $this->_body = $preencoded ? $postdata : urlencode($postdata);
    }

    /**
     * Sets the querystring to literally what you supply
     *
     * @param string $querystring The querystring data. Should be of the format foo=bar&x=y etc
     */
    public function addRawQueryString(string $querystring)
    {
        $this->_url->addRawQueryString($querystring);
    }

    /**
     * Clears any cookies that have been added (DEPRECATED).
     * Useful for multiple request scenarios
     *
     * @deprecated deprecated since 1.2
     */
    public function clearCookies()
    {
        $this->removeHeader('Cookie');
    }

    /**
     * Clears any postdata that has been added (DEPRECATED).
     * Useful for multiple request scenarios.
     *
     * @deprecated deprecated since 1.2
     */
    public function clearPostData()
    {
        $this->_postData = null;
    }

    /**
     * Disconnect the socket, if connected. Only useful if using Keep-Alive.
     *
     * @throws \RequestException
     */
    public function disconnect()
    {
        if (!empty($this->_sock) && !empty($this->_sock->fp))
        {
            $this->_sock->disconnect();
        }
    }

    /**
     * Returns the body of the response
     *
     * @return string|bool     response body, false if not set
     */
    public function getResponseBody()
    {
        return $this->_response->_body ?? false;
    }

    /**
     * Returns the response code
     *
     * @return string|bool     Response code, false if not set
     */
    public function getResponseCode()
    {
        return $this->_response->_code ?? false;
    }

    /**
     * Returns cookies set in response
     *
     * @return array|bool     array of response cookies, false if none are present
     */
    public function getResponseCookies()
    {
        return $this->_response->_cookies ?? false;
    }

    /**
     * Returns either the named header or all if no name given
     *
     * @param ?string $headername The header name to return, do not set to get all headers
     *
     * @return mixed     either the value of $headername (false if header is not present)
     *                   or an array of all headers
     */
    public function getResponseHeader(?string $headername = null)
    {
        if (!isset($headername))
        {
            return $this->_response->_headers ?? [];
        }
        else
        {
            $headername = strtolower($headername);

            return $this->_response->_headers[$headername] ?? false;
        }
    }

    /**
     * Returns the response reason phrase
     *
     * @return string|bool     Response reason phrase, false if not set
     */
    public function getResponseReason()
    {
        return $this->_response->_reason ?? false;
    }

    /**
     * If you have a class that's mostly/entirely static, and you need static
     * properties, you can use this method to simulate them. Eg. in your method(s)
     * do this: $myVar = &PEAR::getStaticProperty('myclass', 'myVar');
     * You MUST use a reference, or they will not persist!
     *
     * @param string $class The calling classname, to prevent clashes
     * @param string $var   The variable to retrieve.
     *
     * @return mixed   A reference to the variable. If not set it will be
     *                 auto initialised to NULL.
     */
    public static function &getStaticProperty(string $class, string $var)
    {
        static $properties;

        if (!isset($properties[$class]))
        {
            $properties[$class] = [];
        }

        if (!array_key_exists($var, $properties[$class]))
        {
            $properties[$class][$var] = null;
        }

        return $properties[$class][$var];
    }

    /**
     * Returns the current request URL
     *
     * @return   string  Current request URL
     */
    public function getUrl(): string
    {
        return empty($this->_url) ? '' : $this->_url->getURL();
    }

    /**
     * Sets the URL to be requested
     *
     * @param string $url The url to be requested
     */
    public function setURL(string $url)
    {
        $this->_url = new Net_URL($url, $this->_useBrackets);

        if (!empty($this->_url->username) || !empty($this->_url->password))
        {
            $this->setBasicAuth($this->_url->username, $this->_url->password);
        }

        if (HTTP_REQUEST_HTTP_VER_1_1 == $this->_http)
        {
            $this->addHeader('Host', $this->_generateHostHeader());
        }

        // set '/' instead of empty path rather than check later (see bug #8662)
        if (empty($this->_url->path))
        {
            $this->_url->path = '/';
        }
    }

    /**
     * Removes a request header
     *
     * @param string $name Header name to remove
     */
    public function removeHeader(string $name)
    {
        if (isset($this->_requestHeaders[strtolower($name)]))
        {
            unset($this->_requestHeaders[strtolower($name)]);
        }
    }

    /**
     * Resets the object to its initial state (DEPRECATED).
     * Takes the same parameters as the constructor.
     *
     * @param string $url     The url to be requested
     * @param array $params   Associative array of parameters
     *                        (see constructor for details)
     *
     * @deprecated deprecated since 1.2, call the constructor if this is necessary
     */
    public function reset(string $url, array $params = [])
    {
        $this->__construct($url, $params);
    }

    /**
     * Sends the request
     *
     * @param bool $saveBody Whether to store response body in Response object property,
     *                       set this to false if downloading a LARGE file and using a Listener
     *
     * @return bool
     * @throws \RequestException
     */
    public function sendRequest(bool $saveBody = true): bool
    {
        if (!is_a($this->_url, 'Net_URL'))
        {
            throw new RequestException('No URL given');
        }

        $host = $this->_proxy_host ?? $this->_url->host;
        $port = $this->_proxy_port ?? $this->_url->port;

        if (strcasecmp($this->_url->protocol, 'https') == 0)
        {
            // Bug #14127, don't try connecting to HTTPS sites without OpenSSL
            if (version_compare(PHP_VERSION, '4.3.0', '<') || !extension_loaded('openssl'))
            {
                throw new RequestException('Need PHP 4.3.0 or later with OpenSSL support for https:// requests');
            }
            elseif (isset($this->_proxy_host))
            {
                throw new RequestException('HTTPS proxies are not supported');
            }
            $host = 'ssl://' . $host;
        }

        // magic quotes may fuck up file uploads and chunked response processing
        $magicQuotes = ini_get('magic_quotes_runtime');
        ini_set('magic_quotes_runtime', false);

        // RFC 2068, section 19.7.1: A client MUST NOT send the Keep-Alive
        // connection token to a proxy server...
        if (isset($this->_proxy_host) && !empty($this->_requestHeaders['connection']) &&
            'Keep-Alive' == $this->_requestHeaders['connection'])
        {
            $this->removeHeader('connection');
        }

        $keepAlive = (HTTP_REQUEST_HTTP_VER_1_1 == $this->_http && empty($this->_requestHeaders['connection'])) ||
            (!empty($this->_requestHeaders['connection']) && 'Keep-Alive' == $this->_requestHeaders['connection']);
        $sockets = &self::getStaticProperty('HTTP_Request', 'sockets');
        $sockKey = $host . ':' . $port;
        unset($this->_sock);

        // There is a connected socket in the "static" property?
        if ($keepAlive && !empty($sockets[$sockKey]) && !empty($sockets[$sockKey]->fp))
        {
            $this->_sock =& $sockets[$sockKey];
        }
        else
        {
            $this->_sock = new Net_Socket();
            $this->_sock->connect($host, $port, null, $this->_timeout, $this->_socketOptions);
        }

        $this->_sock->write($this->_buildRequest());

        if (!empty($this->_readTimeout))
        {
            $this->_sock->setTimeout($this->_readTimeout[0], $this->_readTimeout[1]);
        }

        // Read the response
        $this->_response = new HTTP_Response($this->_sock);
        $this->_response->process(
            $this->_saveBody && $saveBody, HTTP_REQUEST_METHOD_HEAD != $this->_method
        );

        if ($keepAlive)
        {
            $keepAlive = (isset($this->_response->_headers['content-length']) ||
                (isset($this->_response->_headers['transfer-encoding']) &&
                    strtolower($this->_response->_headers['transfer-encoding']) == 'chunked'));

            if ($keepAlive)
            {
                if (isset($this->_response->_headers['connection']))
                {
                    $keepAlive = strtolower($this->_response->_headers['connection']) == 'keep-alive';
                }
                else
                {
                    $keepAlive = 'HTTP/' . HTTP_REQUEST_HTTP_VER_1_1 == $this->_response->_protocol;
                }
            }
        }

        ini_set('magic_quotes_runtime', $magicQuotes);

        if (!$keepAlive)
        {
            $this->disconnect();
            // Store the connected socket in "static" property
        }
        elseif (empty($sockets[$sockKey]) || empty($sockets[$sockKey]->fp))
        {
            $sockets[$sockKey] =& $this->_sock;
        }

        // Check for redirection
        if ($this->_allowRedirects and $this->_redirects <= $this->_maxRedirects and $this->getResponseCode() > 300 and
            $this->getResponseCode() < 399 and !empty($this->_response->_headers['location']))
        {


            $redirect = $this->_response->_headers['location'];

            // Absolute URL
            if (preg_match('/^https?:\/\//i', $redirect))
            {
                $this->_url = new Net_URL($redirect);
                $this->addHeader('Host', $this->_generateHostHeader());
                // Absolute path
            }
            elseif ($redirect[0] == '/')
            {
                $this->_url->path = $redirect;
                // Relative path
            }
            elseif (substr($redirect, 0, 3) == '../' or substr($redirect, 0, 2) == './')
            {
                if (substr($this->_url->path, - 1) == '/')
                {
                    $redirect = $this->_url->path . $redirect;
                }
                else
                {
                    $redirect = dirname($this->_url->path) . '/' . $redirect;
                }

                $redirect = Net_URL::resolvePath($redirect);
                $this->_url->path = $redirect;
                // Filename, no path
            }
            else
            {
                if (substr($this->_url->path, - 1) == '/')
                {
                    $redirect = $this->_url->path . $redirect;
                }
                else
                {
                    $redirect = dirname($this->_url->path) . '/' . $redirect;
                }

                $this->_url->path = $redirect;
            }

            $this->_redirects ++;

            return $this->sendRequest($saveBody);
            // Too many redirects
        }
        elseif ($this->_allowRedirects and $this->_redirects > $this->_maxRedirects)
        {
            throw new RequestException('Too many redirects');
        }

        return true;
    }

    /**
     * Sets basic authentication parameters
     *
     * @param string $user Username
     * @param string $pass Password
     */
    public function setBasicAuth(string $user, string $pass)
    {
        $this->_user = $user;
        $this->_pass = $pass;

        $this->addHeader('Authorization', 'Basic ' . base64_encode($user . ':' . $pass));
    }

    /**
     * Sets the request body (for POST, PUT and similar requests)
     *
     * @param string $body Request body
     */
    public function setBody(string $body)
    {
        $this->_body = $body;
    }

    /**
     * Sets the HTTP version to use, 1.0 or 1.1
     *
     * @param string $http Version to use. Use the defined constants for this
     */
    public function setHttpVer(string $http)
    {
        $this->_http = $http;
    }

    /**
     * Sets the method to be used, GET, POST etc.
     *
     * @param string $method Method to use. Use the defined constants for this
     */
    public function setMethod(string $method)
    {
        $this->_method = $method;
    }

    /**
     * Sets a proxy to be used
     *
     * @param string $host  Proxy host
     * @param int $port     Proxy port
     * @param ?string $user Proxy username
     * @param ?string $pass Proxy password
     */
    public function setProxy(string $host, int $port = 8080, ?string $user = null, ?string $pass = null)
    {
        $this->_proxy_host = $host;
        $this->_proxy_port = $port;
        $this->_proxy_user = $user;
        $this->_proxy_pass = $pass;

        if (!empty($user))
        {
            $this->addHeader('Proxy-Authorization', 'Basic ' . base64_encode($user . ':' . $pass));
        }
    }
}

/**
 * Response class to complement the Request class
 *
 * @category    HTTP
 * @package     HTTP_Request
 * @author      Richard Heyes <richard@phpguru.org>
 * @author      Alexey Borzov <avb@php.net>
 * @version     Release: 1.4.4
 */
class HTTP_Response
{

    public string $_body = '';

    /**
     * Used by _readChunked(): remaining length of the current chunk
     */
    public int $_chunkLength = 0;

    public string $_code;

    /**
     * Cookies set in response
     */
    public array $_cookies;

    public array $_headers;

    public string $_protocol;

    /**
     * Response reason phrase
     */
    public string $_reason;

    public Net_Socket $_sock;

    /**
     * Bytes left to read from message-body
     */
    public ?int $_toRead;

    /**
     * @param Net_Socket $sock socket to read the response from
     */
    public function __construct(Net_Socket $sock)
    {
        $this->_sock = $sock;
    }

    /**
     * Decodes the message-body encoded by gzip
     * The real decoding work is done by gzinflate() built-in function, this
     * method only parses the header and checks data for compliance with
     * RFC 1952
     *
     * @throws \RequestException
     */
    protected function _decodeGzip(string $data): string
    {
        if (HTTP_REQUEST_MBSTRING)
        {
            $oldEncoding = mb_internal_encoding();
            mb_internal_encoding('iso-8859-1');
        }

        $length = strlen($data);

        // If it doesn't look like gzip-encoded data, don't bother
        if (18 > $length || strcmp(substr($data, 0, 2), "\x1f\x8b"))
        {
            return $data;
        }

        $method = ord(substr($data, 2, 1));

        if (8 != $method)
        {
            throw new RequestException('_decodeGzip(): unknown compression method');
        }

        $flags = ord(substr($data, 3, 1));

        if ($flags & 224)
        {
            throw new RequestException('_decodeGzip(): reserved bits are set');
        }

        // header is 10 bytes minimum. may be longer, though.
        $headerLength = 10;

        // extra fields, need to skip 'em
        if ($flags & 4)
        {
            if ($length - $headerLength - 2 < 8)
            {
                throw new RequestException('_decodeGzip(): data too short');
            }

            $extraLength = unpack('v', substr($data, 10, 2));

            if ($length - $headerLength - 2 - $extraLength[1] < 8)
            {
                throw new RequestException('_decodeGzip(): data too short');
            }
            $headerLength += $extraLength[1] + 2;
        }

        // file name, need to skip that
        if ($flags & 8)
        {
            if ($length - $headerLength - 1 < 8)
            {
                throw new RequestException('_decodeGzip(): data too short');
            }

            $filenameLength = strpos(substr($data, $headerLength), chr(0));

            if (false === $filenameLength || $length - $headerLength - $filenameLength - 1 < 8)
            {
                throw new RequestException('_decodeGzip(): data too short');
            }

            $headerLength += $filenameLength + 1;
        }

        // comment, need to skip that also
        if ($flags & 16)
        {
            if ($length - $headerLength - 1 < 8)
            {
                throw new RequestException('_decodeGzip(): data too short');
            }

            $commentLength = strpos(substr($data, $headerLength), chr(0));

            if (false === $commentLength || $length - $headerLength - $commentLength - 1 < 8)
            {
                throw new RequestException('_decodeGzip(): data too short');
            }

            $headerLength += $commentLength + 1;
        }

        // have a CRC for header. let's check
        if ($flags & 1)
        {
            if ($length - $headerLength - 2 < 8)
            {
                throw new RequestException('_decodeGzip(): data too short');
            }

            $crcReal = 0xffff & crc32(substr($data, 0, $headerLength));
            $crcStored = unpack('v', substr($data, $headerLength, 2));

            if ($crcReal != $crcStored[1])
            {
                throw new RequestException('_decodeGzip(): header CRC check failed');
            }
            $headerLength += 2;
        }

        // unpacked data CRC and size at the end of encoded data
        $tmp = unpack('V2', substr($data, - 8));
        $dataCrc = $tmp[1];
        $dataSize = $tmp[2];

        // finally, call the gzinflate() function
        // don't pass $dataSize to gzinflate, see bugs #13135, #14370
        $unpacked = gzinflate(substr($data, $headerLength, - 8));

        if (false === $unpacked)
        {
            throw new RequestException('_decodeGzip(): gzinflate() call failed');
        }
        elseif ($dataSize != strlen($unpacked))
        {
            throw new RequestException('_decodeGzip(): data size check failed');
        }
        elseif ((0xffffffff & $dataCrc) != (0xffffffff & crc32($unpacked)))
        {
            throw new RequestException('_decodeGzip(): data CRC check failed');
        }

        if (HTTP_REQUEST_MBSTRING && isset($oldEncoding))
        {
            mb_internal_encoding($oldEncoding);
        }

        return $unpacked;
    }

    /**
     * Parse a Set-Cookie header to fill $_cookies array
     *
     * @param string $headervalue value of Set-Cookie header
     */
    protected function _parseCookie(string $headervalue)
    {
        $cookie = [
            'expires' => null,
            'domain' => null,
            'path' => null,
            'secure' => false
        ];

        // Only a name=value pair
        if (!strpos($headervalue, ';'))
        {
            $pos = strpos($headervalue, '=');
            $cookie['name'] = trim(substr($headervalue, 0, $pos));
            $cookie['value'] = trim(substr($headervalue, $pos + 1));
            // Some optional parameters are supplied
        }
        else
        {
            $elements = explode(';', $headervalue);
            $pos = strpos($elements[0], '=');
            $cookie['name'] = trim(substr($elements[0], 0, $pos));
            $cookie['value'] = trim(substr($elements[0], $pos + 1));

            for ($i = 1; $i < count($elements); $i ++)
            {
                if (false === strpos($elements[$i], '='))
                {
                    $elName = trim($elements[$i]);
                    $elValue = null;
                }
                else
                {
                    [$elName, $elValue] = array_map('trim', explode('=', $elements[$i]));
                }

                $elName = strtolower($elName);

                if ('secure' == $elName)
                {
                    $cookie['secure'] = true;
                }
                elseif ('expires' == $elName)
                {
                    $cookie['expires'] = str_replace('"', '', $elValue);
                }
                elseif ('path' == $elName || 'domain' == $elName)
                {
                    $cookie[$elName] = urldecode($elValue);
                }
                else
                {
                    $cookie[$elName] = $elValue;
                }
            }
        }
        $this->_cookies[] = $cookie;
    }

    protected function _processHeader(string $header)
    {
        if (false === strpos($header, ':'))
        {
            return;
        }

        [$headername, $headervalue] = explode(':', $header, 2);
        $headername = strtolower($headername);
        $headervalue = ltrim($headervalue);

        if ('set-cookie' != $headername)
        {
            if (isset($this->_headers[$headername]))
            {
                $this->_headers[$headername] .= ',' . $headervalue;
            }
            else
            {
                $this->_headers[$headername] = $headervalue;
            }
        }
        else
        {
            $this->_parseCookie($headervalue);
        }
    }

    /**
     * Read a part of response body encoded with chunked Transfer-Encoding
     *
     * @throws \RequestException
     */
    protected function _readChunked(): string
    {
        // at start of the next chunk?
        if (0 == $this->_chunkLength)
        {
            $line = $this->_sock->readLine();

            if (preg_match('/^([0-9a-f]+)/i', $line, $matches))
            {
                $this->_chunkLength = hexdec($matches[1]);
                // Chunk with zero length indicates the end
                if (0 == $this->_chunkLength)
                {
                    $this->_sock->readLine(); // make this an eof()

                    return '';
                }
            }
            else
            {
                return '';
            }
        }

        $data = $this->_sock->read($this->_chunkLength);
        $this->_chunkLength -= HTTP_REQUEST_MBSTRING ? mb_strlen($data, 'iso-8859-1') : strlen($data);

        if (0 == $this->_chunkLength)
        {
            $this->_sock->readLine(); // Trailing CRLF
        }

        return $data;
    }

    /**
     * Processes a HTTP response
     * This extracts response code, headers, cookies and decodes body if it
     * was encoded in some way
     *
     * @param bool $saveBody    Whether to store response body in object property, set
     *                          this to false if downloading a LARGE file and using a Listener.
     *                          This is assumed to be true if body is gzip-encoded.
     * @param bool $canHaveBody Whether the response can actually have a message-body.
     *                          Will be set to false for HEAD requests.
     *
     * @throws \RequestException
     */
    public function process(bool $saveBody = true, bool $canHaveBody = true): bool
    {
        do
        {
            $line = $this->_sock->readLine();

            if (!preg_match('!^(HTTP/\d\.\d) (\d{3})(?: (.+))?!', $line, $s))
            {
                throw new RequestException('Malformed response');
            }
            else
            {
                $this->_protocol = $s[1];
                $this->_code = intval($s[2]);
                $this->_reason = empty($s[3]) ? null : $s[3];
            }

            while ('' !== ($header = $this->_sock->readLine()))
            {
                $this->_processHeader($header);
            }
        }
        while (100 == $this->_code);

        // RFC 2616, section 4.4:
        // 1. Any response message which "MUST NOT" include a message-body ...
        // is always terminated by the first empty line after the header fields
        // 3. ... If a message is received with both a
        // Transfer-Encoding header field and a Content-Length header field,
        // the latter MUST be ignored.
        $canHaveBody = $canHaveBody && $this->_code >= 200 && $this->_code != 204 && $this->_code != 304;

        // If response body is present, read it and decode
        $chunked = isset($this->_headers['transfer-encoding']) && ('chunked' == $this->_headers['transfer-encoding']);
        $gzipped = isset($this->_headers['content-encoding']) && ('gzip' == $this->_headers['content-encoding']);
        $hasBody = false;

        if ($canHaveBody &&
            ($chunked || !isset($this->_headers['content-length']) || 0 != $this->_headers['content-length']))
        {
            if ($chunked || !isset($this->_headers['content-length']))
            {
                $this->_toRead = null;
            }
            else
            {
                $this->_toRead = $this->_headers['content-length'];
            }

            while (!$this->_sock->eof() && (is_null($this->_toRead) || 0 < $this->_toRead))
            {
                if ($chunked)
                {
                    $data = $this->_readChunked();
                }
                elseif (is_null($this->_toRead))
                {
                    $data = $this->_sock->read(4096);
                }
                else
                {
                    $data = $this->_sock->read(min(4096, $this->_toRead));
                    $this->_toRead -= HTTP_REQUEST_MBSTRING ? mb_strlen($data, 'iso-8859-1') : strlen($data);
                }

                if ('' == $data && (!$this->_chunkLength || $this->_sock->eof()))
                {
                    break;
                }
                else
                {
                    $hasBody = true;

                    if ($saveBody || $gzipped)
                    {
                        $this->_body .= $data;
                    }
                }
            }
        }

        if ($hasBody)
        {
            // Uncompress the body if needed
            if ($gzipped)
            {
                $body = $this->_decodeGzip($this->_body);
                $this->_body = $body;
            }
        }

        return true;
    }
}