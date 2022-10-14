<?php
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
 * @link        http://pear.php.net/package/HTTP_Request/
 */

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