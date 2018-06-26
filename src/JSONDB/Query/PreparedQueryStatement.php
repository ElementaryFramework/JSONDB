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

/**
 * Prepared Query Statement
 *
 * @package  JSONDB
 * @category Query
 * @author   Axel Nana <ax.lnana@outlook.com>
 * @link     http://php.jsondb.na2axl.tk/docs/api/jsondb/query/preparedquerystatement
 */
class PreparedQueryStatement
{
    /**
     * The Query instance
     * @var Query
     */
    private $_query;

    /**
     * The query string.
     * @var string
     */
    private $_queryString;

    /**
     * An array of keys inserted in the
     * query.
     * @var array
     */
    private $_preparedQueryKeys = array();

    /**
     * PreparedQueryStatement __constructor.
     * @param string $query
     * @param Database $query
     */
    public function __construct($queryString, Query &$query)
    {
        $this->_queryString = $queryString;
        $this->_query = &$query;

        $this->_prepareQuery();
    }

    /**
     * Binds a value in a prepared query.
     * @param string $key The parameter's key
     * @param string|int|bool $value The parameter's value
     * @param int $parse_method The parse method to use
     * @throws Exception
     */
    public function bindValue($key, $value, $parse_method = JSONDB::PARAM_STRING)
    {
        if ($this->_query->isPreparedQuery()) {
            if (in_array($key, $this->_preparedQueryKeys, true)) {
                switch ($parse_method) {
                    default:
                    case JSONDB::PARAM_STRING:
                        $value = JSONDB::quote(str_val($value));
                        break;

                    case JSONDB::PARAM_INT:
                        $value = int_val($value);
                        break;

                    case JSONDB::PARAM_BOOL:
                        $value = int_val($value);
                        $value = "{$value}:JSONDB::TO_BOOL:";
                        break;

                    case JSONDB::PARAM_NULL:
                        $value = "{$value}:JSONDB::TO_NULL:";
                        break;

                    case JSONDB::PARAM_ARRAY:
                        $value = JSONDB::quote(serialize($value)) . ':JSONDB::TO_ARRAY:';
                        break;
                }

                $this->_queryString = str_replace($key, $value, $this->_queryString);

            } else {
                throw new QueryException("JSONDB Error: Can't bind the value \"{$value}\" for the key \"{$key}\". The key isn't in the query.");
            }
        } else {
            throw new QueryException("JSONDB Error: Can't use bindValue() with non prepared queries. Send your query with prepare() first.");
        }
    }

    /**
     * Execute the prepared query
     * @throws Exception
     * @return mixed
     */
    public function execute()
    {
        if ($this->_query->isPreparedQuery()) {
            return $this->_query->query($this->_queryString);
        } else {
            throw new QueryException("JSONDB Error: Can't execute the prepared query. There is no prepared query to execute or the prepared query is already executed.");
        }
    }

    /**
     * Prepare a query
     * @return PreparedQueryStatement
     */
    private function _prepareQuery()
    {
        $query = $this->queryString;
        preg_match_all('/(:[\w]+)/', $query, $keys);
        $this->preparedQueryKeys = $keys[0];
    }

}