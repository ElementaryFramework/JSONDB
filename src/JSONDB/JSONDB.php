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

use ElementaryFramework\JSONDB\Data\Database;
use ElementaryFramework\JSONDB\Exceptions\AuthenticationException;
use ElementaryFramework\JSONDB\Exceptions\DatabaseException;
use ElementaryFramework\JSONDB\Exceptions\ServerException;
use ElementaryFramework\JSONDB\Query\QueryParser;
use ElementaryFramework\JSONDB\Utilities\Configuration;
use ElementaryFramework\JSONDB\Utilities\Util;

/**
 * JSON Databases Manager
 *
 * @package  JSONDB
 * @author   Axel Nana <ax.lnana@outlook.com>
 * @link     http://php.jsondb.na2axl.tk/docs/api/jsondb/jsondb
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
     * The JSONDB configuration to use with all
     * instances.
     *
     * @var JSONDBConfig
     */
    private static $_configuration = null;

    /**
     * Sets the JSONDB configuration associated to all instances.
     *
     * @param JSONDBConfig $config The configuration.
     */
    public static function setConfig(JSONDBConfig $config)
    {
        self::$_configuration = $config;
    }

    /**
     * Gets the value of the given configuration's name.
     *
     * @param string $value The configuration value name.
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public static function getConfigValue(string $value)
    {
        if (self::$_configuration instanceof JSONDBConfig) {
            return self::$_configuration->{"get{$value}"}();
        } else {
            switch ($value) {
                case "StoragePath":
                    return dirname(dirname(__DIR__));

                default:
                    throw new \Exception("Invalid configuration value queried.");
            }
        }
    }

    /**
     * Escapes reserved characters and quotes a value.
     *
     * @param string $value The value to quote.
     *
     * @uses QueryParser::quote()
     *
     * @return string
     */
    public static function quote($value)
    {
        return QueryParser::quote($value);
    }

    /**
     * Creates a new server.
     *
     * @param string $name The server's path
     * @param string $username The server's username
     * @param string $password The server's user password
     * @param bool $connect If JSONDB connects directly to the server after creation
     *
     * @return JSONDB|Database
     *
     * @throws ServerException
     * @throws DatabaseException
     * @throws AuthenticationException
     */
    public function createServer(string $name, string $username, string $password, bool $connect = false)
    {
        $path = Util::makePath(self::getConfigValue(JSONDBConfig::CONFIG_STORAGE_PATH), "servers", $name);

        if (isset($path, $username, $password)) {
            if (file_exists($path) || is_dir($path)) {
                throw new ServerException("JSONDB Error: Can't create server \"{$path}\", the directory already exists.");
            }

            if (!@mkdir($path, 0777, true) && !is_dir($path)) {
                throw new ServerException("JSONDB Error: Can't create the server \"{$path}\". Maybe you don't have write access.");
            }

            chmod($path, 0777);

            Configuration::addUser($name, $username, $password);

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
     *
     * @return Database
     *
     * @throws DatabaseException
     * @throws AuthenticationException
     */
    public function connect(string $server, string $username, string $password, string $database = null): Database
    {
        return new Database($server, $username, $password, $database);
    }

    /**
     * Checks if a server exists.
     *
     * @param string $name The name of the server.
     *
     * @return bool
     */
    public function serverExists(string $name): bool
    {
        $path = Util::makePath(self::getConfigValue(JSONDBConfig::CONFIG_STORAGE_PATH), "servers", $name);
        return file_exists($path) && is_dir($path);
    }
}
