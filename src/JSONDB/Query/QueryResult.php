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
use ElementaryFramework\JSONDB\JSONDB;
use ElementaryFramework\JSONDB\Utilities\Benchmark;


/**
 * Query Result
 *
 * @package  JSONDB
 * @category Query
 * @author   Axel Nana <ax.lnana@outlook.com>
 * @link     http://php.jsondb.na2axl.tk/docs/api/jsondb/query/queryresult
 */
class QueryResult implements \Iterator, \SeekableIterator, \Countable, \Serializable, \ArrayAccess
{
    /**
     * Current key
     * @var string|int
     */
    private $key = 0;

    /**
     * Results array
     * @var array
     */
    private $results;

    /**
     * Fetch mode
     * @var int
     */
    private $fetchMode;

    /**
     * Class name used for FETCH_CLASS method
     * @var string
     */
    private $className;

    /**
     * Query instance
     * @var Query
     */
    private $database;

    /**
     * QueryResult __constructor
     * @param Query $query The Query instance to use with results.
     */
    public function __construct(Query &$query)
    {
        $this->database =& $query;
        $this->_setResults($query->getResults());
        $this->setFetchMode(JSONDB::FETCH_ARRAY);
    }

    /**
     * Changes the value of results
     * @param mixed $results
     */
    private function _setResults($results)
    {
        $this->results = $results;
        $this->_parseResults();
    }

    /**
     * Checks if the key can be accessed
     */
    public function valid()
    {
        if ($this->key === '#queryString' || $this->key === '#elapsedtime' || $this->key === '#memoryusage') {
            return false;
        } else {
            return array_key_exists($this->key, $this->results);
        }
    }

    /**
     * Returns the query string
     */
    public function queryString()
    {
        return $this->database->getParser()->getQueryString();
    }

    /**
     * Returns the current result
     * @return array|\ElementaryFramework\JSONDB\Query\QueryResultObject|object
     * @throws Exception
     */
    public function current()
    {
        $return = $this->results[$this->key];

        switch ($this->fetchMode) {
            case JSONDB::FETCH_ARRAY:
                return (array)$return;

            case JSONDB::FETCH_OBJECT:
                return new \ElementaryFramework\JSONDB\Query\QueryResultObject($return);

            case JSONDB::FETCH_CLASS:
                if (!class_exists($this->className)) {
                    throw new QueryException("JSONDB Query Result Error: Can't fetch for data. Trying to use JSONDB::FETCH_CLASS mode with class \"{$this->className}\" but the class doesn't exist or not found.");
                }
                $mapper = new $this->className;
                $availableVars = get_class_vars($this->className);
                foreach ((array)$return as $item => $value) {
                    if (!array_key_exists($item, $availableVars)) {
                        throw new QueryException("JSONDB Query Result Error: Can't fetch for data. Using JSONDB::FETCH_CLASS mode with class \"{$this->className}\" but the property \"{$item}\" doesn't exist or not public.");
                    }
                    $mapper->$item = $value;
                }
                return $mapper;

            default:
                throw new QueryException('JSONDB Query Result Error: Fetch mode not supported.');
        }
    }

    /**
     * Seeks the internal pointer to the next value
     */
    public function next()
    {
        $this->key++;
    }

    /**
     * Returns the value of the internal pointer
     * @return mixed
     */
    public function key()
    {
        return $this->key;
    }

    /**
     * Seeks the internal pointer to 0
     */
    public function rewind()
    {
        $this->key = 0;
    }

    /**
     * Seeks the internal pointer to a position
     * @param int $position
     * @throws Exception
     * @return mixed
     */
    public function seek($position)
    {
        $lastKey = $this->key;
        $this->key = $position;

        if (!$this->valid()) {
            $this->key = $lastKey;
            throw new QueryException("JSONDB Query Result Error: Trying to access an inexisting result key \"{$position}\"");
        }
    }

    /**
     * Counts results
     * @return int
     */
    public function count()
    {
        $counter = 0;
        foreach ((array)$this->results as $key => $value) {
            if (is_int($key)) {
                ++$counter;
            }
        }

        return $counter;
    }

    /**
     * Serializes results
     * @return string
     */
    public function serialize()
    {
        return serialize($this->results);
    }

    /**
     * Unserializes results
     * @param string $serialized
     * @return QueryResult
     */
    public function unserialize($serialized)
    {
        $this->_setResults(unserialize($serialized));
        return $this;
    }

    /**
     * Checks if a result exist at the given offset
     * @param int $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->results);
    }

    /**
     * Return the result at the given offset
     * @param int $offset
     * @return mixed
     * @throws Exception
     */
    public function offsetGet($offset)
    {
        if ($this->offsetExists($offset)) {
            return $this->results[$offset];
        } else {
            throw new QueryException("JSONDB Query Result Error: Can't access the result at offset \"{$offset}\".");
        }
    }

    /**
     * Changes the result value at the given offset
     * @param int $offset
     * @param array $value
     * @return void
     * @throws Exception
     */
    public function offsetSet($offset, $value)
    {
        throw new QueryException("JSONDB Query Result Error: Trying to change a result value. The action isn't allowed.");
    }

    /**
     * Unsets a result at the given offset
     * @param int $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        if ($this->offsetExists($offset)) {
            unset($this->results[$offset]);
            $this->_setResults(array_values(array_slice($this->results, 3)));
        }
    }

    /**
     * Fetch for results
     * @param int $mode The fetch mode
     * @param string $className The class name (for JSONDB::FETCH_CLASS)
     * @return array|\ElementaryFramework\JSONDB\Query\QueryResultObject|bool
     * @throws Exception
     */
    public function fetch($mode = null, $className = null)
    {
        if (null !== $mode) {
            $this->setFetchMode($mode, $className);
        }

        if ($this->database->isQueryExecuted()) {
            if ($this->valid()) {
                $return = $this->current();
                ++$this->key;
                return $return;
            }
            return false;
        } else {
            throw new QueryException("JSONDB Query Result Error: Can't fetch for results without execute the query.");
        }
    }

    /**
     * Changes the fetch mode
     * @param int $mode
     * @param string $className
     * @return QueryResult
     */
    public function setFetchMode($mode = JSONDB::FETCH_ARRAY, $className = null)
    {
        $this->fetchMode = $mode;
        $this->className = $className;
        return $this;
    }

    /**
     * Adds information in results
     */
    private function _parseResults()
    {
        $this->results = array_merge(
            array(
                '#queryString' => $this->queryString(),
                '#elapsedtime' => Benchmark::elapsed_time('jsondb_(query)_start', 'jsondb_(query)_end'),
                '#memoryusage' => Benchmark::memory_usage('jsondb_(query)_start', 'jsondb_(query)_end')
            ),
            $this->results);
    }
}