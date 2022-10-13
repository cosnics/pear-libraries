<?php
// +-----------------------------------------------------------------------+
// | Copyright (c) 2002-2004, Richard Heyes                                |
// | All rights reserved.                                                  |
// |                                                                       |
// | Redistribution and use in source and binary forms, with or without    |
// | modification, are permitted provided that the following conditions    |
// | are met:                                                              |
// |                                                                       |
// | o Redistributions of source code must retain the above copyright      |
// |   notice, this list of conditions and the following disclaimer.       |
// | o Redistributions in binary form must reproduce the above copyright   |
// |   notice, this list of conditions and the following disclaimer in the |
// |   documentation and/or other materials provided with the distribution.|
// | o The names of the authors may not be used to endorse or promote      |
// |   products derived from this software without specific prior written  |
// |   permission.                                                         |
// |                                                                       |
// | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS   |
// | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT     |
// | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR |
// | A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT  |
// | OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, |
// | SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT      |
// | LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, |
// | DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY |
// | THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT   |
// | (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE |
// | OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.  |
// |                                                                       |
// +-----------------------------------------------------------------------+
// | Author: Richard Heyes <richard at php net>                            |
// +-----------------------------------------------------------------------+
//

class Net_URL
{
    public string $anchor;

    public string $host;

    public array $options = ['encode_query_keys' => false];

    public string $password;

    public string $path;

    public int $port;

    public string $protocol;

    public array $querystring;

    public string $url;

    /**
     * Whether to use []
     */
    public bool $useBrackets;

    public string $username;

    /**
     * Parses the given url and stores the various parts
     * Defaults are used in certain cases
     *
     * @param ?string $url        Optional URL
     * @param bool $useBrackets   Whether to use square brackets when
     *                            multiple querystrings with the same name
     *                            exist
     */
    public function __construct(?string $url = null, bool $useBrackets = true)
    {
        $this->url = $url;
        $this->useBrackets = $useBrackets;

        $this->initialize();
    }

    /**
     * Parses raw querystring and returns an array of it
     */
    protected function _parseRawQuerystring(string $querystring): array
    {
        $parts = preg_split(
            '/[' . preg_quote(ini_get('arg_separator.input'), '/') . ']/', $querystring, - 1, PREG_SPLIT_NO_EMPTY
        );
        $return = [];

        foreach ($parts as $part)
        {
            if (strpos($part, '=') !== false)
            {
                $value = substr($part, strpos($part, '=') + 1);
                $key = substr($part, 0, strpos($part, '='));
            }
            else
            {
                $value = null;
                $key = $part;
            }

            if (!$this->getOption('encode_query_keys'))
            {
                $key = rawurldecode($key);
            }

            if (preg_match('#^(.*)\[([0-9a-z_-]*)\]#i', $key, $matches))
            {
                $key = $matches[1];
                $idx = $matches[2];

                // Ensure is an array
                if (empty($return[$key]) || !is_array($return[$key]))
                {
                    $return[$key] = [];
                }

                // Add data
                if ($idx === '')
                {
                    $return[$key][] = $value;
                }
                else
                {
                    $return[$key][$idx] = $value;
                }
            }
            elseif (!$this->useBrackets and !empty($return[$key]))
            {
                $return[$key] = (array) $return[$key];
                $return[$key][] = $value;
            }
            else
            {
                $return[$key] = $value;
            }
        }

        return $return;
    }

    /**
     * Adds or updates a querystring item (URL parameter).
     * Automatically encodes parameters with rawurlencode() if $preencoded
     *  is false.
     * You can pass an array to $value, it gets mapped via [] in the URL if
     * $this->useBrackets is activated.
     *
     * @param string|array $value Value of item
     * @param bool $preencoded    Whether value is urlencoded or not, default = not
     */
    public function addQueryString(string $name, $value, bool $preencoded = false)
    {
        if ($this->getOption('encode_query_keys'))
        {
            $name = rawurlencode($name);
        }

        if ($preencoded)
        {
            $this->querystring[$name] = $value;
        }
        else
        {
            $this->querystring[$name] = is_array($value) ? array_map('rawurlencode', $value) : rawurlencode($value);
        }
    }

    /**
     * Sets the querystring to literally what you supply
     *
     * @param string $querystring The querystring data. Should be of the format foo=bar&x=y etc
     */
    public function addRawQueryString(string $querystring)
    {
        $this->querystring = $this->_parseRawQuerystring($querystring);
    }

    /**
     * Get an option. This function gets an option from the $this->options array and return it's value.
     *
     * @see    $this->options
     */
    public function getOption(string $optionName)
    {
        if (!isset($this->options[$optionName]))
        {
            return false;
        }

        return $this->options[$optionName];
    }

    /**
     * Returns flat querystring
     *
     * @return string Querystring
     */
    public function getQueryString(): string
    {
        if (!empty($this->querystring))
        {
            $querystring = [];

            foreach ($this->querystring as $name => $value)
            {
                // Encode var name
                $name = rawurlencode($name);

                if (is_array($value))
                {
                    foreach ($value as $k => $v)
                    {
                        $querystring[] = $this->useBrackets ? sprintf('%s[%s]=%s', $name, $k, $v) : ($name . '=' . $v);
                    }
                }
                elseif (!is_null($value))
                {
                    $querystring[] = $name . '=' . $value;
                }
                else
                {
                    $querystring[] = $name;
                }
            }

            $querystring = implode(ini_get('arg_separator.output'), $querystring);
        }
        else
        {
            $querystring = '';
        }

        return $querystring;
    }

