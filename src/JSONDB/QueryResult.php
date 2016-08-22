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
     * @package	   JSONDB
     * @author	   Nana Axel
     * @copyright  Copyright (c) 2016, Centers Technologies
     * @license	   http://opensource.org/licenses/MIT MIT License
     * @filesource
     */

    namespace JSONDB;

    /**
     * Class QueryResult
     *
     * @package		JSONDB
     * @subpackage  Utilities
     * @category    Query
     * @author		Nana Axel
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
         * JSONDB instance
         * @var JSONDB
         */
        private $database;

        /**
         * QueryResult __constructor
         * @param array  $result
         * @param JSONDB $database The JSONDB instance to use with results.
         */
        public function __construct(array $result, JSONDB $database)
        {
            $this->database = $database;
            $this->_setResults($result);
            $this->_parseResults();
        }

        /**
         * Changes the value of results
         * @param mixed $results
         */
        private function _setResults($results)
        {
            $this->results = $results;
        }

        /**
         * Checks if the key can be accessed
         */
        public function valid()
        {
            if ($this->key === '#queryString') {
                return FALSE;
            } else {
                return array_key_exists($this->key, $this->results);
            }
        }

        /**
         * Returns the current result
         * @return array
         */
        public function current()
        {
            return $this->results[$this->key];
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
                throw new Exception("JSONDB Query Result Error: Trying to access an inexisting result key \"{$position}\"");
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
                throw new Exception("JSONDB Query Result Error: Can't access the result at offset \"{$offset}\".");
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
            throw new Exception("JSONDB Query Result Error: Trying to change a result value. The action isn't allowed.");
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
                $this->_setResults(array_values(array_slice($this->results, 2)));
                $this->_parseResults();
            }
        }

        /**
         * Fetch for results
         * @param int $mode The fetch mode
         * @return array|QueryResultObject|null
         * @throws Exception
         */
        public function fetch($mode = NULL)
        {
            if (NULL !== $mode) {
                $this->setFetchMode($mode);
            }

            if ($this->database->queryIsExecuted()) {
                if ($this->valid()) {
                    $return = $this->current();
                    ++$this->key;

                    switch ($this->fetchMode) {
                        case JSONDB::FETCH_ARRAY:
                            return (array)$return;

                        case JSONDB::FETCH_OBJECT:
                            return new QueryResultObject($return);
                    }
                }
                return NULL;
            } else {
                throw new Exception("JSONDB Query Result Error: Can't fetch for results without execute the query.");
            }
        }

        /**
         * Changes the fetch mode
         * @param int $mode
         */
        public function setFetchMode($mode = JSONDB::FETCH_ARRAY)
        {
            $this->fetchMode = $mode;
        }

        /**
         * Adds information in results
         */
        private function _parseResults()
        {
            $this->results = array_merge(
                array('#queryString' => $this->database->queryString(),
                      '#elapsedtime' => $this->database->benchmark()->elapsed_time('jsondb_(query)_start', 'jsondb_(query)_end'))
                , $this->results);
        }
    }

    /**
     * Class QueryResultObject
     *
     * @package		JSONDB
     * @subpackage  Utilities
     * @category    Results
     * @author		Nana Axel
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
         * @param mixed $result
         */
        private function _setResult($result)
        {
            $this->result = $result;
        }

        /**
         * @param string $name
         * @return mixed
         * @throws Exception
         */
        public function __get($name)
        {
            if (array_key_exists($name, $this->result)) {
                return $this->result[$name];
            } else {
                throw new Exception("JSONDB Query Result Error: Can't access the key \"{$name}\" in result, maybe the key doesn't exist.");
            }
        }
    }