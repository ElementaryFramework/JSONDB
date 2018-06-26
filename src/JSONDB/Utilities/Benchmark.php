<?php

/**
 * JSONDB - JSON Database Manager
 *
 * Manage JSON files as databases with JSON Query Language (JQL)
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2016-2018 Aliens Group, Inc.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @category  Library
 * @package   JSONDB
 * @author    Axel Nana <ax.lnana@outlook.com>
 * @copyright 2016-2018 Aliens Group, Inc.
 * @license   MIT <https://github.com/ElementaryFramework/JSONDB/blob/master/LICENSE>
 * @version   2.0.0
 * @link      http://php.jsondb.na2axl.tk
 */

namespace ElementaryFramework\JSONDB\Utilities;

/**
 * Class Benchmark
 *
 * @package  JSONDB
 * @category Utilities
 * @author   Axel Nana <ax.lnana@outlook.com>
 * @link     http://php.jsondb.na2axl.tk/docs/api/jsondb/utilities/benchmark
 */
class Benchmark
{
    /**
     * The benchmark
     *
     * @var array
     *
     * @access private
     */
    private static $_marker = array();

    /**
     * Add a benchmark point.
     *
     * @param string $name The name of the benchmark point
     *
     * @return void
     */
    public static function mark(string $name)
    {
        self::$_marker[$name]['e'] = microtime();
        self::$_marker[$name]['m'] = memory_get_usage();
    }

    /**
     * Calculate the elapsed time between two benchmark points.
     *
     * @param  string $point1 The name of the first benchmark point
     * @param  string $point2 The name of the second benchmark point
     * @param  int $decimals
     *
     * @return string
     */
    public static function elapsed_time(string $point1 = '', string $point2 = '', int $decimals = 4): string
    {
        if ($point1 === '') {
            return '{elapsed_time}';
        }

        if (!array_key_exists($point1, self::$_marker)) {
            return '';
        }

        if (!array_key_exists($point2, self::$_marker)) {
            self::$_marker[$point2]['e'] = microtime();
            self::$_marker[$point2]['m'] = memory_get_usage();
        }

        list($sm, $ss) = explode(' ', self::$_marker[$point1]['e']);
        list($em, $es) = explode(' ', self::$_marker[$point2]['e']);

        return number_format(($em + $es) - ($sm + $ss), $decimals);
    }

    /**
     * Calculate the memory usage of a benchmark point
     *
     * @param  string $point1 The name of the first benchmark point
     * @param  string $point2 The name of the second benchmark point
     * @param  int $decimals
     *
     * @return string
     */
    public static function memory_usage(string $point1 = '', string $point2 = '', int $decimals = 4): string
    {
        if ($point1 === '') {
            return '{memory_usage}';
        }

        if (!array_key_exists($point1, self::$_marker)) {
            return '';
        }

        if (!array_key_exists($point2, self::$_marker)) {
            self::$_marker[$point2]['e'] = microtime();
            self::$_marker[$point2]['m'] = memory_get_usage();
        }

        $sm = self::$_marker[$point1]['m'];
        $em = self::$_marker[$point2]['m'];

        return number_format($em - $sm, $decimals);
    }
}
