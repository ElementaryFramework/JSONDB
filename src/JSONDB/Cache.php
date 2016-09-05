<?php

    /**
     * JSONDB - JSON Database Manager
     *
     * Manage JSON files as databases with JSON Query Language (JQL)
     *
     * This content is released under the MIT License (MIT)
     *
     * Copyright (c) 2016, Centers Technologies
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
     * @package	   JSONDB
     * @author	   Nana Axel
     * @copyright  Copyright (c) 2016, Centers Technologies
     * @license	   http://opensource.org/licenses/MIT MIT License
     * @filesource
     */

    namespace JSONDB;

    /**
     * Class Cache
     * @package     JSONDB
     * @subpackage  Utilities
     * @category    Cache
     * @author      Nana Axel
     */
    class Cache
    {
        /**
         * JSONDB class instance
         * @var JSONDB
         */
        private $database;

        /**
         * Cache array
         * @var array
         */
        private static $cache = array();

        /**
         * Cache __constructor.
         *
         * @param Database $db Database instance to use with Cache
         * @return Cache
         */
        public function __construct(Database $db)
        {
            $this->setDatabase($db);
        }

        /**
         * Changes the Database instance used
         *
         * @param Database $database Database class' instance
         * @return Cache
         */
        public function setDatabase(Database $database)
        {
            $this->database = $database;
            $this->reset();
            return $this;
        }

        /**
         * Gets cached data
         * @param array|string $path The path to the table
         * @return array|mixed
         */
        public function get($path)
        {
            if (is_array($path)) {
                $results = array();
                foreach ($path as $id) {
                    $results[] = $this->get($id);
                }
                return $results;
            }

            if (!array_key_exists($path, self::$cache)) {
                self::$cache[$path] = $this->database->getTableData($path);
            }

            return self::$cache[$path];
        }

        /**
         * Updates the cached data for a table
         * @param string $path The path to the table
         * @param array|null $data The data to cache
         * @return array
         */
        public function update($path, $data = NULL)
        {
            if (NULL !== $data) {
                self::$cache[$path] = $data;
            } else {
                self::$cache[$path] = $this->database->getTableData($path);
            }
        }

        /**
         * Resets the cache
         * @return Cache
         */
        public function reset()
        {
            self::$cache = array();
            return $this;
        }
    }