    /**
     * Returns the standard port number for a protocol
     *
     * @param string $scheme The protocol to lookup
     *
     * @author Philippe Jausions <Philippe.Jausions@11abacus.com>
     */
    public function getStandardPort(string $scheme): ?int
    {
        switch (strtolower($scheme))
        {
            case 'http':
                return 80;
            case 'https':
                return 443;
            case 'ftp':
                return 21;
            case 'imap':
                return 143;
            case 'imaps':
                return 993;
            case 'pop3':
                return 110;
            case 'pop3s':
                return 995;
            default:
                return null;
        }
    }

    public function getURL(): string
    {
        $querystring = $this->getQueryString();

        $this->url =
            $this->protocol . '://' . $this->username . (!empty($this->password) ? ':' : '') . $this->password .
            (!empty($this->user) ? '@' : '') . $this->host .
            ($this->port == $this->getStandardPort($this->protocol) ? '' : ':' . $this->port) . $this->path .
            (!empty($querystring) ? '?' . $querystring : '') . (!empty($this->anchor) ? '#' . $this->anchor : '');

        return $this->url;
    }

    public function initialize()
    {
        $HTTP_SERVER_VARS = !empty($_SERVER) ? $_SERVER : $GLOBALS['HTTP_SERVER_VARS'];

        $this->username = '';
        $this->password = '';
        $this->host = '';
        $this->port = 80;
        $this->path = '';
        $this->querystring = [];
        $this->anchor = '';

        // Only use defaults if not an absolute URL given
        if (!preg_match('/^[a-z0-9]+:\/\//i', $this->url))
        {
            $this->protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https' : 'http');

            /**
             * Figure out host/port
             */
            if (!empty($HTTP_SERVER_VARS['HTTP_HOST']) &&
                preg_match('/^(.*)(:([0-9]+))?$/U', $HTTP_SERVER_VARS['HTTP_HOST'], $matches))
            {
                $host = $matches[1];

                if (!empty($matches[3]))
                {
                    $port = $matches[3];
                }
                else
                {
                    $port = $this->getStandardPort($this->protocol);
                }
            }

            $this->username = '';
            $this->password = '';
            $this->host = !empty($host) ? $host : ($HTTP_SERVER_VARS['SERVER_NAME'] ?? 'localhost');
            $this->port =
                !empty($port) ? $port : ($HTTP_SERVER_VARS['SERVER_PORT'] ?? $this->getStandardPort($this->protocol));
            $this->path = !empty($HTTP_SERVER_VARS['PHP_SELF']) ? $HTTP_SERVER_VARS['PHP_SELF'] : '/';
            $this->querystring = isset($HTTP_SERVER_VARS['QUERY_STRING']) ?
                $this->_parseRawQuerystring($HTTP_SERVER_VARS['QUERY_STRING']) : null;
            $this->anchor = '';
        }

        // Parse the url and store the various parts
        if (!empty($this->url))
        {
            $urlinfo = parse_url($this->url);

            // Default querystring
            $this->querystring = [];

            foreach ($urlinfo as $key => $value)
            {
                switch ($key)
                {
                    case 'scheme':
                        $this->protocol = $value;
                        $this->port = $this->getStandardPort($value);
                        break;

                    case 'user':
                    case 'pass':
                    case 'host':
                    case 'port':
                        $this->$key = $value;
                        break;

                    case 'path':
                        if ($value[0] == '/')
                        {
                            $this->path = $value;
                        }
                        else
                        {
                            $path = dirname($this->path) == DIRECTORY_SEPARATOR ? '' : dirname($this->path);
                            $this->path = sprintf('%s/%s', $path, $value);
                        }
                        break;

                    case 'query':
                        $this->querystring = $this->_parseRawQuerystring($value);
                        break;

                    case 'fragment':
                        $this->anchor = $value;
                        break;
                }
            }
        }
    }

    /**
     * Removes a querystring item
     *
     * @param string $name Name of item
     */
    public function removeQueryString(string $name)
    {
        if ($this->getOption('encode_query_keys'))
        {
            $name = rawurlencode($name);
        }

        if (isset($this->querystring[$name]))
        {
            unset($this->querystring[$name]);
        }
    }

    /**
     * Resolves //, ../ and ./ from a path and returns
     * the result. Eg:
     * /foo/bar/../boo.php    => /foo/boo.php
     * /foo/bar/../../boo.php => /boo.php
     * /foo/bar/.././/boo.php => /foo/boo.php
     * This method can also be called statically.
     */
    public static function resolvePath(string $path): string
    {
        $path = explode('/', str_replace('//', '/', $path));

        for ($i = 0; $i < count($path); $i ++)
        {
            if ($path[$i] == '.')
            {
                unset($path[$i]);
                $path = array_values($path);
                $i --;
            }
            elseif ($path[$i] == '..' and ($i > 1 or ($i == 1 and $path[0] != '')))
            {
                unset($path[$i]);
                unset($path[$i - 1]);
                $path = array_values($path);
                $i -= 2;
            }
            elseif ($path[$i] == '..' and $i == 1 and $path[0] == '')
            {
                unset($path[$i]);
                $path = array_values($path);
                $i --;
            }
        }

        return implode('/', $path);
    }

    /**
     * Set an option. This function set an option to be used thorough the script.
     */
    public function setOption(string $optionName, string $value)
    {
        if (!array_key_exists($optionName, $this->options))
        {
            return;
        }

        $this->options[$optionName] = $value;
        $this->initialize();
    }

    /**
     * Forces the URL to a particular protocol
     *
     * @param string $protocol Protocol to force the URL to
     * @param ?int $port       Optional port (standard port is used by default)
     */
    public function setProtocol(string $protocol, ?int $port = null)
    {
        $this->protocol = $protocol;
        $this->port = is_null($port) ? $this->getStandardPort($protocol) : $port;
    }

}