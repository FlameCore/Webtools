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

namespace FlameCore\Webtools\Tests;

use FlameCore\Webtools\WebpageAnalyzer;

/**
 * Test class for WebpageAnalyzer
 */
class WebpageAnalyzerTest extends \PHPUnit_Framework_TestCase
{
    public function test()
    {
        $analyzer = new WebpageAnalyzer('http://localhost:8000/test.html');

        $this->assertEquals('Test Page', $analyzer->getTitle());
        $this->assertEquals('This is a test page.', $analyzer->getDescription());

        $expected = array_fill(0, 2,
            array(
                'url' => 'http://localhost:8000/img.png',
                'width' => 422,
                'height' => 343,
                'area' => 144746,
            )
        );

        $this->assertEquals($expected, $analyzer->getImages());
    }
}
