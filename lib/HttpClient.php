<?php
/**
 * FlameCore Webtools
 * Copyright (C) 2015 IceFlame.net
 *
 * Permission to use, copy, modify, and/or distribute this software for
 * any purpose with or without fee is hereby granted, provided that the
 * above copyright notice and this permission notice appear in all copies.
 *
 * THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
 * WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE
 * FOR ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY
 * DAMAGES WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER
 * IN AN ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING
 * OUT OF OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
 *
 * @package  FlameCore\Webtools
 * @version  2.0
 * @link     http://www.flamecore.org
 * @license  http://opensource.org/licenses/ISC ISC License
 */

namespace FlameCore\Webtools;

/**
 * The HttpClient class
 *
 * @author   Christian Neff <christian.neff@gmail.com>
 */
class HttpClient
{
    const ENCODING_ALL = '';
    const ENCODING_GZIP = 'gzip';
    const ENCODING_DEFLATE = 'deflate';
    const ENCODING_IDENTITY = 'identity';

    const AUTH_BASIC = CURLAUTH_BASIC;
    const AUTH_NTLM = CURLAUTH_NTLM;

    const PROXY_HTTP = CURLPROXY_HTTP;
    const PROXY_SOCKS5 = CURLPROXY_SOCKS5;

    /**
     * The headers
     *
     * @var array
     */
    protected $headers = array();

    /**
     * The user agent string
     *
     * @var string
     */
    protected $useragent = 'Mozilla/5.0 (compatible; FlameCore Webtools/2.0)';

    /**
     * The timeout in seconds
     *
     * @var int
     */
    protected $timeout = 30;

    /**
     * The accepted encoding
     *
     * @var string
     */
    protected $encoding = self::ENCODING_ALL;

    /**
     * The curl handle
     *
     * @var resource
     */
    protected $handle;

    /**
     * Creates a HttpClient object.
     *
     * @param string $useragent The user agent string
     */
    public function __construct($useragent = null)
    {
        $this->handle = curl_init();

        curl_setopt($this->handle, CURLOPT_HEADER, false);
        curl_setopt($this->handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->handle, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->handle, CURLOPT_HEADERFUNCTION, [$this, 'buffer']);

        $this->headers = array(
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Connection' => 'Keep-Alive'
        );

        if ($useragent !== null) {
            $this->setUserAgent($useragent);
        }
    }

    /**
     * Destructs the object.
     *
     * @return void
     */
    public function __destruct()
    {
        curl_close($this->handle);
    }

    /**
     * Executes a GET request.
     *
     * @param string $url The URL to make the request to
     * @param array $headers Optional extra headers
     * @return object Returns an object containing the response information.
     */
    public function get($url, array $headers = array())
    {
        curl_setopt($this->handle, CURLOPT_HTTPGET, true);

        return $this->execute($url, $headers);
    }

    /**
     * Executes a POST request.
     *
     * @param string $url The URL to make the request to
     * @param array|string $data The full data to post in the operation
     * @param array $headers Optional extra headers
     * @return object Returns an object containing the response information.
     */
    public function post($url, $data, array $headers = array())
    {
        curl_setopt($this->handle, CURLOPT_POST, true);
        curl_setopt($this->handle, CURLOPT_POSTFIELDS, $data);

        return $this->execute($url, $headers);
    }

    /**
     * Executes a PUT request.
     *
     * @param string $url The URL to make the request to
     * @param array|string $data The full data to post in the operation
     * @param array $headers Optional extra headers
     * @return object Returns an object containing the response information.
     */
    public function put($url, $data, array $headers = array())
    {
        curl_setopt($this->handle, CURLOPT_CUSTOMREQUEST, 'PUT');

        $fields = is_array($data) ? http_build_query($data) : $data;
        curl_setopt($this->handle, CURLOPT_POSTFIELDS, $fields);

        $headers = array_replace($headers, ['Content-Length' => strlen($fields)]);
        return $this->execute($url, $headers);
    }

