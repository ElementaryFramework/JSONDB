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
use ElementaryFramework\JSONDB\Exceptions\IOException;

/**
 * Class Util
 *
 * @package  JSONDB
 * @category Utilities
 * @author   Axel Nana <ax.lnana@outlook.com>
 * @link     http://php.jsondb.na2axl.tk/docs/api/jsondb/utilities/util
 */
class Util
{
    /**
     * String encryption salt
     *
     * @var string
     */
    private static $_cryptSalt = '<~>:q;axMw|S01%@yu*lfr^Q#j)OG<Z_dQOvzuTZsa^sm0K}*u9{d3A[ekV;/x[c';

    /**
     * Encrypt a string
     *
     * @param string $string
     *
     * @return string
     */
    public static function crypt(string $string): string
    {
        return hash("sha256", $string . self::$_cryptSalt);
    }

    /**
     * @param string $path
     * @param array $data
     *
     * @return bool
     *
     * @throws IOException
     */
    public static function writeTableData(string $path, array $data): bool
    {
        $handle = fopen($path, "w");

        if (flock($handle, LOCK_EX)) {
            $result = (bool)fwrite($handle, json_encode($data));
            flock($handle, LOCK_UN);
            fclose($handle);

            return $result;
        } else {
            throw new IOException("JSONDB Error: Unable to get a lock on the table file.");
        }
    }
}