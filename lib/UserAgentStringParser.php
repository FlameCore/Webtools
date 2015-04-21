<?php
/**
 * Webtools Library
 * Copyright (C) 2014 IceFlame.net
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
 * @version  1.1
 * @link     http://www.flamecore.org
 * @license  ISC License <http://opensource.org/licenses/ISC>
 */

namespace FlameCore\Webtools;

/**
 * Simple User Agent string parser
 *
 * @author   Thibault Duplessis <thibault.duplessis at gmail dot com>
 * @author   Christian Neff <christian.neff@gmail.com>
 */
class UserAgentStringParser
{
    /**
     * Parses a user agent string.
     *
     * @param string $string The user agent string (Default: `$_SERVER['HTTP_USER_AGENT']`)
     * @return array The user agent information:
     *   - `string`:           The original user agent string
     *   - `browser_name`:     The browser name, e.g. `chrome`
     *   - `browser_version`:  The browser version, e.g. `3.6`
     *   - `browser_engine`:   The browser engine, e.g. `webkit`
     *   - `operating_system`: The operating system, e.g. `linux`
     */
    public function parse($string = null)
    {
        // use current user agent string as default
        if (!$string) {
            $string = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
        }

        // parse quickly (with medium accuracy)
        $information = $this->doParse($string);

        // run some filters to increase accuracy
        $information = $this->filterBots($information);
        $information = $this->filterBrowsers($information);
        $information = $this->filterBrowserEngines($information);
        $information = $this->filterOperatingSystems($information);

        return $information;
    }

    /**
     * Make user agent string lowercase, and replace browser aliases.
     *
     * @param string $string The dirty user agent string
     * @return string The clean user agent string
     */
    public function cleanUserAgentString($string)
    {
        // clean up the string
        $string = trim(strtolower($string));

        // replace browser names with their aliases
        $string = strtr($string, $this->getKnownBrowserAliases());

        // replace operating system names with their aliases
        $string = strtr($string, $this->getKnownOperatingSystemAliases());

        // replace engine names with their aliases
        $string = strtr($string, $this->getKnownEngineAliases());

        return $string;
    }

    /**
     * Extracts information from the user agent string.
     *
     * @param string $string The user agent string
     * @return array The user agent information
     */
    protected function doParse($string)
    {
        $userAgent = array(
            'string' => $this->cleanUserAgentString($string),
            'browser_name' => null,
            'browser_version' => null,
            'browser_engine' => null,
            'operating_system' => null
        );

        if (empty($userAgent['string'])) {
            return $userAgent;
        }

        // Build regex that matches phrases for known browsers (e.g. "Firefox/2.0" or "MSIE 6.0").
        // This only matches the major and minor version numbers (e.g. "2.0.0.6" is parsed as simply "2.0").
        $pattern = '#(' . join('|', $this->getKnownBrowsers()) . ')[/ ]+([0-9]+(?:\.[0-9]+)?)#';

        // Find all phrases (or return empty array if none found)
        if (preg_match_all($pattern, $userAgent['string'], $matches)) {
            // Since some UAs have more than one phrase (e.g Firefox has a Gecko phrase, Opera 7,8 has a MSIE phrase),
            // use the last one found (the right-most one in the UA). That's usually the most correct.
            $i = count($matches[1]) - 1;

            if (isset($matches[1][$i])) {
                $userAgent['browser_name'] = $matches[1][$i];
            }

            if (isset($matches[2][$i])) {
                $userAgent['browser_version'] = $matches[2][$i];
            }
        }

        // Find operating system
        $pattern = '#' . join('|', $this->getKnownOperatingSystems()) . '#';

        if (preg_match($pattern, $userAgent['string'], $match)) {
            if (isset($match[0])) {
                $userAgent['operating_system'] = $match[0];
            }
        }

        // Find browser engine
        $pattern = '#' . join('|', $this->getKnownEngines()) . '#';

        if (preg_match($pattern, $userAgent['string'], $match)) {
            if (isset($match[0])) {
                $userAgent['browser_engine'] = $match[0];
            }
        }

        return $userAgent;
    }

    /**
     * Gets known browsers.
     *
     * @return array
     */
    protected function getKnownBrowsers()
    {
        return array(
            'msie',
            'firefox',
            'safari',
            'webkit',
            'opera',
            'netscape',
            'konqueror',
            'gecko',
            'chrome',
            'iphone',
            'applewebkit',
            'googlebot',
            'bingbot',
            'msnbot',
            'yahoobot',
            'facebookbot'
        );
    }

    /**
     * Gets known browser aliases.
     *
     * @return array
     */
    protected function getKnownBrowserAliases()
    {
        return array(
            'shiretoko'           => 'firefox',
            'namoroka'            => 'firefox',
            'shredder'            => 'firefox',
            'minefield'           => 'firefox',
            'granparadiso'        => 'firefox',
            'iceweasel'           => 'firefox',
            'facebookexternalhit' => 'facebookbot'
        );
    }

