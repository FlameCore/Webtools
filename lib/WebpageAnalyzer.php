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
 * The WebpageAnalyzer class
 *
 * @author   Christian Neff <christian.neff@gmail.com>
 * @author   Alejandro Parra <maparrar@gmail.com>
 * @author   Tony of RedsunSoft <tony@redsunsoft.com>
 */
class WebpageAnalyzer
{
    /**
     * The URL of the webpage
     *
     * @var string
     */
    protected $url;

    /**
     * The base URL
     *
     * @var string
     */
    protected $baseUrl;

    /**
     * The local URL
     *
     * @var string
     */
    protected $localUrl;

    /**
     * The HttpClient object
     *
     * @var \FlameCore\Webtools\HttpClient
     */
    protected $http;

    /**
     * The HtmlExplorer object
     *
     * @var \FlameCore\Webtools\HtmlExplorer
     */
    protected $html;

    /**
     * Creates a WebpageAnalyzer object.
     *
     * @param string $url The URL of the webpage
     * @param \FlameCore\Webtools\HttpClient $http The HttpClient instance to use
     * @throws \RuntimeException if the URL could not be loaded.
     */
    public function __construct($url, HttpClient $http = null)
    {
        $this->url = $url;

        $this->baseUrl = preg_replace('#^(https?://[^/]+).+$#', '\1', $url);
        $this->localUrl = preg_replace('#^(https?://.+)/[^/]+$#', '\1', $url);

        $http = $http ?: new HttpClient();
        $html = HtmlExplorer::fromWeb($url, $http);

        $node = $html->findFirstTag('base');
        if ($node && $href = $node->getAttribute('href')) {
            $this->baseUrl = trim($href, ' /');
        }

        $this->http = $http;
        $this->html = $html;
    }

    /**
     * Gets the title of the webpage.
     *
     * @return string
     */
    public function getTitle()
    {
        $node = $this->html->findFirstTag('title');

        return $node ? trim($node->nodeValue) : null;
    }

    /**
     * Gets the description of the webpage.
     *
     * @return string
     */
    public function getDescription()
    {
        $nodes = $this->html->findTags('meta');
        foreach ($nodes as $node) {
            if (strtolower($node->getAttribute('name')) == 'description') {
                return trim($node->getAttribute('content'));
            }
        }

        return null;
    }

    /**
     * Gets the images of the webpage.
     *
     * @return array
     */
    public function getImages()
    {
        $images = array();

        $nodes = $this->html->findTags('img');
        foreach ($nodes as $node) {
            $source = trim($node->getAttribute('src'));

            if (empty($source)) {
                continue;
            }

            $url = $this->getAbsoluteUrl($source);
            $size = $this->getImageSize($url);

            if (is_array($size)) {
                list($width, $height) = $size;
            } else {
                continue;
            }

            if ($this->isImageAccepted($url, $width, $height)) {
                $images[] = array(
                    'url'    => $url,
                    'width'  => $width,
                    'height' => $height,
                    'area'   => ($width * $height)
                );
            }
        }

        return $images;
    }

    /**
     * Transforms the given URL to an absolute URL.
     *
     * @param string $href The relative URL
     * @return string
     */
    protected function getAbsoluteUrl($href)
    {
        if (preg_match('#^(https?|ftps?)://#', $href)) {
            return $href;
        } elseif (substr($href, 0, 2) == '//') {
            return parse_url($this->baseUrl, PHP_URL_SCHEME).':'.$href;
        } elseif ($href[0] == '/') {
            return $this->baseUrl.$href;
        } else {
            return $this->localUrl.'/'.$href;
        }
    }

    /**
     * Returns whether the given image is accepted.
     *
     * @param string $url The image URL
     * @param int $width The image width
     * @param int $height The image height
     * @return bool
     */
    protected function isImageAccepted($url, $width, $height)
    {
        return $width > 299 || $height > 149;
    }

    /**
     * Returns the size of an image.
     *
     * @param string $url The URL of the image
     * @return array|false Returns an array with width and height of the image or FALSE on error.
     */
    protected function getImageSize($url)
    {
        $request = $this->http->get($url, [
            'Range' => 'bytes=0-32768'
        ]);

        if (!$request->success) {
            return false;
        }

        return getimagesizefromstring($request->data);
    }
}
