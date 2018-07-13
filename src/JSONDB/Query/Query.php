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

namespace ElementaryFramework\JSONDB\Query;

use ElementaryFramework\JSONDB\Data\Database;
use ElementaryFramework\JSONDB\Exceptions\DatabaseException;
use ElementaryFramework\JSONDB\Exceptions\IOException;
use ElementaryFramework\JSONDB\Exceptions\QueryException;
use ElementaryFramework\JSONDB\Utilities\Benchmark;
use ElementaryFramework\JSONDB\Utilities\Cache;
use ElementaryFramework\JSONDB\Utilities\Util;

/**
 * Query
 *
 * @package  JSONDB
 * @category Query
 * @author   Axel Nana <ax.lnana@outlook.com>
 * @link     http://php.jsondb.na2axl.tk/docs/api/jsondb/Data/Database
 */
class Query
{
    /**
     * @var Database
     */
    private $_database;

    /**
     * The path to current used table
     *
     * @var string
     */
    private $_table;

    /**
     * The query is prepared ?
     *
     * @var bool
     */
    private $_isPreparedQuery = false;

    /**
     * The prepared query is executed ?
     *
     * @var bool
     */
    private $_isQueryExecuted = false;

    /**
     * The parsed query.
     *
     * @var array
     */
    private $_parsedQuery;


    /**
     * Store the result of a query.
     *
     * @var array
     */
    private $_queryResults = array();

    /**
     * Query parser
     *
     * @var QueryParser
     */
    private $_parser;

    /**
     * The table file handle used when
     * a query is being processed.
     *
     * @var resource
     */
    private $_tableFileHandle;

    /**
     * @return bool
     */
    public function isPreparedQuery(): bool
    {
        return $this->_isPreparedQuery;
    }

    /**
     * @return bool
     */
    public function isQueryExecuted(): bool
    {
        return $this->_isQueryExecuted;
    }

    public function __construct(Database &$database)
    {
        $this->_database =& $database;
        $this->_parser = new QueryParser();
    }

    public function query(string $query)
    {
        try {
            $this->_parsedQuery = $this->_parser->parse($query);
        } catch (\Exception $e) {
            throw $e;
        }

        $this->_isPreparedQuery = false;
        $this->_isQueryExecuted = false;

        return $this->_execute();
    }

    public function prepare(string $query)
    {
        $this->_isPreparedQuery = true;
        $this->_isQueryExecuted = false;

        return new PreparedQueryStatement($query, $this);
    }

    /**
     * Undocumented function
     *
     * @return QueryParser
     */
    public function getParser(): QueryParser
    {
        return $this->_parser;
    }

    public function getResults()
    {
        return $this->_isQueryExecuted ? $this->_queryResults : null;
    }

    /**
     * @return string
     */
    public function getTable(): string
    {
        return $this->_table;
    }

    /**
     * @return mixed
     * @throws QueryException
     */
    private function _execute()
    {
        if (!$this->isQueryExecuted()) {
            if (null === $this->_database || null === $this->_parsedQuery) {
                throw new QueryException("JSONDB Error: Can't execute the query. No database/table selected or internal error.");
            }

            $this->setTable($this->_parsedQuery['table']);

            $table_path = Database::getTablePath($this->_database->getServer(), $this->_database->getDatabase(), $this->_table);

            if (!file_exists($table_path) || !is_readable($table_path) || !is_writable($table_path)) {
                throw new QueryException("JSONDB Error: Can't execute the query. The table \"{$this->_table}\" doesn't exists in database \"{$this->_database->getDatabase()}\" or file access denied.");
            }

            $this->_tableFileHandle = fopen($table_path, "r+");

            try {
                Benchmark::mark('jsondb_(query)_start');
                {
                    if (flock($this->_tableFileHandle, LOCK_EX)) {
                        $this->_isQueryExecuted = true;

                        $json_array = Cache::get($table_path);
                        $method = "_{$this->_parsedQuery['action']}";

                        $return = $this->$method($json_array);

                        flock($this->_tableFileHandle, LOCK_UN);

                        fclose($this->_tableFileHandle);
                    } else {
                        Benchmark::mark('jsondb_(query)_end');
                        throw new IOException("JSONDB Error: Can't execute the query. Unable to acquire a lock on the table \"{$this->_table}\".");
                    }
                }
                Benchmark::mark('jsondb_(query)_end');

                return $return;
            } catch (QueryException $e) {
                flock($this->_tableFileHandle, LOCK_UN);
                fclose($this->_tableFileHandle);
                $this->_isQueryExecuted = false;
                throw $e;
            }
        } else {
            throw new QueryException('JSONDB Error: There is no query to execute, or the query is already executed.');
        }
    }

