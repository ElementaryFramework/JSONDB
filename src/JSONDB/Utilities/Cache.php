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

use ElementaryFramework\JSONDB\Data\Database;

/**
 * Class Cache
 *
 * @package  JSONDB
 * @category Utilities
 * @author   Axel Nana <ax.lnana@outlook.com>
 * @link     http://php.jsondb.na2axl.tk/docs/api/jsondb/utilities/cache
 */
class Cache
{
    /**
     * Cache array
     *
     * @var array
     */
    private static $_cache = array();

    /**
     * Gets cached data
     *
     * @param array|string $path The path to the table
     *
     * @return array|mixed
     */
    public static function get($path)
    {
        if (is_array($path)) {
            $results = array();

            foreach ($path as $id) {
                $results[] = self::get($id);
            }

            return $results;
        }

        if (!array_key_exists($path, self::$_cache)) {
            self::$_cache[$path] = Database::getTableData($path);
        }

        return self::$_cache[$path];
    }

    /**
     * Updates the cached data for a table
     *
     * @param string $path The path to the table
     * @param array|null $data The data to cache
     */
    public static function update(string $path, array $data = null)
    {
        if (null !== $data) {
            self::$_cache[$path] = $data;
        } else {
            self::$_cache[$path] = Database::getTableData($path);
        }
    }

    /**
     * Resets the cache
     */
    public static function reset()
    {
        self::$_cache = array();
    }
}