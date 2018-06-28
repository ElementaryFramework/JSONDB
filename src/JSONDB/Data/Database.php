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

namespace ElementaryFramework\JSONDB\Data;

use ElementaryFramework\JSONDB\Exceptions\AuthenticationException;
use ElementaryFramework\JSONDB\Exceptions\DatabaseException;
use ElementaryFramework\JSONDB\JSONDB;
use ElementaryFramework\JSONDB\JSONDBConfig;
use ElementaryFramework\JSONDB\Query\PreparedQueryStatement;
use ElementaryFramework\JSONDB\Query\Query;
use ElementaryFramework\JSONDB\Utilities\Benchmark;
use ElementaryFramework\JSONDB\Utilities\Configuration;
use ElementaryFramework\JSONDB\Utilities\Util;

/**
 * Class Database
 *
 * @package  JSONDB
 * @category Data
 * @author   Axel Nana <ax.lnana@outlook.com>
 * @link     http://php.jsondb.na2axl.tk/docs/api/jsondb/Data/Database
 */
class Database
{
    /**
     * The name of the server.
     *
     * @var string
     */
    private $_serverName;

    /**
     * The name of the database.
     *
     * @var string
     */
    private $_databaseName;

    /**
     * The current logged in username
     *
     * @var string
     */
    private $_username = '';

    /**
     * Database __constructor
     *
     * @param string $server
     * @param string $username
     * @param string $password
     * @param string|null $database
     *
     * @throws AuthenticationException
     * @throws DatabaseException
     */
    public function __construct(string $server, string $username, string $password, string $database = null)
    {
        $userFound = false;

        Benchmark::mark('Database_(connect)_start');
        {
            $config = Configuration::getConfig('users');

            if (!array_key_exists($server, $config)) {
                Benchmark::mark('Database_(connect)_end');
                throw new AuthenticationException("JSONDB Error: There is no registered server with the name \"{$server}\".");
            }

            foreach ($config as $user) {
                $userFound = $user["username"] === Util::crypt($username) && $user["password"] === Util::crypt($password);
                if ($userFound) break;
            }

            if (!$userFound) {
                Benchmark::mark('Database_(connect)_end');
                throw new AuthenticationException("JSONDB Error: User's authentication failed for user \"{$username}\" on server \"{$server}\". Access denied.");
            }

            $this->_serverName = Util::makePath(JSONDB::getConfigValue(JSONDBConfig::CONFIG_STORAGE_PATH), "servers", $server);
            $this->_username = $username;

            if (null !== $database) {
                try {
                    $this->setDatabase($database);
                } catch (AuthenticationException|DatabaseException $e) {
                    Benchmark::mark('Database_(connect)_end');
                    throw $e;
                }
            }
        }
        Benchmark::mark('Database_(connect)_end');
    }

    /**
     * The username of the current connected client.
     *
     * @return string
     */
    public function getUsername(): string
    {
        return $this->_username;
    }

    /**
     * Returns the path to the current working server.
     *
     * @return string
     */
    public function getServer(): string
    {
        return $this->_serverName;
    }

    /**
     * Returns the name of the currently working database.
     *
     * @return string
     */
    public function getDatabase(): string
    {
        return $this->_databaseName;
    }

    /**
     * Checks if the user is connected.
     *
     * @return bool
     */
    public function isConnected(): bool
    {
        return isset($this->_serverName) && strlen($this->_serverName) > 0;
    }

    /**
     * Checks if a database is currently used.
     *
     * @return bool
     */
    public function isWorkingDatabase(): bool
    {
        return isset($this->_databaseName) && strlen($this->_databaseName) > 0;
    }

    /**
     * Gets the list of databases in a server.
     *
     * @param string $server
     *
     * @return string[]
     */
    public static function getDatabasesList(string $server): array
    {
        return file_exists($path = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . "servers" . DIRECTORY_SEPARATOR . $server) ? scandir($path) : array();
    }

    /**
     * Returns the path to a table
     *
     * @param string $server
     * @param string $database
     * @param string $table
     *
     * @return string
     */
    public static function getTablePath(string $server, string $database, string $table): string
    {
        return $server . DIRECTORY_SEPARATOR . $database . DIRECTORY_SEPARATOR . "{$table}.jdbt";
    }

    /**
     * Returns a table's data
     *
     * @param string $path
     *
     * @return array
     */
    public static function getTableData(string $path): array
    {
        return json_decode(file_get_contents($path), true);
    }

    /**
     * Returns the path to a database
     *
     * @param string $server
     * @param string $database
     *
     * @return string
     */
    public static function getDatabasePath(string $server, string $database): string
    {
        return $server . DIRECTORY_SEPARATOR . $database;
    }

    /**
     * Disconnects from a server
     */
    public function disconnect()
    {
        Benchmark::mark('Database_(disconnect)_start');
        {
            $this->_serverName = null;
            $this->_databaseName = null;
            $this->_username = '';
        }
        Benchmark::mark('Database_(disconnect)_end');
    }