    /**
     * @param string $table
     *
     * @return Query
     *
     * @throws DatabaseException
     */
    public function setTable(string $table): Query
    {
        if (!$this->_database->isWorkingDatabase()) {
            throw new DatabaseException("Query Error: Can't use the table \"{$table}\", there is no query selected.");
        }

        $path = Database::getTablePath($this->_database->getServer(), $this->_database->getDatabase(), $table);

        if (!file_exists($path)) {
            throw new DatabaseException("Query Error: Can't use the table \"{$table}\", the table doesn't exist.");
        }

        $this->_table = $table;

        return $this;
    }

    /**
     * @param $value
     * @param $properties
     * @return float|int|string
     * @throws QueryException
     */
    private function _parseValue($value, array $properties)
    {
        if (null !== $value || (array_key_exists('not_null', $properties) && true === $properties['not_null'])) {
            if (array_key_exists('type', $properties)) {
                if (preg_match('#link\((.+)\)#', $properties['type'], $link)) {
                    $link_info = explode('.', $link[1]);
                    $link_table_path = Database::getTablePath($this->_database->getServer(), $this->_database->getDatabase(), $link_info[0]);
                    $link_table_data = Database::getTableData($link_table_path);
                    $value = $this->_parseValue($value, $link_table_data['properties'][$link_info[1]]);
                    foreach ($link_table_data['data'] as $linkID => $data) {
                        if ($data[$link_info[1]] === $value) {
                            return $linkID;
                        }
                    }
                    throw new QueryException("JSONDB Error: There is no value \"{$value}\" in any rows of the table \"{$link_info[0]}\" at the column \"{$link_info[1]}\".");
                } else {
                    switch ($properties['type']) {
                        case 'int':
                        case 'integer':
                        case 'number':
                            $value = intval($value);
                            break;

                        case 'decimal':
                        case 'float':
                            $value = floatval($value);
                            if (array_key_exists('max_length', $properties)) {
                                $value = floatval(number_format($value, $properties['max_length'], '.', ''));
                            }
                            break;

                        case 'string':
                            $value = strval($value);
                            if (array_key_exists('max_length', $properties) && strlen($value) > 0) {
                                $value = substr($value, 0, $properties['max_length']);
                            }
                            break;

                        case 'char':
                            $value = strval($value[0]);
                            break;

                        case 'bool':
                        case 'boolean':
                            $value = boolval($value);
                            break;

                        case 'array':
                            $value = (array)$value;
                            break;

                        default:
                            throw new QueryException("JSONDB Error: Trying to parse a value with an unsupported type \"{$properties['type']}\"");
                    }
                }
            }
        } elseif (array_key_exists('default', $properties)) {
            $value = $this->_parseValue($properties['default'], $properties);
        }

        return $value;
    }

    /**
     * @param string $func
     * @param $value
     * @return int|string
     * @throws QueryException
     */
    private function _parseFunction(string $func, $value)
    {
        switch ($func) {
            case "sha1":
                return sha1($value);

            case "md5":
                return md5($value);

            case "lowercase":
                return strtolower($value);

            case "uppercase":
                return strtoupper($value);

            case "ucfirst":
                return ucfirst($value);

            case "strlen":
                return strlen($value);

            default:
                throw new QueryException("JSONDB Error: Sorry but the function {$func}() is not implemented in JQL.");
        }
    }