    /**
     * Executes a PUT request using a file.
     *
     * @param string $url The URL to make the request to
     * @param string|resource $file The file that the transfer should be read from when uploading
     * @param array $headers Optional extra headers
     * @return object Returns an object containing the response information.
     */
    public function putFile($url, $file, array $headers = array())
    {
        $file = $this->openFile($file);
        $stat = fstat($file);

        curl_setopt($this->handle, CURLOPT_PUT, true);
        curl_setopt($this->handle, CURLOPT_INFILE, $file);
        curl_setopt($this->handle, CURLOPT_INFILESIZE, $stat['size']);

        return $this->execute($url, $headers);
    }

    /**
     * Executes a custom request.
     *
     * @param string $method The custom request method verb
     * @param string $url The URL to make the request to
     * @param array|string $data The data to post in the operation
     * @param array $headers Optional extra headers
     * @return object Returns an object containing the response information.
     */
    public function request($method, $url, $data = null, array $headers = array())
    {
        curl_setopt($this->handle, CURLOPT_CUSTOMREQUEST, strtoupper($method));

        if (!empty($data)) {
            curl_setopt($this->handle, CURLOPT_POSTFIELDS, $data);
        }

        return $this->execute($url, $headers);
    }

    /**
     * Gets all defined headers.
     *
     * @return array Returns the headers.
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Sets a header.
     *
     * @param string $name The name of the header
     * @param string $value The value of the header
     */
    public function setHeader($name, $value)
    {
        $this->headers[$name] = $value;
    }

    /**
     * Sets multiple headers.
     *
     * @param array $headers The headers to set
     */
    public function setHeaders(array $headers)
    {
        $this->headers = array_merge($this->headers, $headers);
    }

    /**
     * Gets the user agent string.
     *
     * @return string
     */
    public function getUserAgent()
    {
        return $this->useragent;
    }

    /**
     * Sets the user agent string.
     *
     * @param string $useragent The new user agent string
     */
    public function setUserAgent($useragent)
    {
        $this->useragent = (string) $useragent;
    }

    /**
     * Gets the timeout.
     *
     * @return int Returns the timeout in seconds.
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * Sets the timeout.
     *
     * @param int $timeout The new timeout in seconds
     */
    public function setTimeout($timeout)
    {
        $this->timeout = (int) $timeout;
    }

    /**
     * Gets the encoding.
     *
     * @return string Returns the encoding.
     */
    public function getEncoding()
    {
        return $this->encoding;
    }

    /**
     * Sets the encoding.
     *
     * @param string $encoding The encoding to use. This can be any of the `HttpClient::ENCODING_*` constants.
     */
    public function setEncoding($encoding)
    {
        if (!in_array($encoding, [self::ENCODING_IDENTITY, self::ENCODING_DEFLATE, self::ENCODING_GZIP, self::ENCODING_ALL])) {
            throw new \InvalidArgumentException('The encoding must be one of: HttpClient::ENCODING_IDENTITY, HttpClient::ENCODING_DEFLATE, HttpClient::ENCODING_GZIP, HttpClient::ENCODING_ALL.');
        }

        $this->encoding = $encoding;
    }

    /**
     * Enables the use of cookies.
     *
     * @param string $jarfile The full path to the file where cookies are saved
     * @throws \InvalidArgumentException if the given parameter is invalid.
     * @throws \LogicException if the cookie file could not be opened.
     */
    public function acceptCookies($jarfile = null)
    {
        $jarfile = $jarfile ? (string) $jarfile : sys_get_temp_dir().DIRECTORY_SEPARATOR.'cookies.txt';

        if (!is_file($jarfile) && !touch($jarfile)) {
            throw new \LogicException(sprintf('Cookie file "%s" could not be opened. Make sure that the directory is writable.', $jarfile));
        }

        curl_setopt($this->handle, CURLOPT_COOKIEFILE, $jarfile);
        curl_setopt($this->handle, CURLOPT_COOKIEJAR, $jarfile);
    }

