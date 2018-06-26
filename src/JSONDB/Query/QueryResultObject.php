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

use ElementaryFramework\JSONDB\Exceptions\QueryException;

/**
 * Query Result Object
 *
 * @package  JSONDB
 * @category Query
 * @author   Axel Nana <ax.lnana@outlook.com>
 * @link     http://php.jsondb.na2axl.tk/docs/api/jsondb/query/queryresultobject
 */
class QueryResultObject
{
    /**
     * The specified result
     * @var array
     */
    private $result;

    /**
     * ObjectQueryResult __constructor.
     * @param array $result_array
     */
    public function __construct(array $result_array)
    {
        $this->_setResult($result_array);
    }

    /**
     * Sets the results to use
     * @param mixed $result
     */
    private function _setResult($result)
    {
        $this->result = $result;
    }

    /**
     * Gets a row value if the given row name exist
     * in the table.
     * @param string $name
     * @return mixed
     * @throws Exception
     */
    public function __get($name)
    {
        if (array_key_exists($name, $this->result)) {
            if (is_array($this->result[$name])) {
                return new QueryResultObject($this->result[$name]);
            }
            return $this->result[$name];
        } else {
            throw new QueryException("JSONDB Query Result Error: Can't access the key \"{$name}\" in result, maybe the key doesn't exist.");
        }
    }
}