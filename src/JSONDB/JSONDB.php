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
     * @package    JSONDB
     * @author     Nana Axel
     * @copyright  Copyright (c) 2016, Centers Technologies
     * @license    http://opensource.org/licenses/MIT MIT License
     * @filesource
     */

    namespace JSONDB;

    /**
     * Class JSONDB
     *
     * @package JSONDB
     * @author  Nana Axel
     */
    class JSONDB
    {
        /**
         * Parse value to string for
         * prepared queries.
         * @const integer
         */
        const PARAM_STRING = 0;

        /**
         * Parse value to integer for
         * prepared queries.
         * @const integer
         */
        const PARAM_INT = 1;

        /**
         * Parse value to boolean for
         * prepared queries.
         * @const integer
         */
        const PARAM_BOOL = 2;

        /**
         * Parse value to null for
         * prepared queries.
         * @const integer
         */
        const PARAM_NULL = 3;

        /**
         * Parse value to array for
         * prepared queries.
         * @const integer
         */
        const PARAM_ARRAY = 7;

        /**
         * Define if we fetch results as arrays
         * @const int
         */
        const FETCH_ARRAY = 4;

        /**
         * Define if we fetch results as objects
         * @const int
         */
        const FETCH_OBJECT = 5;

        /**
         * Define if we fetch results with class mapping
         * @const int
         */
        const FETCH_CLASS = 6;

        /**
         * Current JSONDB instance
         */
        private static $instance;

        /**
         * JSONDB __constructor
         */
        public function __construct() {
            self::$instance = $this;
        }

        /**
         * Gets the current JSONDB instance
         * @return JSONDB
         */
        public static function &getInstance() {
            return self::$instance;
        }

        /**
         * Creates a new server.
         * @param string $name The server's path
         * @param string $username The server's username
         * @param string $password The server's user password
         * @param bool $connect If JSONDB connects directly to the server after creation
         * @return JSONDB|Database
         * @throws Exception
         */
        public function createServer($name, $username, $password, $connect = FALSE)
        {
            $path = dirname(dirname(__DIR__)) . '/servers/' . $name;
            if (isset($path, $username, $password)) {
                if (file_exists($path) || is_dir($path)) {
                    throw new Exception("JSONDB Error: Can't create server \"{$path}\", the directory already exists.");
                }

                if (!@mkdir($path, 0777, TRUE) && !is_dir($path)) {
                    throw new Exception("JSONDB Error: Can't create the server \"{$path}\". Maybe you don't have write access.");
                }

                chmod($path, 0777);

                $config = new Configuration();
                $config->addUser($name, $username, $password);

                if ($connect) {
                    return $this->connect($name, $username, $password);
                }
            }

            return $this;
        }

        /**
         * Connects to a database.
         *
         * Access to a database with an username, a password,
         * and optionally a path to the database.
         *
         * @param string $server The path to the server
         * @param string $username The username used to login
         * @param string $password The password used to login
         * @param string $database The name of the database
         * @throws Exception
         * @return Database
         */
        public function connect($server, $username, $password, $database = NULL)
        {
            return new Database($server, $username, $password, $database);
        }

        /**
         * Escapes reserved characters and quotes a value
         * @param string $value
         * @link QueryParser::quote
         * @return string
         */
        public static function quote($value)
        {
            return QueryParser::quote($value);
        }

    }
