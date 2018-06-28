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

namespace ElementaryFramework\JSONDB;

/**
 * JSON Databases Manager Configuration
 *
 * @package  JSONDB
 * @author   Axel Nana <ax.lnana@outlook.com>
 * @link     http://php.jsondb.na2axl.tk/docs/api/jsondb/JSONDBConfig
 */
class JSONDBConfig
{
    /**
     * The storage path configuration value name.
     */
    const CONFIG_STORAGE_PATH = "StoragePath";

    /**
     * The path to the folder used
     * to store servers and databases.
     *
     * @var string
     */
    private $_storagePath;

    /**
     * Gets the path to the folder used
     * to store servers and databases.
     *
     * @return string
     */
    public function getStoragePath(): string
    {
        return $this->_storagePath;
    }

    /**
     * Sets the path to the folder used
     * to store servers and databases.
     *
     * @param string $storagePath
     */
    public function setStoragePath(string $storagePath): void
    {
        $this->_storagePath = realpath($storagePath);

        if (!file_exists($path = $this->_storagePath . DIRECTORY_SEPARATOR . "servers")) {
            @mkdir($path, 0777, true);
        }

        if (!file_exists($path = $this->_storagePath . DIRECTORY_SEPARATOR . "config")) {
            @mkdir($path, 0777, true);
        }
    }
}