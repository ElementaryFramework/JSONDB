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
     * Class QueryParser
     *
     * @package     JSONDB
     * @subpackage  Utilities
     * @category    Parser
     * @author      Nana Axel
     */
    class QueryParser
    {
        /**
         * Reserved query's characters
         * @const string
         */
        const TRIM_CHAR = '\'"`() ';

        /**
         * Reserved query's characters
         * @const string
         */
        const ESCAPE_CHAR = '.,;\'()';

        /**
         * The not parsed query
         * @var string
         */
        private $notParsedQuery;

        /**
         * The parsed query
         * @var array
         */
        private $parsedQuery;

        /**
         * A list of supported queries
         * @var array
         */
        private static $supportedQueries = array('select', 'insert', 'delete', 'replace', 'truncate', 'update', 'count');

        /**
         * Registered query operators
         * @var array
         */
        private static $operators = array('%!', '%=', '!=', '<>', '<=', '>=', '=', '<', '>');

        /**
         * Quotes a value and escape reserved characters
         * @param string $value
         * @return string
         */
        public static function quote($value)
        {
            return "'" . str_replace(array('\\\'', '\,', '\.', '\(', '\)', '\;'), array('{{quot}}', '{{comm}}', '{{dot}}', '{{pto}}', '{{ptc}}', '{{semi}}'), preg_replace('#(['.self::ESCAPE_CHAR.'])#isU', '\\\$1', $value)) . "'";
        }

        /**
         * Parses a query
         * @param string $query
         * @return array
         * @throws Exception
         */
        public function parse($query)
        {
            $benchmark = new Benchmark();
            $benchmark->mark('jsondb_query_parse_start');

            $this->notParsedQuery = $query;

            // Getting query's parts
            $queryParts = explode('.', $this->notParsedQuery);

            // Getting the table name
            $this->parsedQuery['table'] = $queryParts[0];
            if (empty($this->parsedQuery['table'])) {
                throw new Exception('JSONDB Query Parse Error: No table detected in the query.');
            }

            // Checking query's parts validity
            foreach (array_slice($queryParts, 1) as $index => $part) {
                if (NULL === $part || $part === '') {
                    throw new Exception("JSONDB Query Parse Error: Unexpected \".\" after extension \"{$queryParts[$index]}\".");
                }
                if (FALSE === (bool)preg_match('#\w+\(.*\)#', $part)) {
                    throw new Exception("JSONDB Query Parse Error: There is an error at the extension \"{$part}\".");
                }
            }

            // Getting the query's main action
            $this->parsedQuery['action'] = preg_replace('#\(.*\)#', '', $queryParts[1]);
            if (!in_array(strtolower($this->parsedQuery['action']), self::$supportedQueries, TRUE)) {
                throw new Exception("JSONDB Query Parse Error: The query \"{$this->parsedQuery['action']}\" isn't supported by JSONDB.");
            }

            // Getting the action's parameters
            $this->parsedQuery['parameters'] = explode(',', trim(str_replace($this->parsedQuery['action'], '', $queryParts[1]), ' ()'));
            $this->parsedQuery['parameters'] = array_map(function($field) {
                return trim($field, ' ');
            }, (!empty($this->parsedQuery['parameters'][0]) ? $this->parsedQuery['parameters'] : array()));

            // Parsing values for some actions
            if (in_array(strtolower($this->parsedQuery['action']), array('insert', 'replace'), TRUE)) {
                $this->parsedQuery['parameters'] = array_map(array(&$this, '_parseValue'), $this->parsedQuery['parameters']);
            }

            // Getting query's extensions
            $this->parsedQuery['extensions'] = array();
            $extensions = array();
            foreach (array_slice($queryParts, 2) as $extension) {
                $name = preg_replace('#\(.*\)#', '', $extension);
                $string = trim(str_replace($name, '', $extension), ' ()');
                switch (strtolower($name)) {
                    case 'order':
                        $extensions['order'] = $this->_parseOrderExtension($string);
                        break;

                    case 'where':
                        if (!array_key_exists('where', $extensions)) {
                            $extensions['where'] = array();
                        }
                        $extensions['where'][] = $this->_parseWhereExtension($string);
                        break;

                    case 'and':
                        if (!array_key_exists('and', $extensions)) {
                            $extensions['and'] = array();
                        }
                        $extensions['and'][] = $this->_parseAndExtension($string);
                        break;

                    case 'limit':
                        $extensions['limit'] = $this->_parseLimitExtension($string);
                        break;

                    case 'in':
                        $extensions['in'] = $this->_parseInExtension($string);
                        break;

                    case 'with':
                        $extensions['with'] = $this->_parseWithExtension($string);
                        break;

                    case 'as':
                        $extensions['as'] = $this->_parseAsExtension($string);
                        break;

                    case 'group':
                        $extensions['group'] = $this->_parseGroupExtension($string);
                        break;

                    case 'on':
                        if (!array_key_exists('on', $extensions)) {
                            $extensions['on'] = array();
                        }
                        $extensions['on'][] = $this->_parseOnExtension($string);
                        break;

                    case 'link':
                        if (!array_key_exists('link', $extensions)) {
                            $extensions['link'] = array();
                        }
                        $extensions['link'][] = $this->_parseLinkExtension($string);
                        break;
                }
            }
            $this->parsedQuery['extensions'] = $extensions;

            $this->parsedQuery['benchmark'] = array(
                'elapsed_time' => $benchmark->elapsed_time('jsondb_query_parse_start', 'jsondb_query_parse_end'),
                'memory_usage' => $benchmark->memory_usage()
            );

            return $this->parsedQuery;
        }

        /**
         * Parses an order() extension
         * @param string $clause
         * @return array
         * @throws Exception
         */
        private function _parseOrderExtension($clause)
        {
            $parsedClause = array_map(function($field) {
                return trim($field, self::TRIM_CHAR);
            }, explode(',', $clause));
            $parsedClause = NULL !== $parsedClause[0] ? $parsedClause : array();
            if (count($parsedClause) === 0) {
                throw new Exception("JSONDB Query Parse Error: At least one parameter expected for the \"order()\" extension.");
            }
            if (count($parsedClause) > 2) {
                throw new Exception("JSONDB Query Parse Error: Too much parameters given to the \"order()\" extension, only two required.");
            }
            if (array_key_exists(1, $parsedClause) && !in_array(strtolower($parsedClause[1]), array('asc', 'desc'), TRUE)) {
                throw new Exception("JSONDB Query Parse Error: The second parameter of the \"order()\" extension can only have values: \"asc\" or \"desc\".");
            }
            if (!array_key_exists(1, $parsedClause)) {
                $parsedClause[1] = 'asc';
            }

            return $parsedClause;
        }

        /**
         * Parses a where() extension
         * @param string $clause
         * @return array
         * @throws Exception
         */
        private function _parseWhereExtension($clause)
        {
            $parsedClause = explode(',', $clause);
            $parsedClause = NULL !== $parsedClause[0] ? $parsedClause : array();
            if (count($parsedClause) === 0) {
                throw new Exception("JSONDB Query Parse Error: At least one parameter expected for the \"where()\" extension.");
            }

            foreach ($parsedClause as $index => &$condition) {
                $condition = $this->_parseWhereExtensionCondition($parsedClause[$index]);
            }
            unset($condition);

            return $parsedClause;
        }

        /**
         * Parses a where() extension's condition
         * @param string $condition The condition
         * @return array
         */
        private function _parseWhereExtensionCondition($condition)
        {
            $filters = array();

            foreach (self::$operators as $operator) {
                if (FALSE !== strpos($condition, $operator) || in_array($operator, explode(' ', $condition), TRUE) || in_array($operator, str_split($condition), TRUE)) {
                    $row_val = explode($operator, $condition);
                    $filters['operator'] = $operator;
                    $filters['field'] = trim($row_val[0], self::TRIM_CHAR);
                    $filters['value'] = $this->_parseValue($row_val[1]);
                    break;
                }
            }

            return $filters;
        }

        /**
         * Parses an and() extension
         * @param string $clause
         * @return array
         * @throws Exception
         */
        private function _parseAndExtension($clause)
        {
            $parsedClause = explode(',', $clause);
            $parsedClause = NULL !== $parsedClause[0] ? $parsedClause : array();
            if (count($parsedClause) === 0) {
                throw new Exception("JSONDB Query Parse Error: At least one parameter expected for the \"and()\" extension.");
            }

            return (array)array_map(array(&$this, '_parseValue'), $parsedClause);
        }

        /**
         * Parses a limit() condition
         * @param string $clause
         * @return array
         * @throws Exception
         */
        private function _parseLimitExtension($clause)
        {
            $parsedClause = explode(',', $clause);
            $parsedClause = (NULL !== $parsedClause[0] || (int)$parsedClause[0] === 0) ? $parsedClause : array();
            if (count($parsedClause) === 0) {
                throw new Exception("JSONDB Query Parse Error: At least one parameter expected for the \"limit()\" extension.");
            }
            if (count($parsedClause) > 2) {
                throw new Exception("JSONDB Query Parse Error: Too much parameters given to the \"limit()\" extension, only two required.");
            }

            if (!array_key_exists(1, $parsedClause)) {
                $parsedClause[1] = $parsedClause[0];
                $parsedClause[0] = 0;
            }

            return (array)array_map(array(&$this, '_parseValue'), $parsedClause);
        }

        /**
         * Parses an in() extension
         * @param string $clause
         * @return array
         * @throws Exception
         */
        private function _parseInExtension($clause)
        {
            $parsedClause = array_map(function($field) {
                return trim($field, self::TRIM_CHAR);
            }, explode(',', $clause));
            $parsedClause = NULL !== $parsedClause[0] ? $parsedClause : array();
            if (count($parsedClause) === 0) {
                throw new Exception("JSONDB Query Parse Error: At least one parameter expected for the \"in()\" extension.");
            }

            return $parsedClause;
        }

        /**
         * Parses a with() extension
         * @param string $clause
         * @return array
         * @throws Exception
         */
        private function _parseWithExtension($clause)
        {
            $parsedClause = explode(',', $clause);
            $parsedClause = NULL !== $parsedClause[0] ? $parsedClause : array();
            if (count($parsedClause) === 0) {
                throw new Exception("JSONDB Query Parse Error: At least one parameter expected for the \"with()\" extension.");
            }

            return (array)array_map(array(&$this, '_parseValue'), $parsedClause);
        }

        /**
         * Parses a as() extension
         * @param string $clause
         * @return array
         * @throws Exception
         */
        private function _parseAsExtension($clause)
        {
            $parsedClause = array_map(function($field) {
                return trim($field, self::TRIM_CHAR);
            }, explode(',', $clause));
            $parsedClause = NULL !== $parsedClause[0] ? $parsedClause : array();
            if (count($parsedClause) === 0) {
                throw new Exception("JSONDB Query Parse Error: At least one parameter expected for the \"as()\" extension.");
            }

            return $parsedClause;
        }

        /**
         * Parses a group() extension
         * @param string $clause
         * @return array
         * @throws Exception
         */
        private function _parseGroupExtension($clause)
        {
            $parsedClause = array_map(function($field) {
                return trim($field, self::TRIM_CHAR);
            }, explode(',', $clause));
            $parsedClause = NULL !== $parsedClause[0] ? $parsedClause : array();
            if (count($parsedClause) === 0) {
                throw new Exception("JSONDB Query Parse Error: At least one parameter expected for the \"group()\" extension.");
            }
            if (count($parsedClause) > 1) {
                throw new Exception("JSONDB Query Parse Error: Too much parameters given to the \"group()\" extension, only one required.");
            }

            return $parsedClause;
        }

        private function _parseOnExtension($clause)
        {
            $parsedClause = array_map(function($field) {
                return trim($field, self::TRIM_CHAR);
            }, explode(',', $clause));
            $parsedClause = NULL !== $parsedClause[0] ? $parsedClause : array();
            if (count($parsedClause) === 0) {
                throw new Exception("JSONDB Query Parse Error: At least one parameter expected for the \"on()\" extension.");
            }

            return $parsedClause;
        }

        private function _parseLinkExtension($clause)
        {
            $parsedClause = array_map(function($field) {
                return trim($field, self::TRIM_CHAR);
            }, explode(',', $clause));
            $parsedClause = NULL !== $parsedClause[0] ? $parsedClause : array();
            if (count($parsedClause) === 0) {
                throw new Exception("JSONDB Query Parse Error: At least one parameter expected for the \"link()\" extension.");
            }

            return $parsedClause;
        }

        /**
         * Parses a value
         *
         * It will convert (cast if necessary) a value
         * to its true type.
         *
         * @param mixed $value
         * @return bool|int|string|null
         */
        protected function _parseValue($value)
        {
            $trim_value = trim($value, ' ');
            if (strpos($value, ':JSONDB::TO_BOOL:') !== FALSE) {
                return (bool)(int)str_replace(':JSONDB::TO_BOOL:', '', $value);
            } elseif (strtolower($value) === 'false') {
                return FALSE;
            } elseif (strtolower($value) === 'true') {
                return TRUE;
            } elseif (strpos($value, ':JSONDB::TO_NULL:') !== FALSE || strtolower($value) === 'null') {
                return NULL;
            } elseif ($trim_value[0] === "'" && $trim_value[strlen($trim_value) - 1] === "'") {
                return (string)str_replace(array('{{quot}}', '{{comm}}', '{{dot}}', '{{pto}}', '{{ptc}}', '{{semi}}'), array('\'', ',', '.', '(', ')', ';'), (string)trim($value, self::TRIM_CHAR));
            } else {
                return (int)trim($value, self::TRIM_CHAR);
            }
        }
    }