    /**
     * The select() query
     * @param array $data
     * @return QueryResult
     * @throws Exception
     * @throws QueryException
     */
    private function _select($data): QueryResult
    {
        $result = $data['data'];
        $field_links = array();
        $column_links = array();

        foreach ($this->_parsedQuery['extensions'] as $name => $parameters) {
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
                        foreach ($parameters as $filters) {
                            $out += $this->_filter($result, $filters);
                        }
                        $result = $out;
                    }
                    break;

                case 'limit':
                    $result = array_slice($result, $parameters[0], $parameters[1]);
                    break;

                case 'on':
                    if (count($parameters) > 0) {
                        foreach ($parameters as $field) {
                            array_push($field_links, $field);
                        }
                    }
                    break;

                case 'link':
                    if (count($parameters) > 0) {
                        foreach ($parameters as $field) {
                            array_push($column_links, $field);
                        }
                    }
                    break;
            }
        }

        if (count($field_links) === count($column_links)) {
            $links = array_combine($field_links, $column_links);
        } else {
            throw new QueryException('JSONDB Error: Invalid numbers of links. Given "' . count($field_links) . '" columns to link but receive "' . count($column_links) . '" links');
        }

        if (count($links) > 0) {
            foreach ($result as $index => $result_p) {
                foreach ($links as $field => $columns) {
                    if (preg_match('#link\((.+)\)#', $data['properties'][$field]['type'], $link)) {
                        $linkInfo = explode('.', $link[1]);
                        $linkTablePath = Database::getTablePath($this->_database->getServer(), $this->_database->getDatabase(), $linkInfo[0]);
                        $linkTableData = Database::getTableData($linkTablePath);
                        foreach ($linkTableData['data'] as $linkID => $value) {
                            if ($linkID === $result_p[$field]) {
                                if (in_array('*', $columns, true)) {
                                    $columns = array_diff($linkTableData['prototype'], array('#rowid'));
                                }
                                $result[$index][$field] = array_intersect_key($value, array_flip($columns));
                            }
                        }
                    } else {
                        throw new QueryException("JSONDB Error: Can't link tables with the column \"{$field}\". The column is not of link type.");
                    }
                }
            }
        }

        $temp = array();
        if (in_array('last_insert_id', $this->_parsedQuery['parameters'], true)) {
            $temp['last_insert_id'] = $data['properties']['last_insert_id'];
        } elseif (!in_array('*', $this->_parsedQuery['parameters'], true)) {
            foreach ($result as $linkID => $line) {
                $res = array();
                for ($i = 0, $max = count($this->_parsedQuery["parameters"]); $i < $max; ++$i) {
                    $field = $this->_parsedQuery["parameters"][$i];
                    if (preg_match("/\w+\(.*\)/", $field, $parts)) {
                        $name = strtolower($parts[1]);
                        $param = isset($parts[2]) ? $parts[2] : false;

                        if ($param === false) {
                            throw new QueryException("JSONDB Error: Can't use the function \"{$name}\" without parameters.");
                        }
                        $res[$field] = $this->_parseFunction($name, $line[$param]);
                    } else {
                        $res[$field] = $line[$field];
                    }
                }
                array_push($temp, $res);
            }

            if (array_key_exists('as', $this->_parsedQuery['extensions'])) {
                for ($i = 0, $max = count($this->_parsedQuery['parameters']) - count($this->_parsedQuery['extensions']['as']); $i < $max; ++$i) {
                    array_push($this->_parsedQuery['extensions']['as'], 'null');
                }
                $replace = array_combine($this->_parsedQuery['parameters'], $this->_parsedQuery['extensions']['as']);
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
            foreach ($result as $linkID => $line) {
                array_push($temp, array_diff_key($line, array('#rowid' => '#rowid')));
            }
        }

        $this->_queryResults = $temp;

        return new QueryResult($this);
    }

    /**
     * The insert() query
     * @param array $data
     * @return bool
     * @throws QueryException
     * @throws \Exception
     */
    private function _insert($data): bool
    {
        $rows = array_values(array_diff($data['prototype'], array('#rowid' => '#rowid')));

        if (array_key_exists('in', $this->_parsedQuery['extensions'])) {
            $rows = $this->_parsedQuery['extensions']['in'];
            foreach ($rows as $row) {
                if (!in_array($row, $data['prototype'], false)) {
                    throw new QueryException("JSONDB Error: Can't insert data in the table \"{$this->_table}\". The column \"{$row}\" doesn't exist.");
                }
            }
        }

        $values_nb = count($this->_parsedQuery['parameters']);
        $rows_nb = count($rows);
        if ($values_nb !== $rows_nb) {
            throw new QueryException("JSONDB Error: Can't insert data in the table \"{$this->_table}\". Invalid number of parameters (given \"{$values_nb}\" values to insert in \"{$rows_nb}\" columns).");
        }

        $current_data = $data['data'];
        $ai_id = intval($data['properties']['last_insert_id']);
        $lk_id = intval($data['properties']['last_link_id']) + 1;
        $insert = array(
            "#{$lk_id}" => array(
                '#rowid' => intval($data['properties']['last_valid_row_id']) + 1
            )
        );

        foreach ($this->_parsedQuery['parameters'] as $key => $value) {
            $insert["#{$lk_id}"][$rows[$key]] = $this->_parseValue($value, $data['properties'][$rows[$key]]);
        }

        if (array_key_exists('and', $this->_parsedQuery['extensions'])) {
            foreach ($this->_parsedQuery['extensions']['and'] as $values) {
                $values_nb = count($values);

                if ($values_nb !== $rows_nb) {
                    throw new QueryException("JSONDB Error: Can't insert data in the table \"{$this->_table}\". Invalid number of parameters (given \"{$values_nb}\" values to insert in \"{$rows_nb}\" columns).");
                }

                $to_add = array('#rowid' => $this->_getLastValidRowID(array_merge($current_data, $insert), false) + 1);

                foreach ($values as $key => $value) {
                    $to_add[$rows[$key]] = $this->_parseValue($value, $data['properties'][$rows[$key]]);
                }

                $insert['#' . ++$lk_id] = $to_add;
            }
        }

        foreach ($data['properties'] as $field => $property) {
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

        $temp = array();
        foreach ($data['prototype'] as $column) {
            foreach ($insert as $key => &$item) {
                $temp[$key] = isset($temp[$key]) ? $temp[$key] : array();
                if (!array_key_exists($column, $insert[$key])) {
                    $item[$column] = $this->_parseValue(null, $data['properties'][$column]);
                }
                $temp[$key][$column] = $insert[$key][$column];
            }
        }
        unset($item);

        $insert = array_merge($current_data, $insert);

        $pk_error = false;
        $non_pk = array_flip(array_diff($data['prototype'], $data['properties']['primary_keys']));
        $i = 0;
        foreach ($insert as $array_data) {
            $array_data = array_diff_key($array_data, $non_pk);
            $slice = array_slice($insert, $i + 1);
            foreach ($slice as $value) {
                $value = array_diff_key($value, $non_pk);
                $pk_error = $pk_error || (($value === $array_data) && (count($array_data) > 0));
                if ($pk_error) {
                    $values = implode(', ', $value);
                    $keys = implode(', ', $data['properties']['primary_keys']);
                    throw new QueryException("JSONDB Error: Can't insert value. Duplicate values \"{$values}\" for primary keys \"{$keys}\".");
                }
            }
            $i++;
        }

        $uk_error = false;
        $i = 0;
        foreach ($data['properties']['unique_keys'] as $uk) {
            foreach ($insert as $array_data) {
                $array_data = array_intersect_key($array_data, array($uk => $uk));
                $slice = array_slice($insert, $i + 1);
                foreach ($slice as $value) {
                    $value = array_intersect_key($value, array($uk => $uk));
                    $uk_error = $uk_error || (!empty($value[$uk]) && ($value === $array_data));
                    if ($uk_error) {
                        throw new QueryException("JSONDB Error: Can't insert value. Duplicate values \"{$value[$uk]}\" for unique key \"{$uk}\".");
                    }
                }
                $i++;
            }
            $i = 0;
        }

        foreach ($insert as &$line) {
            uksort($line, function ($after, $now) use ($data) {
                return array_search($now, $data['prototype'], true) < array_search($after, $data['prototype'], true);
            });
        }
        unset($line);

        uksort($insert, function ($after, $now) use ($insert) {
            return $insert[$now]['#rowid'] < $insert[$after]['#rowid'];
        });

        $last_ai = 0;
        foreach ($data['properties'] as $field => $property) {
            if (is_array($property) && array_key_exists('auto_increment', $property) && true === $property['auto_increment']) {
                foreach ($insert as $lid => $array_data) {
                    $last_ai = max($insert[$lid][$field], $last_ai);
                }
                break;
            }
        }

        $data['data'] = $insert;
        $data['properties']['last_valid_row_id'] = self::_getLastValidRowID($insert, false);
        $data['properties']['last_insert_id'] = $last_ai;
        $data['properties']['last_link_id'] = $lk_id;

        Cache::update($path = Database::getTablePath($this->_database->getServer(), $this->_database->getDatabase(), $this->_table), $data);

        try {
            return Util::writeTableData($path, $data, $this->_tableFileHandle);
        } catch (IOException $e) {
            throw $e;
        }
    }

    /**
     * The replace() query
     * @param array $data
     * @return bool
     * @throws QueryException
     */
    protected function _replace($data): bool
    {
        $rows = array_values(array_diff($data['prototype'], array('#rowid' => '#rowid')));

        if (isset($this->_parsedQuery['extensions']['in'])) {
            $rows = $this->_parsedQuery['extensions']['in'];
            foreach ($rows as $row) {
                if (!in_array($row, $data['prototype'], true)) {
                    throw new QueryException("JSONDB Error: Can't replace data in the table \"{$this->_table}\". The column \"{$row}\" doesn't exist.");
                }
            }
        }

        $values_nb = count($this->_parsedQuery['parameters']);
        $rows_nb = count($rows);

        if ($values_nb !== $rows_nb) {
            throw new QueryException("JSONDB Error: Can't replace data in the table \"{$this->_table}\". Invalid number of parameters (given \"{$values_nb}\" values to insert in \"{$rows_nb}\" columns).");
        }

        $current_data = $data['data'];
        $insert = array(array());

        foreach ($this->_parsedQuery['parameters'] as $key => $value) {
            if (!(null === $value && array_key_exists('auto_increment', $data['properties'][$rows[$key]]) && true === $data['properties'][$rows[$key]]['auto_increment'])) {
                $insert[0][$rows[$key]] = $this->_parseValue($value, $data['properties'][$rows[$key]]);
            }
        }

        if (array_key_exists('and', $this->_parsedQuery['extensions'])) {
            foreach ($this->_parsedQuery['extensions']['and'] as $values) {
                $to_add = array();
                foreach ((array)$values as $key => $value) {
                    if (!(null === $value && array_key_exists('auto_increment', $data['properties'][$rows[$key]]) && true === $data['properties'][$rows[$key]]['auto_increment'])) {
                        $to_add[$rows[$key]] = $this->_parseValue($value, $data['properties'][$rows[$key]]);
                    }
                }
                $insert[] = $to_add;
            }
        }

        $i = 0;
        foreach ($current_data as $field => $array_data) {
            $current_data[$field] = array_key_exists($i, $insert) ? array_replace_recursive($array_data, $insert[$i]) : $array_data;
            $i++;
        }

        $insert = $current_data;

        $pk_error = false;
        $non_pk = array_flip(array_diff($data['prototype'], $data['properties']['primary_keys']));
        $i = 0;
        foreach ($insert as $array_data) {
            $array_data = array_diff_key($array_data, $non_pk);
            $slice = array_slice($insert, $i + 1);
            foreach ($slice as $value) {
                $value = array_diff_key($value, $non_pk);
                $pk_error = $pk_error || (($value === $array_data) && (count($array_data) > 0));
                if ($pk_error) {
                    $values = implode(', ', $value);
                    $keys = implode(', ', $data['properties']['primary_keys']);
                    throw new QueryException("JSONDB Error: Can't insert value. Duplicate values \"{$values}\" for primary keys \"{$keys}\".");
                }
            }
            $i++;
        }

        $uk_error = false;
        $i = 0;
        foreach ($data['properties']['unique_keys'] as $uk) {
            foreach ($insert as $array_data) {
                $array_data = array_intersect_key($array_data, array($uk => $uk));
                $slice = array_slice($insert, $i + 1);
                foreach ($slice as $value) {
                    $value = array_intersect_key($value, array($uk => $uk));
                    $uk_error = $uk_error || (!empty($value[$uk]) && ($value === $array_data));
                    if ($uk_error) {
                        throw new QueryException("JSONDB Error: Can't insert value. Duplicate values \"{$value[$uk]}\" for unique key \"{$uk}\".");
                    }
                }
                $i++;
            }
            $i = 0;
        }

        foreach ($insert as &$line) {
            uksort($line, function ($after, $now) use ($data) {
                return array_search($now, $data['prototype'], true) < array_search($after, $data['prototype'], true);
            });
        }
        unset($line);

        uksort($insert, function ($after, $now) use ($insert) {
            return $insert[$now]['#rowid'] < $insert[$after]['#rowid'];
        });

        $last_ai = 0;
        foreach ($data['properties'] as $field => $property) {
            if (is_array($property) && array_key_exists('auto_increment', $property) && true === $property['auto_increment']) {
                foreach ($insert as $lid => $array_data) {
                    $last_ai = max($insert[$lid][$field], $last_ai);
                }
                break;
            }
        }

        $data['data'] = $insert;
        $data['properties']['last_insert_id'] = $last_ai;

        Cache::update($path = Database::getTablePath($this->_database->getServer(), $this->_database->getDatabase(), $this->_table), $data);

        try {
            return Util::writeTableData($path, $data, $this->_tableFileHandle);
        } catch (IOException $e) {
            throw $e;
        }
    }


    /**
     * The update() query
     * @param array $data
     * @return bool
     * @throws QueryException
     */
    protected function _update($data)
    {
        $result = $data['data'];

        if (array_key_exists('where', $this->_parsedQuery['extensions'])) {
            $out = array();
            foreach ($this->_parsedQuery['extensions']['where'] as $filters) {
                $out += $this->_filter($result, $filters);
            }
            $result = $out;
        }

        if (!array_key_exists('with', $this->_parsedQuery['extensions'])) {
            throw new QueryException("JSONDB Error: Can't execute the \"update()\" query without values. The \"with()\" extension is required.");
        }

        $fields_nb = count($this->_parsedQuery['parameters']);
        $values_nb = count($this->_parsedQuery['extensions']['with']);
        if ($fields_nb !== $values_nb) {
            throw new QueryException("JSONDB Error: Can't execute the \"update()\" query. Invalid number of parameters (trying to update \"{$fields_nb}\" columns with \"{$values_nb}\" values).");
        }

        $values = array_combine($this->_parsedQuery['parameters'], $this->_parsedQuery['extensions']['with']);

        $pk_error = false;
        $non_pk = array_flip(array_diff($data['prototype'], $data['properties']['primary_keys']));
        foreach ($data['data'] as $array_data) {
            $array_data = array_diff_key($array_data, $non_pk);
            $pk_error = $pk_error || ((array_diff_key($values, $non_pk) === $array_data) && (count($array_data) > 0));
            if ($pk_error) {
                $v = implode(', ', $array_data);
                $k = implode(', ', $data['properties']['primary_keys']);
                throw new QueryException("JSONDB Error: Can't update value. Duplicate values \"{$v}\" for primary keys \"{$k}\".");
            }
        }

        $uk_error = false;
        foreach ($data['properties']['unique_keys'] as $uk) {
            $item = array_intersect_key($values, array($uk => $uk));
            foreach ($data['data'] as $index => $array_data) {
                $array_data = array_intersect_key($array_data, array($uk => $uk));
                $uk_error = $uk_error || (!empty($item[$uk]) && ($item === $array_data));
                if ($uk_error) {
                    throw new QueryException("JSONDB Error: Can't replace value. Duplicate values \"{$item[$uk]}\" for unique key \"{$uk}\".");
                }
            }
        }

        foreach ($result as $id => $res_line) {
            foreach ($values as $row => $value) {
                $result[$id][$row] = $this->_parseValue($value, $data['properties'][$row]);
            }
            foreach ($data['data'] as $key => $data_line) {
                if ($data_line['#rowid'] === $res_line['#rowid']) {
                    $data['data'][$key] = $result[$id];
                    break;
                }
            }
        }

        foreach ($data['data'] as $key => &$line) {
            uksort($line, function ($after, $now) use ($data) {
                return array_search($now, $data['prototype'], true) < array_search($after, $data['prototype'], true);
            });
        }
        unset($line);

        uksort($data['data'], function ($after, $now) use ($data) {
            return $data['data'][$now]['#rowid'] < $data['data'][$after]['#rowid'];
        });

        $last_ai = 0;
        foreach ($data['properties'] as $field => $property) {
            if (is_array($property) && array_key_exists('auto_increment', $property) && true === $property['auto_increment']) {
                foreach ($data['data'] as $lid => $array_data) {
                    $last_ai = max($data['data'][$lid][$field], $last_ai);
                }
                break;
            }
        }

        $data['properties']['last_insert_id'] = $last_ai;

        Cache::update($path = Database::getTablePath($this->_database->getServer(), $this->_database->getDatabase(), $this->_table), $data);

        try {
            return Util::writeTableData($path, $data, $this->_tableFileHandle);
        } catch (IOException $e) {
            throw $e;
        }
    }

    /**
     * The truncate() query
     * @param array $data
     * @return bool
     */
    protected function _truncate($data): bool
    {
        $data['properties']['last_insert_id'] = 0;
        $data['properties']['last_valid_row_id'] = 0;
        $data['data'] = array();

        Cache::update($path = Database::getTablePath($this->_database->getServer(), $this->_database->getDatabase(), $this->_table), $data);

        try {
            return Util::writeTableData($path, $data, $this->_tableFileHandle);
        } catch (IOException $e) {
            throw $e;
        }
    }

    /**
     * The delete() query.
     *
     * @param array $data
     *
     * @return bool
     *
     * @throws \Exception
     * @throws IOException
     */
    protected function _delete($data): bool
    {
        $current_data = $data['data'];
        $to_delete = $current_data;

        if (array_key_exists('where', $this->_parsedQuery['extensions'])) {
            $out = array();
            foreach ($this->_parsedQuery['extensions']['where'] as $filters) {
                $out += $this->_filter($to_delete, $filters);
            }
            $to_delete = $out;
        }

        foreach ($to_delete as $array) {
            if (in_array($array, $current_data, true)) {
                unset($current_data[array_search($array, $current_data, true)]);
            }
        }

        foreach ($current_data as $key => &$line) {
            uksort($line, function ($after, $now) use ($data) {
                return array_search($now, $data['prototype'], true) < array_search($after, $data['prototype'], true);
            });
        }
        unset($line);

        uksort($current_data, function ($after, $now) use ($current_data) {
            return $current_data[$now]['#rowid'] < $current_data[$after]['#rowid'];
        });

        $data['data'] = $current_data;
        (count($to_delete) > 0) ? $data['properties']['last_valid_row_id'] = $this->_getLastValidRowID($to_delete) - 1 : null;

        Cache::update($path = Database::getTablePath($this->_database->getServer(), $this->_database->getDatabase(), $this->_table), $data);

        try {
            return Util::writeTableData($path, $data, $this->_tableFileHandle);
        } catch (IOException $e) {
            throw $e;
        }
    }

    /**
     * The count() query
     * @param array $data
     * @return QueryResult
     */
    protected function _count($data): QueryResult
    {
        $rows = array_values(array_diff($data['prototype'], array('#rowid' => '#rowid')));
        if (!in_array('*', $this->_parsedQuery['parameters'], true)) {
            $rows = $this->_parsedQuery['parameters'];
        }

        if (array_key_exists('where', $this->_parsedQuery['extensions'])) {
            $out = array();
            foreach ($this->_parsedQuery['extensions']['where'] as $filters) {
                $out += $this->_filter($data['data'], $filters);
            }
            $data['data'] = $out;
        }

        $result = array();
        if (array_key_exists('group', $this->_parsedQuery['extensions'])) {
            $used = array();
            foreach ($data['data'] as $array_data_p) {
                $current_column = $this->_parsedQuery['extensions']['group'][0];
                $current_data = $array_data_p[$current_column];
                $current_counter = 0;
                if (!in_array($current_data, $used, true)) {
                    foreach ($data['data'] as $array_data_c) {
                        if ($array_data_c[$current_column] === $current_data) {
                            ++$current_counter;
                        }
                    }
                    if (array_key_exists('as', $this->_parsedQuery['extensions'])) {
                        $result[] = array($this->_parsedQuery['extensions']['as'][0] => $current_counter, $current_column => $current_data);
                    } else {
                        $result[] = array('count(' . implode(',', $this->_parsedQuery['parameters']) . ')' => $current_counter, $current_column => $current_data);
                    }
                    $used[] = $current_data;
                }
            }
        } else {
            $counter = array();
            foreach ($data['data'] as $array_data) {
                foreach ($rows as $row) {
                    if (null !== $array_data[$row]) {
                        array_key_exists($row, $counter) ? ++$counter[$row] : $counter[$row] = 1;
                    }
                }
            }
            $count = count($counter) > 0 ? max($counter) : 0;

            if (array_key_exists('as', $this->_parsedQuery['extensions'])) {
                $result[$this->_parsedQuery['extensions']['as'][0]] = $count;
            } else {
                $result['count(' . implode(',', $this->_parsedQuery['parameters']) . ')'] = $count;
            }

            $result = array($result);
        }

        return new QueryResult($result, $this);
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

        foreach ($filters as $filter) {
            if (strtolower($filter['value']) === 'last_insert_id') {
                $filter['value'] = $data['properties']['last_insert_id'];
            }
            foreach ($result as $line) {
                $value = $line[$filter["field"]];
                if (preg_match("/(\w+)\\((.*)\\)/", $filter["field"], $parts)) {
                    $name = strtolower($parts[1]);
                    $param = !empty($parts[2]) ? $parts[2] : false;

                    if (false === $param) {
                        throw new QueryException("JSONDB Error: Can't use the function \"{$name}\" without a parameter");
                    }

                    $value = $this->_parseFunction($name, $line[$param]);
                    $filter["field"] = $param;
                }
                if (!array_key_exists($filter['field'], $line)) {
                    throw new QueryException("JSONDB Error: The field \"{$filter['field']}\" doesn't exists in the table \"{$this->_table}\".");
                }
                switch ($filter['operator']) {
                    case '<':
                        if ($value < $filter['value']) {
                            $temp[$line['#rowid']] = $line;
                        }
                        break;

                    case '<=':
                        if ($value <= $filter['value']) {
                            $temp[$line['#rowid']] = $line;
                        }
                        break;

                    case '=':
                        if ($value === $filter['value']) {
                            $temp[$line['#rowid']] = $line;
                        }
                        break;

                    case '>=':
                        if ($value >= $filter['value']) {
                            $temp[$line['#rowid']] = $line;
                        }
                        break;

                    case '>':
                        if ($value > $filter['value']) {
                            $temp[$line['#rowid']] = $line;
                        }
                        break;

                    case '!=':
                    case '<>':
                        if ($value !== $filter['value']) {
                            $temp[$line['#rowid']] = $line;
                        }
                        break;

                    case '%=':
                        if (0 === ($value % $filter['value'])) {
                            $temp[$line['#rowid']] = $line;
                        }
                        break;

                    case '%!':
                        if (0 !== ($value % $filter['value'])) {
                            $temp[$line['#rowid']] = $line;
                        }
                        break;

                    default:
                        throw new QueryException("JSONDB Error: The operator \"{$filter['operator']}\" is not supported. Try to use one of these operators: \"<\", \"<=\", \"=\", \">=\", \">\", \"<>\", \"!=\", \"%=\" or \"%!\".");
                }
            }

            $result = $temp;
            $temp = array();
        }

        return array_values($result);
    }

    /**
     * @param array $data
     * @param bool $min
     *
     * @return int
     */
    private static function _getLastValidRowID(array $data, bool $min = true): int
    {
        $last_valid_row_id = 0;

        foreach ($data as $line) {
            if ($last_valid_row_id === 0) {
                $last_valid_row_id = $line['#rowid'];
            } else {
                $last_valid_row_id = (true === $min) ? min($last_valid_row_id, $line['#rowid']) : max($last_valid_row_id, $line['#rowid']);
            }
        }

        return $last_valid_row_id;
    }
}