    /**
     * Gets known operating systems.
     *
     * @return array
     */
    protected function getKnownOperatingSystems()
    {
        return array(
            'Windows 8',
            'Windows 7',
            'Windows Vista',
            'Windows Server 2003/XP x64',
            'Windows XP',
            'Windows XP',
            'Windows 2000',
            'Windows ME',
            'Windows 98',
            'Windows 95',
            'Windows 3.11',
            'Mac OS X',
            'Mac OS 9',
            'Macintosh',
            'Ubuntu',
            'iPhone',
            'iPod',
            'iPad',
            'Android',
            'BlackBerry',
            'Mobile',
            'Linux'
        );
    }

    /**
     * Gets known operating system aliases.
     *
     * @return array
     */
    protected function getKnownOperatingSystemAliases()
    {
        return array(
            'windows nt 6.2' => 'Windows 8',
            'windows nt 6.1' => 'Windows 7',
            'windows nt 6.0' => 'Windows Vista',
            'windows nt 5.2' => 'Windows Server 2003/XP x64',
            'windows nt 5.1' => 'Windows XP',
            'windows xp'     => 'Windows XP',
            'windows nt 5.0' => 'Windows 2000',
            'windows me'     => 'Windows ME',
            'win98'          => 'Windows 98',
            'win95'          => 'Windows 95',
            'win16'          => 'Windows 3.11',
            'mac os x'       => 'Mac OS X',
            'mac_powerpc'    => 'Mac OS 9',
            'ubuntu'         => 'Ubuntu',
            'iphone'         => 'iPhone',
            'ipod'           => 'iPod',
            'ipad'           => 'iPad',
            'android'        => 'Android',
            'blackberry'     => 'BlackBerry',
            'webos'          => 'Mobile',
            'linux'          => 'Linux'
        );
    }

    /**
     * Gets known browser engines.
     *
     * @return array
     */
    protected function getKnownEngines()
    {
        return array(
            'gecko',
            'webkit',
            'trident',
            'presto'
        );
    }

    /**
     * Gets known browser engine aliases.
     *
     * @return array
     */
    protected function getKnownEngineAliases()
    {
        return array();
    }

    /**
     * Filters bots to increase accuracy.
     *
     * @param array $userAgent
     * @return array
     */
    protected function filterBots(array $userAgent)
    {
        // Yahoo bot has a special user agent string
        if ($userAgent['browser_name'] === null && strpos($userAgent['string'], 'yahoo! slurp')) {
            $userAgent['browser_name'] = 'yahoobot';
            return $userAgent;
        }

        return $userAgent;
    }

    /**
     * Filters browsers to increase accuracy.
     *
     * @param array $userAgent
     * @return array
     */
    protected function filterBrowsers(array $userAgent)
    {
        // Google chrome has a safari like signature
        if ('safari' === $userAgent['browser_name'] && strpos($userAgent['string'], 'chrome/')) {
            $userAgent['browser_name'] = 'chrome';
            $userAgent['browser_version'] = preg_replace('|.+chrome/([0-9]+(?:\.[0-9]+)?).+|', '$1', $userAgent['string']);
            return $userAgent;
        }

        // Safari version is not encoded "normally"
        if ('safari' === $userAgent['browser_name'] && strpos($userAgent['string'], ' version/')) {
            $userAgent['browser_version'] = preg_replace('|.+\sversion/([0-9]+(?:\.[0-9]+)?).+|', '$1', $userAgent['string']);
            return $userAgent;
        }

        // Opera 10.00 (and higher) version number is located at the end
        if ('opera' === $userAgent['browser_name'] && strpos($userAgent['string'], ' version/')) {
            $userAgent['browser_version'] = preg_replace('|.+\sversion/([0-9]+\.[0-9]+)\s*.*|', '$1', $userAgent['string']);
            return $userAgent;
        }

        // IE11 hasn't 'MSIE' in its user agent string
        if (empty($userAgent['browser_name']) && $userAgent['browser_engine'] == 'trident' && strpos($userAgent['string'], 'rv:')) {
            $userAgent['browser_name'] = 'msie';
            $userAgent['browser_version'] = preg_replace('|.+rv:([0-9]+(?:\.[0-9]+)+).+|', '$1', $userAgent['string']);
            return $userAgent;
        }

        return $userAgent;
    }

    /**
     * Filters browser engines to increase accuracy.
     *
     * @param array $userAgent
     * @return array
     */
    protected function filterBrowserEngines(array $userAgent)
    {
        // MSIE does not always declare its engine
        if ('msie' === $userAgent['browser_name'] && empty($userAgent['browser_engine'])) {
            $userAgent['browser_engine'] = 'trident';
            return $userAgent;
        }

        return $userAgent;
    }

    /**
     * Filters operating systems to increase accuracy.
     *
     * @param array $userAgent
     * @return array
     */
    protected function filterOperatingSystems(array $userAgent)
    {
        // Android instead of Linux
        if (strpos($userAgent['string'], 'Android ')) {
            $userAgent['operating_system'] = preg_replace('|.+(Android [0-9]+(?:\.[0-9]+)+).+|', '$1', $userAgent['string']);
            return $userAgent;
        }

        return $userAgent;
    }
}