    /**
     * Enables the use of a proxy.
     *
     * @param string $proxy The proxy to use. Use `@` to separate credentials and address.
     * @param int $type The type of proxy. This can be one of: `HttpClient::PROXY_HTTP` (default), `HttpClient::PROXY_SOCKS5`.
     * @param int $auth The HTTP authentication method(s) to use for the proxy connection.
     *   This can be one of: `HttpClient::AUTH_BASIC` (default), `HttpClient::AUTH_NTLM`.
     * @throws \InvalidArgumentException if the given parameter is invalid.
     */
    public function useProxy($proxy, $type = self::PROXY_HTTP, $auth = self::AUTH_BASIC)
    {
        $proxy = (string) $proxy;

        if (!in_array($type, [self::PROXY_HTTP, self::PROXY_SOCKS5])) {
            throw new \InvalidArgumentException('The $type parameter must be one of: HttpClient::PROXY_HTTP, HttpClient::PROXY_SOCKS5.');
        }

        if (!in_array($auth, [self::AUTH_BASIC, self::AUTH_NTLM])) {
            throw new \InvalidArgumentException('The $auth parameter must be one of: HttpClient::AUTH_BASIC, HttpClient::AUTH_NTLM.');
        }

        if (strpos($proxy, '@') !== false) {
            list($proxyCredentials, $proxyAddress) = explode('@', $proxy, 2);
            curl_setopt($this->handle, CURLOPT_PROXY, $proxyAddress);
            curl_setopt($this->handle, CURLOPT_PROXYUSERPWD, $proxyCredentials);
        } else {
            curl_setopt($this->handle, CURLOPT_PROXY, $proxy);
        }
    }

    /**
     * Really executes a request to the given URL.
     *
     * @param string $url The URL to fetch
     * @param array $headers Optional extra headers
     * @return object Returns an object containing the response information.
     */
    protected function execute($url, array $headers = array())
    {
        curl_setopt($this->handle, CURLOPT_URL, $url);

        curl_setopt($this->handle, CURLOPT_USERAGENT, $this->useragent);
        curl_setopt($this->handle, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($this->handle, CURLOPT_ENCODING, $this->encoding);

        $curlheaders = array();

        $headers = array_merge($this->headers, $headers);
        foreach ($headers as $headerName => $headerValue) {
            $curlheaders[] = "$headerName: $headerValue";
        }

        curl_setopt($this->handle, CURLOPT_HTTPHEADER, $curlheaders);

        $response = curl_exec($this->handle);
        if ($response !== false) {
            $info = curl_getinfo($this->handle);
            if ($info && $info['http_code'] >= 200 && $info['http_code'] < 300) {
                $headers = $this->buffer();

                $info['success'] = true;
                $info['headers'] = $this->parseHeaders($headers);
                $info['data'] = $response;
            } else {
                $info['success'] = false;
            }
        } else {
            $info = array();
            $info['success'] = false;
            $info['error'] = curl_errno($this->handle);
            $info['error_text'] = curl_error($this->handle);
        }

        return (object) $info;
    }

    /**
     * Parses the given HTTP headers.
     *
     * @param string $rawHeaders The raw HTTP headers
     * @return array
     */
    protected function parseHeaders($rawHeaders)
    {
        $headers = array();

        $lines = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $rawHeaders));
        foreach ($lines as $header) {
            if (preg_match('/([^:]+): (.+)/m', $header, $match)) {
                if (!isset($headers[$match[1]])) {
                    $headers[$match[1]] = trim($match[2]);
                } elseif (is_array($headers[$match[1]])) {
                    $headers[$match[1]][] = trim($match[2]);
                } else {
                    $headers[$match[1]] = array($headers[$match[1]], trim($match[2]));
                }
            }
        }

        return $headers;
    }

    /**
     * Opens a file handle.
     *
     * @param string|resource $file The file to open
     * @return resource
     */
    private function openFile($file)
    {
        if (is_resource($file)) {
            if (get_resource_type($file) !== 'stream') {
                throw new \InvalidArgumentException('The given resource is not a file handle.');
            }
        } else {
            $filename = (string) $file;

            if (!is_file($filename) || !is_readable($filename)) {
                throw new \LogicException(sprintf('File "%s" could not be opened.', $filename));
            }

            $file = fopen($filename, 'r');
        }

        return $file;
    }

    /**
     * Buffers the line read by curl. When no handle is given, returns and clears the content of the buffer.
     *
     * @param resource $curl The curl handle
     * @param string $line The current line
     * @return int|string
     */
    private function buffer($curl = null, $line = null)
    {
        static $buffer;

        if ($curl) {
            $buffer .= $line;

            return strlen($line);
        } else {
            $return = $buffer;
            $buffer = '';

            return $return;
        }
    }
}