    /**
     * Change the currently used database
     *
     * @param string $database The database's name
     *
     * @throws AuthenticationException
     *
     * @return Database
     * @throws DatabaseException
     */
    public function setDatabase(string $database): Database
    {
        if (!$this->isConnected()) {
            throw new AuthenticationException("JSONDB Error: Can't use the database \"{$database}\", there is no connection established with a server.");
        }

        if (!$this->exists($database)) {
            throw new DatabaseException("JSONDB Error: Can't use the database \"{$database}\", the database doesn't exist in the server.");
        }

        $this->_databaseName = $database;

        return $this;
    }

    /**
     * Gets the list of tables in the current working database.
     *
     * @return string[]
     */
    public function getTableList(): array
    {
        return $this->isWorkingDatabase() ? scandir(self::getDatabasePath($this->_serverName, $this->_databaseName)) : array();
    }

    /**
     * Checks if a database exist in the current working server.
     *
     * @param string $database The database's name
     *
     * @return bool
     */
    public function exists(string $database): bool
    {
        return file_exists(self::getDatabasePath($this->_serverName, $database));
    }

    /**
     * Checks if a table exists in the current working database.
     *
     * @param string $name
     *
     * @return bool
     */
    public function tableExists(string $name): bool
    {
        return $this->isWorkingDatabase() && file_exists(self::getTablePath($this->_serverName, $this->_databaseName, $name));
    }

    /**
     * Creates a new database
     *
     * The new database will be a folder in the
     * server directory.
     *
     * @param string $name The name of the database
     *
     * @throws DatabaseException
     *
     * @return Database
     */
    public function createDatabase(string $name): Database
    {
        Benchmark::mark('Database_(createDatabase)_start');
        {
            if (!$this->isConnected()) {
                Benchmark::mark('Database_(createDatabase)_end');
                throw new DatabaseException("JSONDB Error: Can't create the database \"{$name}\", there is no connection established with a server.");
            }

            $path = self::getDatabasePath($this->_serverName, $name);

            if (file_exists($path)) {
                Benchmark::mark('Database_(createDatabase)_end');
                throw new DatabaseException("JSONDB Error: Can't create the database \"{$name}\" in the server \"{$this->_serverName}\", the database already exist.");
            }

            if (!@mkdir($path, 0777, true) && !is_dir($path)) {
                Benchmark::mark('Database_(createDatabase)_end');
                throw new DatabaseException("JSONDB Error: Can't create the database \"{$name}\" in the server \"{$this->_serverName}\".");
            } else {
                chmod($path, 0777);
            }
        }
        Benchmark::mark('Database_(createDatabase)_end');

        return $this;
    }

