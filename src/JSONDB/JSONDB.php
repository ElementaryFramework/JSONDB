<?php

    /**
     * JSONDB - JSON Database Manager
     *
     * Manage local databases with JSON files and JSON Query Language (JQL)
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
         * Define if we fetch results as arrays
         * @const int
         */
        const FETCH_ARRAY = 0;

        /**
         * Define if we fetch results as objects
         * @const int
         */
        const FETCH_OBJECT = 1;

        /**
         * The current JSONDB instance
         * @var JSONDB
         */
        private static $instance;

        /**
         * The path to databases directories
         * @var string
         **/
        private $server;

        /**
         * The path to current used database
         * @var string
         */
        private $database;

        /**
         * The path to current used table
         * @var string
         */
        private $table;

        /**
         * The current logged in username
         * @var string
         */
        private $username = '';

        /**
         * The current logged in user password
         * @var string
         */
        private $password = '';

        /**
         * The query is prepared ?
         * @var bool
         */
        private $queryIsPrepared = FALSE;

        /**
         * The prepared query is executed ?
         * @var bool
         */
        private $queryIsExecuted = FALSE;

        /**
         * The query string.
         * @var string
         */
        private $queryString;

        /**
         * The parsed query.
         * @var array
         */
        private $parsedQuery;

        /**
         * Store the result of a query.
         *
         * @var array
         */
        private $queryResults = array();

        /**
         * Cache system
         * @var Cache
         */
        private $cache;

        /**
         * Query parser
         * @var QueryParser
         */
        private $queryParser;

        /**
         * Config manager
         * @var Configuration
         */
        private $config;

        /**
         * Benchmark
         * @var Benchmark
         */
        private $benchmark;

        /**
         * JSONDB __constructor
         */
        public function __construct()
        {
            self::$instance = $this;
            $this->cache = new Cache($this);
            $this->queryParser = new QueryParser();
            $this->config = new Configuration();
            $this->benchmark = new Benchmark();
        }

        /**
         * Change the currently used database
         * @param string $database The database's name
         * @throws Exception
         * @return JSONDB
         */
        public function setDatabase($database)
        {
            if (NULL === $this->server) {
                throw new Exception("JSONDB Error: Can't use the database \"{$database}\", there is no connection established with a server.");
            }
            $this->database = $database;
            return $this;
        }

        /**
         * Change the currently used table
         * @param string $table The table's name
         * @throws Exception
         * @return JSONDB
         */
        public function setTable($table)
        {
            if (NULL === $this->database) {
                throw new Exception("JSONDB Error: Can't use the table \"{$table}\", there is no database selected.");
            }
            $this->table = $table;
            return $this;
        }

        /**
         * Gets the current query string
         * @return string
         */
        public function queryString()
        {
            return $this->queryString;
        }

        /**
         * Returns the path to the server.
         * @return string
         */
        public function getServer()
        {
            return $this->server;
        }

        /**
         * Returns the currently used database.
         * @return string
         */
        public function getDatabase()
        {
            return $this->database;
        }

        /**
         * Returns the currently used table.
         * @return string
         */
        public function getTable()
        {
            return $this->table;
        }

        /**
         * Returns the current benchmark instance.
         * @return Benchmark
         */
        public function benchmark()
        {
            return $this->benchmark;
        }

        /**
         * Returns the current JSONDB instance
         * @return JSONDB
         */
        protected static function &getInstance()
        {
            return self::$instance;
        }

        /**
         * Creates a new server.
         * @param string $path The server's path
         * @param string $username The server's username
         * @param string $password The server's user password
         * @param bool $connect If JSONDB connects directly to the server after creation
         * @return JSONDB
         * @throws Exception
         */
        public function createServer($path, $username, $password, $connect = FALSE)
        {
            $this->benchmark->mark('jsondb_(createServer)_start');
            if (isset($path, $username, $password)) {
                if (file_exists($path) || is_dir($path)) {
                    $this->benchmark->mark('jsondb_(createServer)_end');
                    throw new Exception("JSONDB Error: Can't create server \"{$path}\", the directory already exists.");
                }

                if (!@mkdir($path, 0777, TRUE) && !is_dir($path)) {
                    $this->benchmark->mark('jsondb_(createServer)_end');
                    throw new Exception("JSONDB Error: Can't create the server \"{$path}\". Maybe you don't have write access.");
                }

                chmod($path, 0777);

                $htaccess = fopen($path . '/.htaccess', 'a+');
                foreach(array('AuthType Basic', 'AuthName "JSONDB Server Access"', 'AuthUserFile "' . realpath(dirname(__DIR__) . '/config/.htpasswd') . '"', 'Require user ' . $username) as $line) {
                    fwrite($htaccess, $line . "\n");
                }
                fclose($htaccess);

                $htpasswd = fopen(realpath(dirname(__DIR__) . '/config/.htpasswd'), 'a+');
                fwrite($htpasswd, $username . ':' . crypt($password) . "\n");
                fclose($htpasswd);

                $this->config->addUser(realpath($path), $username, $password);

                if ($connect) {
                    $this->connect($path, $username, $password);
                }
            }
            $this->benchmark->mark('jsondb_(createServer)_end');

            return $this;
        }

        /**
         * Creates a new database
         *
         * The new database will be a folder in the
         * server directory.
         *
         * @param string $name The name of the database
         * @throws Exception
         * @return JSONDB
         */
        public function createDatabase($name)
        {
            $this->benchmark->mark('jsondb_(createDatabase)_start');
            if (NULL === $this->server) {
                $this->benchmark->mark('jsondb_(createDatabase)_end');
                throw new Exception("JSONDB Error: Can't create the database \"{$name}\", there is no connection established with a server.");
            }

            $path = $this->_getDatabasePath($name);

            if (file_exists($path)) {
                $this->benchmark->mark('jsondb_(createDatabase)_end');
                throw new Exception("JSONDB Error: Can't create the database \"{$name}\" in the server \"{$this->server}\", the database already exist.");
            }

            if (!@mkdir($path, 0777, TRUE) && !is_dir($path)) {
                $this->benchmark->mark('jsondb_(createDatabase)_end');
                throw new Exception("JSONDB Error: Can't create the database \"{$name}\" in the server \"{$this->server}\".");
            } else {
                chmod($path, 0777);
            }
            $this->benchmark->mark('jsondb_(createDatabase)_end');

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
         * @return JSONDB
         */
        public function connect($server, $username, $password, $database = NULL)
        {
            $this->benchmark->mark('jsondb_(connect)_start');
            $config = $this->config->getConfig('users');
            $server = realpath($server);

            if (!array_key_exists($server, $config)) {
                $this->benchmark->mark('jsondb_(connect)_end');
                throw new Exception("JSONDB Error: There is no registered server with the path \"{$server}\".");
            }

            if ($config[$server]['username'] !== Util::crypt($username) || $config[$server]['password'] !== Util::crypt($password)) {
                $this->benchmark->mark('jsondb_(connect)_end');
                throw new Exception("JSONDB Error: User's authentication failed for user \"{$username}\" on server \"{$server}\". Access denied.");
            }

            $this->server = $server;
            $this->database = $database;
            $this->username = $username;
            $this->password = $password;
            $this->benchmark->mark('jsondb_(connect)_end');

            return $this;
        }

        /**
         * Disconnects to a server
         */
        public function disconnect()
        {
            $this->benchmark->mark('jsondb_(disconnect)_start');
            $this->server = NULL;
            $this->database = NULL;
            $this->table = NULL;
            $this->username = '';
            $this->password = '';
            $this->benchmark->mark('jsondb_(disconnect)_end');
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
         * @throws Exception
         * @return JSONDB
         */
        public function createTable($name, array $prototype)
        {
            $this->benchmark->mark('jsondb_(createTable)_start');
            $table_path = $this->_getTablePath($name);
            if (file_exists($table_path)) {
                $this->benchmark->mark('jsondb_(createTable)_end');
                throw new Exception("JSONDB Error: Can't create the table \"{$name}\" in the database \"{$this->database}\". The table already exist.");
            }

            $fields = array();
            $properties = array('last_insert_id' => 0, 'last_valid_row_id' => 0, 'primary_keys' => array(), 'unique_keys' => array());
            $ai_exist = FALSE;
            foreach ($prototype as $field => $prop) {
                $has_ai = array_key_exists('auto_increment', $prop);
                $has_pk = array_key_exists('primary_key', $prop);
                $has_uk = array_key_exists('unique_key', $prop);
                $has_tp = array_key_exists('type', $prop);
                if ($ai_exist && $has_ai) {
                    $this->benchmark->mark('jsondb_(createTable)_end');
                    throw new Exception("JSONDB Error: Can't use the \"auto_increment\" property on more than one fields.");
                } elseif (!$ai_exist && $has_ai) {
                    $ai_exist = TRUE;
                    $prototype[$field]['unique_key'] = TRUE;
                    $prototype[$field]['not_null'] = TRUE;
                    $properties['unique_keys'][] = $field;
                }
                if ($has_pk) {
                    $prototype[$field]['not_null'] = TRUE;
                    $properties['primary_keys'][] = $field;
                }
                if ($has_uk) {
                    $prototype[$field]['not_null'] = TRUE;
                    $properties['unique_keys'][] = $field;
                }
                if ($has_tp) {
                    if (preg_match('#link\((.+)\)#', $prop['type'], $link)) {
                        $link_info = explode('.', $link[1]);
                        $link_table_path = $this->_getTablePath($link_info[0]);
                        if (!file_exists($link_table_path)) {
                            throw new Exception("JSONDB Error: Can't create the table \"{$name}\". An error occur when linking the column \"{$field}\" with the column \"{$link[1]}\", the table \"{$link_info[0]}\" doesn't exist in the database \"{$this->database}\".");
                        }

                        $link_table_data = $this->getTableData($link_table_path);
                        if (!in_array($link_info[1], $link_table_data['prototype'], TRUE)) {
                            throw new Exception("JSONDB Error: Can't create the table \"{$name}\". An error occur when linking the column \"{$field}\" with the column \"{$link[1]}\", the column \"{$link_info[1]}\" doesn't exist in the table \"{$link_info[0]}\".");
                        }
                        if ((array_key_exists('primary_keys', $link_table_data['properties']) && !in_array($link_info[1], $link_table_data['properties']['primary_keys'], TRUE)) || (array_key_exists('unique_keys', $link_table_data['properties']) && !in_array($link_info[1], $link_table_data['properties']['unique_keys'], TRUE))) {
                            throw new Exception("JSONDB Error: Can't create the table \"{$name}\". An error occur when linking the column \"{$field}\" with the column \"{$link[1]}\", the column \"{$link_info[1]}\" is not a PRIMARY KEY or an UNIQUE KEY of the table \"{$link_info[0]}\".");
                        }
                    }
                } else {
                    $prototype[$field]['type'] = 'string';
                }
                $fields[] = $field;
            }
            $properties = array_merge($properties, $prototype);
            array_unshift($fields, '#rowid');
            $data = array(
                'prototype' => $fields,
                'properties' => $properties,
                'data' => array()
            );
            if (touch($table_path) === FALSE) {
                $this->benchmark->mark('jsondb_(createTable)_end');
                throw new Exception("JSONDB Error: Can't create file \"{$table_path}\".");
            }
            chmod($table_path, 0777);
            file_put_contents($table_path, json_encode($data));
            $this->benchmark->mark('jsondb_(createTable)_end');

            return $this;
        }

        /**
         * Sends a JSONDB query.
         * @param string $query The query.
         * @throws Exception
         * @return mixed
         */
        public function query($query)
        {
            try {
                $this->parsedQuery = $this->queryParser->parse($query);
            } catch (Exception $e) {
                throw $e;
            }

            $this->queryString = $query;
            $this->queryIsExecuted = FALSE;
            $this->queryIsPrepared = FALSE;

            return $this->execute();
        }

        /**
         * Sends a prepared query.
         * @param string $query The query
         * @return PreparedQueryStatement
         */
        public function prepare($query)
        {
            $this->queryString = $query;
            $this->queryIsPrepared = TRUE;

            return new PreparedQueryStatement($query, $this);
        }

        /**
         * Escapes reserved characters and quotes a value
         * @param string $value
         * @return string
         */
        public static function quote($value)
        {
            return QueryParser::quote($value);
        }

        /**
         * Executes a query
         * @return mixed
         * @throws Exception
         */
        private function execute()
        {
            if (!$this->queryIsExecuted()) {
                if (NULL === $this->database || NULL === $this->parsedQuery) {
                    throw new Exception("JSONDB Error: Can't execute the query. No database/table selected or internal error.");
                }

                $this->setTable($this->parsedQuery['table']);
                $table_path = $this->_getTablePath();
                if (!file_exists($table_path) || !is_readable($table_path) || !is_writable($table_path)) {
                    throw new Exception("JSONDB Error: Can't execute the query. The table \"{$this->table}\" doesn't exists in database \"{$this->database}\" or file access denied.");
                }

                $json_array = $this->cache->get($table_path);
                $method = "_{$this->parsedQuery['action']}";

                $this->benchmark->mark('jsondb_(query)_start');
                $return = $this->$method($json_array);
                $this->benchmark->mark('jsondb_(query)_end');

                $this->queryIsExecuted = TRUE;

                return $return;
            } else {
                throw new Exception('JSONDB Error: There is no query to execute, or the query is already executed.');
            }
        }

        /**
         * Returns a table's data
         * @param string|null $path The path to the table
         * @return array
         */
        public function getTableData($path = NULL)
        {
            return json_decode(file_get_contents(NULL !== $path ? $path : $this->_getTablePath()), TRUE);
        }

        /**
         * Checks if the query is prepared.
         * @return bool
         */
        public function queryIsPrepared()
        {
            return $this->queryIsPrepared === TRUE;
        }

        /**
         * Checks if the query is executed.
         * @return bool
         */
        public function queryIsExecuted()
        {
            return $this->queryIsExecuted === TRUE;
        }

        /**
         * @param $data
         * @param bool $min
         * @return int|mixed
         */
        private function _getLastValidRowID($data, $min = TRUE)
        {
            $last_valid_row_id = 0;
            foreach ((array)$data as $line) {
                if ($last_valid_row_id === 0) {
                    $last_valid_row_id = $line['#rowid'];
                } else {
                    $last_valid_row_id = (TRUE === $min) ? min($last_valid_row_id, $line['#rowid']) : max($last_valid_row_id, $line['#rowid']);
                }
            }
            return $last_valid_row_id;
        }

        /**
         * Gets the path to a database
         *
         * @param string $database
         * @return string
         */
        protected function _getDatabasePath($database = NULL)
        {
            return (NULL !== $database) ? "{$this->server}/{$database}" : "{$this->server}/{$this->database}";
        }

        /**
         * Returns the path to a table
         * @param null|string $table The table name
         * @return string
         */
        protected function _getTablePath($table = NULL)
        {
            return (NULL !== $table) ? "{$this->server}/{$this->database}/{$table}.json" : "{$this->server}/{$this->database}/{$this->table}.json";
        }

        /**
         * Gets a table's content
         * @param null|string $table The table's name
         * @return array
         */
        protected function _getTableContent($table = NULL)
        {
            $filename = $this->_getTablePath($this->table);

            if (NULL !== $table) {
                $filename = $this->_getTablePath($table);
            }

            return json_decode(file_get_contents($filename), TRUE);
        }

        /**
         * @param $value
         * @param $properties
         * @return float|int|string
         * @throws Exception
         */
        protected function _parseValue($value, $properties)
        {
            if (NULL !== $value || (array_key_exists('not_null', $properties) && TRUE === $properties['not_null'])) {
                if (array_key_exists('type', $properties)) {
                    if (preg_match('#link\((.+)\)#', $properties['type'], $link)) {
                        $link_info = explode('.', $link[1]);
                        $link_table_path = $this->_getTablePath($link_info[0]);
                        $link_table_data = $this->getTableData($link_table_path);
                        $value = $this->_parseValue($value, $link_table_data['properties'][$link_info[1]]);
                        foreach ((array)$link_table_data['data'] as $data) {
                            if ($data[$link_info[1]] === $value) {
                                return $data['#rowid'];
                            }
                        }
                    } else {
                        switch ($properties['type']) {
                            case 'int':
                            case 'integer':
                            case 'number':
                                $value = (int)$value;
                                break;

                            case 'decimal':
                            case 'float':
                                $value = (float)$value;
                                if (array_key_exists('max_length', $properties)) {
                                    $value = number_format($value, $properties['max_length']);
                                }
                                break;

                            case 'string':
                                $value = (string)$value;
                                if (array_key_exists('max_length', $properties) && strlen($value) > 0) {
                                    $value = substr($value, 0, $properties['max_length']);
                                }
                                break;

                            case 'char':
                                $value = (string)$value[0];
                                break;

                            case 'bool':
                            case 'boolean':
                                $value = (bool)$value;
                                break;

                            case 'array':
                                $value = (array)$value;
                                break;

                            default:
                                throw new Exception("JSONDB Error: Trying to parse a value with an unsupported type \"{$properties['type']}\"");
                        }
                    }
                }
            } elseif (array_key_exists('default', $properties)) {
                $value = $this->_parseValue($properties['default'], $properties);
            }
            return $value;
        }

        /**
         * The select() query
         * @param array $data
         * @return QueryResult
         * @throws Exception
         */
        protected function _select($data)
        {
            $result = $data['data'];

            foreach ((array)$this->parsedQuery['extensions'] as $name => $parameters) {
                switch ($name) {
                    case 'order':
                        list($order_by, $order_method) = $parameters;
                        usort($result, function ($after, $now) use ($order_method, $order_by) {
                            if ($order_method === 'desc') {
                                return $now[$order_by] > $after[$order_by];
                            } else {
                                return $now[$order_by] < $after[$order_by];
                            }
                        });
                        break;

                    case 'where':
                        if (count($parameters) > 0) {
                            $out = array();
                            foreach ((array)$parameters as $filters) {
                                $out += $this->_filter($result, $filters);
                            }
                            $result = $out;
                        }
                        break;

                    case 'limit':
                        $result = array_slice($result, $parameters[0], $parameters[1]);
                        break;
                }
            }

            $temp = array();
            if (in_array('last_insert_id', $this->parsedQuery['parameters'], TRUE)) {
                $temp['last_insert_id'] = $data['properties']['last_insert_id'];
            } elseif (!in_array('*', $this->parsedQuery['parameters'], TRUE)) {
                foreach ((array)$result as $line) {
                    $temp[] = array_intersect_key($line, array_flip($this->parsedQuery['parameters']));
                }
                if (array_key_exists('as', $this->parsedQuery['extensions'])) {
                    for ($i = 0, $max = count($this->parsedQuery['parameters']) - count($this->parsedQuery['extensions']['as']); $i < $max; ++$i) {
                        $this->parsedQuery['extensions']['as'][] = 'null';
                    }
                    $replace = array_combine($this->parsedQuery['parameters'], $this->parsedQuery['extensions']['as']);
                    foreach ($temp as &$t) {
                        foreach ($replace as $old => $new) {
                            if (strtolower($new) === 'null') {
                                continue;
                            }
                            $t[$new] = $t[$old];
                            unset($t[$old]);
                        }
                    }
                    unset($t);
                }
            } else {
                foreach ((array)$result as $line) {
                    $temp[] = array_diff_key($line, array('#rowid' => '#rowid'));
                }
            }
            $result = $temp;

            $this->queryResults = $result;

            return new QueryResult($this->queryResults, $this);
        }

        /**
         * The insert() query
         * @param array $data
         * @return bool
         * @throws Exception
         */
        protected function _insert($data)
        {
            $rows = array_values(array_diff($data['prototype'], array('#rowid' => '#rowid')));
            if (array_key_exists('in', $this->parsedQuery['extensions'])) {
                $rows = $this->parsedQuery['extensions']['in'];
                foreach ((array)$rows as $row) {
                    if (!in_array($row, $data['prototype'], FALSE)) {
                        throw new Exception("JSONDB Error: Can't insert data in the table \"{$this->table}\". The column \"{$row}\" doesn't exist.");
                    }
                }
            }

            $values_nb = count($this->parsedQuery['parameters']);
            $rows_nb = count($rows);
            if ($values_nb !== $rows_nb) {
                throw new Exception("JSONDB Error: Can't insert data in the table \"{$this->table}\". Invalid number of parameters (given \"{$values_nb}\" values to insert in \"{$rows_nb}\" columns).");
            }
            $current_data = $data['data'];
            $ai_id = (int)$data['properties']['last_insert_id'];
            $insert = array(array('#rowid' => (int)$data['properties']['last_valid_row_id'] + 1));
            foreach ((array)$this->parsedQuery['parameters'] as $key => $value) {
                $insert[0][$rows[$key]] = $this->_parseValue($value, $data['properties'][$rows[$key]]);
            }

            if (array_key_exists('and', $this->parsedQuery['extensions'])) {
                foreach ((array)$this->parsedQuery['extensions']['and'] as $values) {
                    $values_nb = count($values);
                    if ($values_nb !== $rows_nb) {
                        throw new Exception("JSONDB Error: Can't insert data in the table \"{$this->table}\". Invalid number of parameters (given \"{$values_nb}\" values to insert in \"{$rows_nb}\" columns).");
                    }
                    $to_add = array('#rowid' => $this->_getLastValidRowID(array_merge($current_data, $insert), FALSE) + 1);
                    foreach ((array)$values as $key => $value) {
                        $to_add[$rows[$key]] = $this->_parseValue($value, $data['properties'][$rows[$key]]);
                    }
                    $insert[] = $to_add;
                }
            }

            foreach ((array)$data['properties'] as $field => $property) {
                if (is_array($property) && array_key_exists('auto_increment', $property) && $property['auto_increment']) {
                    foreach ($insert as &$array_insert) {
                        if (!empty($array_insert[$field])) {
                            continue;
                        }
                        $array_insert[$field] = ++$ai_id;
                    }
                    unset($array_insert);
                    break;
                }
            }

            foreach ((array)$data['prototype'] as $column) {
                foreach ($insert as $key => &$item) {
                    if (!array_key_exists($column, $insert[$key])) {
                        $item[$column] = $this->_parseValue(null, $data['properties'][$column]);
                    }
                }
            }
            unset($item);

            $insert = array_merge($current_data, $insert);

            $pk_error = FALSE;
            $non_pk = array_flip(array_diff($data['prototype'], $data['properties']['primary_keys']));
            foreach ($insert as $index => $array_data) {
                $array_data = array_diff_key($array_data, $non_pk);
                foreach (array_slice($insert, $index + 1) as $value) {
                    $value = array_diff_key($value, $non_pk);
                    $pk_error = $pk_error || ($value === $array_data);
                    if ($pk_error) {
                        $values = implode(', ', $value);
                        $keys = implode(', ', $data['properties']['primary_keys']);
                        throw new Exception("JSONDB Error: Can't insert value. Duplicate values \"{$values}\" for primary keys \"{$keys}\".");
                    }
                }
            }

            $uk_error = FALSE;
            foreach ((array)$data['properties']['unique_keys'] as $uk) {
                foreach ($insert as $index => $array_data) {
                    $array_data = array_intersect_key($array_data, array($uk => $uk));
                    foreach (array_slice($insert, $index + 1) as $value) {
                        $value = array_intersect_key($value, array($uk => $uk));
                        $uk_error = $uk_error || (!empty($item[$uk]) && ($value === $array_data));
                        if ($uk_error) {
                            throw new Exception("JSONDB Error: Can't insert value. Duplicate values \"{$value[$uk]}\" for unique key \"{$uk}\".");
                        }
                    }
                }
            }

            foreach ($insert as $key => &$line) {
                uksort($line, function ($after, $now) use ($data) {
                    return array_search($now, $data['prototype'], TRUE) < array_search($after, $data['prototype'], TRUE);
                });
            }
            unset($line);

            usort($insert, function ($after, $now) {
                return $now['#rowid'] < $after['#rowid'];
            });

            $data['data'] = $insert;
            $data['properties']['last_valid_row_id'] = $this->_getLastValidRowID($insert, FALSE);
            $data['properties']['last_insert_id'] = $ai_id;

            $this->cache->update($this->_getTablePath(), $data);

            return (bool)file_put_contents($this->_getTablePath(), json_encode($data));
        }

        /**
         * The replace() query
         * @param array $data
         * @return bool
         * @throws Exception
         */
        protected function _replace($data)
        {
            $rows = array_values(array_diff($data['prototype'], array('#rowid' => '#rowid')));
            if (isset($this->parsedQuery['extensions']['in'])) {
                $rows = $this->parsedQuery['extensions']['in'];
                foreach ((array)$rows as $row) {
                    if (!in_array($row, $data['prototype'], FALSE)) {
                        throw new Exception("JSONDB Error: Can't insert data in the table \"{$this->table}\". The column \"{$row}\" doesn't exist.");
                    }
                }
            }

            $values_nb = count($this->parsedQuery['parameters']);
            $rows_nb = count($rows);
            if ($values_nb !== $rows_nb) {
                throw new Exception("JSONDB Error: Can't insert data in the table \"{$this->table}\". Invalid number of parameters (given \"{$values_nb}\" values to insert in \"{$rows_nb}\" columns).");
            }
            $current_data = $data['data'];
            $insert = array();
            foreach ((array)$this->parsedQuery['parameters'] as $key => $value) {
                if (NULL === $value && array_key_exists('auto_increment', $data['properties'][$rows[$key]]) && TRUE === $data['properties'][$rows[$key]]['auto_increment']) {
                    continue;
                } else {
                    $insert[0][$rows[$key]] = $this->_parseValue($value, $data['properties'][$rows[$key]]);
                }
            }

            if (array_key_exists('and', $this->parsedQuery['extensions'])) {
                foreach ((array)$this->parsedQuery['extensions']['and'] as $values) {
                    $to_add = array();
                    foreach ((array)$values as $key => $value) {
                        if (NULL === $value && array_key_exists('auto_increment', $data['properties'][$rows[$key]]) && TRUE === $data['properties'][$rows[$key]]['auto_increment']) {
                            continue;
                        } else {
                            $to_add[$rows[$key]] = $this->_parseValue($value, $data['properties'][$rows[$key]]);
                        }
                    }
                    $insert[] = $to_add;
                }
            }

            $insert = array_replace_recursive($current_data, $insert);

            $pk_error = FALSE;
            $non_pk = array_flip(array_diff($data['prototype'], $data['properties']['primary_keys']));
            foreach ((array)$insert as $index => $array_data) {
                $array_data = array_diff_key($array_data, $non_pk);
                foreach (array_slice($insert, $index + 1) as $item) {
                    $item = array_diff_key($item, $non_pk);
                    $pk_error = $pk_error || ($item === $array_data);
                    if ($pk_error) {
                        $values = implode(', ', $item);
                        $keys = implode(', ', $data['properties']['primary_keys']);
                        throw new Exception("JSONDB Error: Can't replace value. Duplicate values \"{$values}\" for primary keys \"{$keys}\".");
                    }
                }
            }

            $uk_error = FALSE;
            foreach ((array)$data['properties']['unique_keys'] as $uk) {
                foreach ($insert as $index => $array_data) {
                    $array_data = array_intersect_key($array_data, array($uk => $uk));
                    foreach (array_slice($insert, $index + 1) as $item) {
                        $item = array_intersect_key($item, array($uk => $uk));
                        $uk_error = $uk_error || (!empty($item[$uk]) && ($item === $array_data));
                        if ($uk_error) {
                            throw new Exception("JSONDB Error: Can't replace value. Duplicate values \"{$item[$uk]}\" for unique key \"{$uk}\".");
                        }
                    }
                }
            }

            foreach ((array)$insert as $key => &$line) {
                uksort($line, function ($after, $now) use ($data) {
                    return array_search($now, $data['prototype'], TRUE) < array_search($after, $data['prototype'], TRUE);
                });
            }
            unset($line);

            usort($insert, function ($after, $now) {
                return $now['#rowid'] < $after['#rowid'];
            });

            $data['data'] = $insert;

            $this->cache->update($this->_getTablePath(), $data);

            return (bool)file_put_contents($this->_getTablePath(), json_encode($data));
        }

        /**
         * The delete() query
         * @param array $data
         * @return bool
         * @throws Exception
         */
        protected function _delete($data)
        {
            $current_data = (array)$data['data'];
            $to_delete = $current_data;

            if (array_key_exists('where', $this->parsedQuery['extensions'])) {
                $out = array();
                foreach ((array)$this->parsedQuery['extensions']['where'] as $filters) {
                    $out += $this->_filter($to_delete, $filters);
                }
                $to_delete = $out;
            }

            $insert = array();
            foreach ($to_delete as $array) {
                if (in_array($array, $current_data, TRUE)) {
                    $current_data[array_search($array, $current_data, TRUE)] = NULL;
                }
            }
            foreach ($current_data as $array) {
                if (NULL !== $array) {
                    $insert[] = $array;
                }
            }

            foreach ($insert as $key => &$line) {
                uksort($line, function ($after, $now) use ($data) {
                    return array_search($now, $data['prototype'], TRUE) < array_search($after, $data['prototype'], TRUE);
                });
            }
            unset($line);

            usort($insert, function ($after, $now) {
                return $now['#rowid'] < $after['#rowid'];
            });

            $data['data'] = $insert;
            $data['properties']['last_valid_row_id'] = $this->_getLastValidRowID($to_delete) - 1;

            $this->cache->update($this->_getTablePath(), $data);

            return (bool)file_put_contents($this->_getTablePath(), json_encode($data));
        }

        /**
         * The update() query
         * @param array $data
         * @return bool
         * @throws Exception
         */
        protected function _update($data)
        {
            $result = $data['data'];

            if (array_key_exists('where', $this->parsedQuery['extensions'])) {
                $out = array();
                foreach ((array)$this->parsedQuery['extensions']['where'] as $filters) {
                    $out += $this->_filter($result, $filters);
                }
                $result = $out;
            }

            if (!array_key_exists('with', $this->parsedQuery['extensions'])) {
                throw new Exception("JSONDB Error: Can't execute the \"update()\" query without values.");
            }

            $fields_nb = count($this->parsedQuery['parameters']);
            $values_nb = count($this->parsedQuery['extensions']['with']);
            if ($fields_nb !== $values_nb) {
                throw new Exception("JSONDB Error: Can't execute the \"update()\" query. Invalid number of parameters (trying to update \"{$fields_nb}\" columns with \"{$values_nb}\" values).");
            }

            $values = array_combine($this->parsedQuery['parameters'], $this->parsedQuery['extensions']['with']);

            $pk_error = FALSE;
            $non_pk = array_flip(array_diff($data['prototype'], $data['properties']['primary_keys']));
            foreach ((array)$data['data'] as $array_data) {
                $array_data = array_diff_key($array_data, $non_pk);
                $pk_error = $pk_error || (array_diff_key($values, $non_pk) === $array_data);
                if ($pk_error) {
                    $v = implode(', ', $array_data);
                    $k = implode(', ', $data['properties']['primary_keys']);
                    throw new Exception("JSONDB Error: Can't update value. Duplicate values \"{$v}\" for primary keys \"{$k}\".");
                }
            }

            $uk_error = FALSE;
            foreach ((array)$data['properties']['unique_keys'] as $uk) {
                $item = array_intersect_key($values, array($uk => $uk));
                foreach ((array)$data['data'] as $index => $array_data) {
                    $array_data = array_intersect_key($array_data, array($uk => $uk));
                    $uk_error = $uk_error || (!empty($item[$uk]) && ($item === $array_data));
                    if ($uk_error) {
                        throw new Exception("JSONDB Error: Can't replace value. Duplicate values \"{$item[$uk]}\" for unique key \"{$uk}\".");
                    }
                }
            }

            foreach ((array)$result as $id => $res_line) {
                foreach ($values as $row => $value) {
                    $result[$id][$row] = $this->_parseValue($value, $data['properties'][$row]);
                }
                foreach ((array)$data['data'] as $key => $data_line) {
                    if ($data_line['#rowid'] === $res_line['#rowid']) {
                        $data['data'][$key] = $result[$id];
                        break;
                    }
                }
            }

            foreach ((array)$data['data'] as $key => &$line) {
                uksort($line, function ($after, $now) use ($data) {
                    return array_search($now, $data['prototype'], TRUE) < array_search($after, $data['prototype'], TRUE);
                });
            }
            unset($line);

            usort($data['data'], function ($after, $now) {
                return $now['#rowid'] < $after['#rowid'];
            });

            $this->cache->update($this->_getTablePath(), $data);

            return (bool)file_put_contents($this->_getTablePath(), json_encode($data));
        }

        /**
         * The truncate() query
         * @param array $data
         * @return bool
         */
        protected function _truncate($data)
        {
            $data['properties']['last_insert_id'] = 0;
            $data['properties']['last_valid_row_id'] = 0;
            $data['data'] = array();

            $this->cache->update($this->_getTablePath(), $data);

            return (bool)file_put_contents($this->_getTablePath($this->table), json_encode($data));
        }

        /**
         * The count() query
         * @param array $data
         * @return array
         * @throws Exception
         */
        protected function _count($data)
        {
            $rows = array_values(array_diff($data['prototype'], array('#rowid' => '#rowid')));
            if (!in_array('*', $this->parsedQuery['parameters'], TRUE)) {
                $rows = $this->parsedQuery['parameters'];
            }

            if (array_key_exists('where', $this->parsedQuery['extensions'])) {
                $out = array();
                foreach ((array)$this->parsedQuery['extensions']['where'] as $filters) {
                    $out += $this->_filter($data['data'], $filters);
                }
                $data['data'] = $out;
            }

            $result = array();
            if (array_key_exists('group', $this->parsedQuery['extensions'])) {
                $used = array();
                foreach ((array)$data['data'] as $array_data_p) {
                    $current_column = $this->parsedQuery['extensions']['group'][0];
                    $current_data = $array_data_p[$current_column];
                    $current_counter = 0;
                    if (!in_array($current_data, $used, TRUE)) {
                        foreach ((array)$data['data'] as $array_data_c) {
                            if ($array_data_c[$current_column] === $current_data) {
                                ++$current_counter;
                            }
                        }
                        if (array_key_exists('as', $this->parsedQuery['extensions'])) {
                            $result[] = array($this->parsedQuery['extensions']['as'][0] => $current_counter, $current_column => $current_data);
                        } else {
                            $result[] = array('count(' . implode(',', $this->parsedQuery['parameters']) . ')' => $current_counter, $current_column => $current_data);
                        }
                        $used[] = $current_data;
                    }
                }
            } else {
                $counter = array();
                foreach ((array)$data['data'] as $array_data) {
                    foreach ((array)$rows as $row) {
                        if (NULL !== $array_data[$row]) {
                            array_key_exists($row, $counter) ? ++$counter[$row] : $counter[$row] = 1;
                        }
                    }
                }
                $count = count($counter) > 0 ? max($counter) : 0;

                if (array_key_exists('as', $this->parsedQuery['extensions'])) {
                    $result[$this->parsedQuery['extensions']['as'][0]] = $count;
                } else {
                    $result['count(' . implode(',', $this->parsedQuery['parameters']) . ')'] = $count;
                }
            }

            return $result;
        }

        /**
         * Iterates over each values of data and test them using conditions in filters
         *
         * If a value of data don't pass each condition of filters, it will be removed.
         *
         * @param array $data The data to iterate over
         * @param array $filters Conditions used to remove data which not correspond
         * @return array
         * @throws Exception
         */
        protected function _filter($data, $filters)
        {
            $result = $data;
            $temp = array();

            foreach ((array)$filters as $filter) {
                if (strtolower($filter['value']) === 'last_insert_id') {
                    $filter['value'] = $data['properties']['last_insert_id'];
                }
                foreach ((array)$result as $line) {
                    if (!array_key_exists($filter['field'], $line)) {
                        throw new Exception("JSONDB Error: The field \"{$filter['field']}\" doesn't exists in the table \"{$this->table}\".");
                    }
                    switch ($filter['operator']) {
                        case '<':
                            if ($line[$filter['field']] < $filter['value']) {
                                $temp[$line['#rowid']] = $line;
                            }
                            break;

                        case '<=':
                            if ($line[$filter['field']] <= $filter['value']) {
                                $temp[$line['#rowid']] = $line;
                            }
                            break;

                        case '=':
                            if ($line[$filter['field']] === $filter['value']) {
                                $temp[$line['#rowid']] = $line;
                            }
                            break;

                        case '>=':
                            if ($line[$filter['field']] >= $filter['value']) {
                                $temp[$line['#rowid']] = $line;
                            }
                            break;

                        case '>':
                            if ($line[$filter['field']] > $filter['value']) {
                                $temp[$line['#rowid']] = $line;
                            }
                            break;

                        case '!=':
                        case '<>':
                            if ($line[$filter['field']] !== $filter['value']) {
                                $temp[$line['#rowid']] = $line;
                            }
                            break;

                        case '%=':
                            if (0 === ($line[$filter['field']] % $filter['value'])) {
                                $temp[$line['#rowid']] = $line;
                            }
                            break;

                        case '%!':
                            if (0 !== ($line[$filter['field']] % $filter['value'])) {
                                $temp[$line['#rowid']] = $line;
                            }
                            break;

                        default:
                            throw new Exception("JSONDB Error: The operator \"{$filter['operator']}\" is not supported. Try to use one of these operators: \"<\", \"<=\", \"=\", \">=\", \">\", \"<>\", \"!=\", \"%=\" or \"%!\".");
                    }
                }
                $result = $temp;
                $temp = array();
            }

            return (array)$result;
        }

    }