    /**
     * Creates a new table in the current database
     *
     * The new table will be a .json file in the folder
     * which represent the current selected database.
     *
     * @param string $name The name of the table
     * @param array $prototype The prototype of the table.
     *                          An array of string which
     *                          represents field names.
     *
     * @throws DatabaseException
     *
     * @return Database
     */
    public function createTable($name, array $prototype)
    {
        Benchmark::mark('Database_(createTable)_start');
        {
            if (!$this->isWorkingDatabase()) {
                Benchmark::mark('Database_(createTable)_end');
                throw new DatabaseException('JSONDB Error: Trying to create a table without using a database.');
            }

            $path = self::getTablePath($this->_serverName, $this->_databaseName, $name);

            if (file_exists($path)) {
                Benchmark::mark('jsondb_(createTable)_end');
                throw new DatabaseException("JSONDB Error: Can't create the table \"{$name}\" in the database \"{$this->_databaseName}\". The table already exist.");
            }

            $fields = array();
            $properties = array(
                'last_insert_id' => 0,
                'last_valid_row_id' => 0,
                'last_link_id' => 0,
                'primary_keys' => array(),
                'unique_keys' => array()
            );
            $aiExist = false;

            foreach ($prototype as $field => &$prop) {
                $hasAi = array_key_exists('auto_increment', $prop);
                $hasPk = array_key_exists('primary_key', $prop);
                $hasUk = array_key_exists('unique_key', $prop);
                $hasTp = array_key_exists('type', $prop);

                if ($aiExist && $hasAi) {
                    Benchmark::mark('Database_(createTable)_end');
                    throw new DatabaseException("JSONDB Error: Can't use the \"auto_increment\" property on more than one field.");
                }

                if (!$aiExist && $hasAi) {
                    $aiExist = true;
                    $prototype[$field]['unique_key'] = true;
                    $prototype[$field]['not_null'] = true;
                    $prototype[$field]['type'] = 'int';
                    $hasTp = true;
                    $hasUk = true;
                }

                if ($hasPk) {
                    $prototype[$field]['not_null'] = true;
                    array_push($properties["primary_keys"], $field);
                }

                if ($hasUk) {
                    $prototype[$field]['not_null'] = true;
                    array_push($properties["unique_keys"], $field);
                }

                if ($hasTp) {
                    $fType = $prop["type"];

                    if (null !== $fType) {
                        if (preg_match('#link\\((.+)\\)#', $fType, $link)) {
                            $linkInfo = explode('.', $link[1]);
                            $linkTablePath = self::getTablePath($this->_serverName, $this->_databaseName, $linkInfo[0]);

                            if (!file_exists($linkTablePath)) {
                                throw new DatabaseException("JSONDB Error: Can't create the table \"{$name}\"." .
                                    " An error occur when linking the column \"{$field}\" with the column \"{$linkInfo[1]}\"," .
                                    " the table \"{$linkInfo[0]}\" doesn't exist in the database \"{$this->_databaseName}\".");
                            }

                            $linkTableData = self::getTableData($linkTablePath);

                            if (!in_array($linkInfo[1], $linkTableData['prototype'], true)) {
                                throw new DatabaseException("JSONDB Error: Can't create the table \"{$name}\"." .
                                    " An error occur when linking the column \"{$field}\" with the column \"{$linkInfo[1]}\"," .
                                    " the column \"{$linkInfo[1]}\" doesn't exist in the table \"{$linkInfo[0]}\".");
                            }

                            if ((array_key_exists('primary_keys', $linkTableData['properties']) && !in_array($linkInfo[1], $linkTableData['properties']['primary_keys'], true)) || (array_key_exists('unique_keys', $linkTableData['properties']) && !in_array($linkInfo[1], $linkTableData['properties']['unique_keys'], true))) {
                                throw new DatabaseException("JSONDB Error: Can't create the table \"{$name}\"." .
                                    "An error occur when linking the column \"{$field}\" with the column \"{$linkInfo[1]}\"," .
                                    " the column \"{$linkInfo[1]}\" is neither a PRIMARY KEY nor an UNIQUE KEY of the table \"{$linkInfo[0]}\".");
                            }

                            unset($prototype[$field]["default"]);
                            unset($prototype[$field]["max_length"]);
                        }
                        else {
                            switch ($fType) {
                                case "bool":
                                case "boolean":
                                    if (array_key_exists("default", $prototype[$field]) && null !== $prototype[$field]["default"]) {
                                        $prototype[$field]["default"] = $prototype[$field]["default"] === true;
                                    }
                                    unset($prototype[$field]["max_length"]);
                                    break;

                                case "int":
                                case "integer":
                                case "number":
                                    if (array_key_exists("default", $prototype[$field]) && null !== $prototype[$field]["default"]) {
                                        $prototype[$field]["default"] = intval($prototype[$field]["default"]);
                                    }
                                    unset($prototype[$field]["max_length"]);
                                    break;

                                case "float":
                                case "decimal":
                                    if (array_key_exists("max_length", $prototype[$field]) && null !== $prototype[$field]["max_length"]) {
                                        $prototype[$field]["max_length"] = intval($prototype[$field]["max_length"]);
                                    }
                                    if (array_key_exists("default", $prototype[$field]) && null !== $prototype[$field]["default"]) {
                                        $prototype[$field]["default"] = floatval(number_format($prototype[$field]["default"], $prototype[$field]["max_length"], '.', ''));
                                    }
                                    break;

                                case "string":
                                    if (array_key_exists("max_length", $prototype[$field]) && null !== $prototype[$field]["max_length"]) {
                                        $prototype[$field]["max_length"] = intval($prototype[$field]["max_length"]);
                                    }
                                    if (array_key_exists("default", $prototype[$field]) && null !== $prototype[$field]["default"]) {
                                        $prototype[$field]["default"] = substr(strval($prototype[$field]["default"]), 0, $prototype[$field]["max_length"]);
                                    }
                                    break;

                                default:
                                    throw new DatabaseException("JSONDB Error: The type \"{$fType}\" isn't supported by JSONDB.");
                            }
                        }
                    }
                }
                else {
                    $prototype[$field]["type"] = "string";
                }

                array_push($fields, $field);
            }
            unset($prop);

            $tableProperties = array_merge($properties, $prototype);
            array_unshift($fields, '#rowid');

            $data = array(
                'prototype' => $fields,
                'properties' => $tableProperties,
                'data' => array()
            );

            if (touch($path) === false) {
                Benchmark::mark('Database_(createTable)_end');
                throw new DatabaseException("JSONDB Error: Can't create file \"{$path}\".");
            }

            chmod($path, 0777);
            file_put_contents($path, json_encode($data));
        }
        Benchmark::mark('Database_(createTable)_end');

        return $this;
    }

    /**
     * Sends a JSONDB query.
     *
     * @param string $query The query.
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function query(string $query)
    {
        return (new Query($this))->query($query);
    }

    /**
     * Sends a prepared query.
     *
     * @param string $query The query
     *
     * @return PreparedQueryStatement
     */
    public function prepare(string $query)
    {
        return (new Query($this))->prepare($query);
    }
}