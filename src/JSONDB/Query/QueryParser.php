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

use ElementaryFramework\JSONDB\Utilities\Benchmark;
use ElementaryFramework\JSONDB\Exceptions\QueryException;


/**
 * Query Parser
 *
 * @package  JSONDB
 * @category Query
 * @author   Axel Nana <ax.lnana@outlook.com>
 * @link     http://php.jsondb.na2axl.tk/docs/api/jsondb/query/queryparser
 */
class QueryParser
{
    /**
     * Reserved query's characters
     * @var string
     */
    const TRIM_CHAR = '\'"`() ';

    /**
     * Reserved query's characters
     * @var string
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

    public function getQueryString()
    {
        return $this->notParsedQuery;
    }

    /**
     * Parses a query
     * @param string $query
     * @return array
     * @throws Exception
     */
    public function parse(string $query): array
    {
        Benchmark::mark('jsondb_query_parse_start');
        {
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
                if (null === $part || $part === '') {
                    throw new Exception("JSONDB Query Parse Error: Unexpected \".\" after extension \"{$queryParts[$index]}\".");
                }
                if (false === (bool)preg_match('/\w+\(.*\)/', $part)) {
                    throw new Exception("JSONDB Query Parse Error: There is an error at the extension \"{$part}\".");
                }
            }

            // Getting the query's main action
            $this->parsedQuery['action'] = preg_replace('/\(.*\)/', '', $queryParts[1]);
            if (!in_array(strtolower($this->parsedQuery['action']), self::$supportedQueries, true)) {
                throw new Exception("JSONDB Query Parse Error: The query \"{$this->parsedQuery['action']}\" isn't supported by JSONDB.");
            }

            // Getting the action's parameters
            // TODO: Continue here...
            $this->parsedQuery["parameters"] = preg_replace('/\w+\((.*)\)/', '$1', $queryParts[1]);
            $this->parsedQuery["parameters"] = preg_replace_callback('/\(([^)]*)\)/', function ($str) {
                return str_replace(',', ';', $str);
            }, $this->parsedQuery["parameters"]);
            $this->parsedQuery['parameters'] = explode(',', $this->parsedQuery["parameters"]);
            $this->parsedQuery['parameters'] = array_map(function($field) {
                return trim($field);
            }, (!empty($this->parsedQuery['parameters'][0]) ? $this->parsedQuery['parameters'] : array()));

            // Parsing values for some actions
            if (in_array(strtolower($this->parsedQuery['action']), array('insert', 'replace'), true)) {
                $this->parsedQuery['parameters'] = array_map(array(&$this, '_parseValue'), $this->parsedQuery['parameters']);
            }

            // Getting query's extensions
            $this->parsedQuery['extensions'] = array();
            $extensions = array();
            $slice = array_slice($queryParts, 2);
            foreach ($slice as $extension) {
                $extension = trim($extension);
                $name = preg_replace('/\(.*\)/', '', $extension);
                $string = preg_replace_callback('/\(([^)]*)\)/', function ($str) {
                    return str_replace(',', ';', $str);
                }, trim(preg_replace("/{$name}\\((.*)\\)/", '$1', $extension)));
                switch (strtolower($name)) {
                    case 'order':
                        $extensions['order'] = $this->_parseOrderExtension($string);
                        break;

                    case 'where':
                        if (!array_key_exists('where', $extensions)) {
                            $extensions['where'] = array();
                        }
                        array_push($extensions['where'], $this->_parseWhereExtension($string));
                        break;

                    case 'and':
                        if (!array_key_exists('and', $extensions)) {
                            $extensions['and'] = array();
                        }
                        array_push($extensions['and'], $this->_parseAndExtension($string));
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
                        array_push($extensions['on'], $this->_parseOnExtension($string));
                        break;

                    case 'link':
                        if (!array_key_exists('link', $extensions)) {
                            $extensions['link'] = array();
                        }
                        array_push($extensions['link'], $this->_parseLinkExtension($string));
                        break;
                }
            }
            $this->parsedQuery['extensions'] = $extensions;
        }
        $this->parsedQuery['benchmark'] = array(
            'elapsed_time' => Benchmark::elapsed_time('jsondb_query_parse_start', 'jsondb_query_parse_end'),
            'memory_usage' => Benchmark::memory_usage('jsondb_query_parse_start', 'jsondb_query_parse_end')
        );

        return $this->parsedQuery;
    }

    /**
     * Parses an order() extension
     * @param string $clause
     * @return array
     * @throws QueryException
     */
    private function _parseOrderExtension(string $clause): array
    {
        $parsedClause = array_map(function($field) {
            return trim($field, self::TRIM_CHAR);
        }, explode(',', $clause));
        $parsedClause = null !== $parsedClause[0] ? $parsedClause : array();
        if (count($parsedClause) === 0) {
            throw new QueryException("JSONDB Query Parse Error: At least one parameter expected for the \"order()\" extension.");
        }
        if (count($parsedClause) > 2) {
            throw new QueryException("JSONDB Query Parse Error: Too much parameters given to the \"order()\" extension, only two required.");
        }
        if (array_key_exists(1, $parsedClause) && !in_array(strtolower($parsedClause[1]), array('asc', 'desc'), true)) {
            throw new QueryException("JSONDB Query Parse Error: The second parameter of the \"order()\" extension can only have values: \"asc\" or \"desc\".");
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
     * @throws QueryException
     */
    private function _parseWhereExtension(string $clause): array
    {
        $parsedClause = explode(',', $clause);
        $parsedClause = isset($parsedClause[0]) ? $parsedClause : array();
        if (count($parsedClause) === 0) {
            throw new QueryException("JSONDB Query Parse Error: At least one parameter expected for the \"where()\" extension.");
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
            if (false !== strpos($condition, $operator) || in_array($operator, explode(' ', $condition), true) || in_array($operator, str_split($condition), true)) {
                $row_val = explode($operator, $condition);
                $filters['operator'] = $operator;
                $filters['field'] = trim($row_val[0], self::TRIM_CHAR);
                $filters['value'] = $this->_parseValue(
                    count($row_val) > 2 ?
                    implode($operator, array_slice($row_val, 1)) :
                    $row_val[1]
                );
                break;
            }
        }

        return $filters;
    }

    /**
     * Parses an and() extension
     * @param string $clause
     * @return array
     * @throws QueryException
     */
    private function _parseAndExtension(string $clause): array
    {
        $parsedClause = explode(',', $clause);
        $parsedClause = null !== $parsedClause[0] ? $parsedClause : array();
        if (count($parsedClause) === 0) {
            throw new QueryException("JSONDB Query Parse Error: At least one parameter expected for the \"and()\" extension.");
        }

        return array_map(array(&$this, '_parseValue'), $parsedClause);
    }

    /**
     * Parses a limit() condition
     * @param string $clause
     * @return array
     * @throws QueryException
     */
    private function _parseLimitExtension(string $clause): array
    {
        $parsedClause = explode(',', $clause);
        $parsedClause = (null !== $parsedClause[0] || (int)$parsedClause[0] === 0) ? $parsedClause : array();
        if (count($parsedClause) === 0) {
            throw new QueryException("JSONDB Query Parse Error: At least one parameter expected for the \"limit()\" extension.");
        }
        if (count($parsedClause) > 2) {
            throw new QueryException("JSONDB Query Parse Error: Too much parameters given to the \"limit()\" extension, only two required.");
        }

        if (!array_key_exists(1, $parsedClause)) {
            $parsedClause[1] = $parsedClause[0];
            $parsedClause[0] = 0;
        }

        return array_map(array(&$this, '_parseValue'), $parsedClause);
    }

    /**
     * Parses an in() extension
     * @param string $clause
     * @return array
     * @throws QueryException
     */
    private function _parseInExtension(string $clause): array
    {
        $parsedClause = array_map(function($field) {
            return trim($field, self::TRIM_CHAR);
        }, explode(',', $clause));
        $parsedClause = null !== $parsedClause[0] ? $parsedClause : array();
        if (count($parsedClause) === 0) {
            throw new QueryException("JSONDB Query Parse Error: At least one parameter expected for the \"in()\" extension.");
        }

        return $parsedClause;
    }

    /**
     * Parses a with() extension
     * @param string $clause
     * @return array
     * @throws QueryException
     */
    private function _parseWithExtension(string $clause): array
    {
        $parsedClause = explode(',', $clause);
        $parsedClause = null !== $parsedClause[0] ? $parsedClause : array();
        if (count($parsedClause) === 0) {
            throw new QueryException("JSONDB Query Parse Error: At least one parameter expected for the \"with()\" extension.");
        }

        return array_map(array(&$this, '_parseValue'), $parsedClause);
    }

    /**
     * Parses a as() extension
     * @param string $clause
     * @return array
     * @throws QueryException
     */
    private function _parseAsExtension(string $clause): array
    {
        $parsedClause = array_map(function($field) {
            return trim($field, self::TRIM_CHAR);
        }, explode(',', $clause));
        $parsedClause = null !== $parsedClause[0] ? $parsedClause : array();
        if (count($parsedClause) === 0) {
            throw new QueryException("JSONDB Query Parse Error: At least one parameter expected for the \"as()\" extension.");
        }

        return $parsedClause;
    }

    /**
     * Parses a group() extension
     * @param string $clause
     * @return array
     * @throws QueryException
     */
    private function _parseGroupExtension(string $clause): array
    {
        $parsedClause = array_map(function($field) {
            return trim($field, self::TRIM_CHAR);
        }, explode(',', $clause));
        $parsedClause = null !== $parsedClause[0] ? $parsedClause : array();
        if (count($parsedClause) === 0) {
            throw new QueryException("JSONDB Query Parse Error: At least one parameter expected for the \"group()\" extension.");
        }
        if (count($parsedClause) > 1) {
            throw new QueryException("JSONDB Query Parse Error: Too much parameters given to the \"group()\" extension, only one required.");
        }

        return $parsedClause;
    }

    /**
     * Parses an on() extension
     * @param string $clause
     * @return array
     * @throws QueryException
     */
    private function _parseOnExtension(string $clause): array
    {
        $parsedClause = array_map(function($field) {
            return trim($field, self::TRIM_CHAR);
        }, explode(',', $clause));
        $parsedClause = null !== $parsedClause[0] ? $parsedClause : array();
        if (count($parsedClause) === 0) {
            throw new QueryException("JSONDB Query Parse Error: At least one parameter expected for the \"on()\" extension.");
        }
        if (count($parsedClause) > 1) {
            throw new QueryException("JSONDB Query Parse Error: Too much parameters given to the \"on()\" extension, only one required.");
        }

        return $parsedClause[0];
    }

    /**
     * Parses an link() extension
     * @param string $clause
     * @return array
     * @throws QueryException
     */
    private function _parseLinkExtension(string $clause): array
    {
        $parsedClause = array_map(function($field) {
            return trim($field, self::TRIM_CHAR);
        }, explode(',', $clause));
        $parsedClause = null !== $parsedClause[0] ? $parsedClause : array();
        if (count($parsedClause) === 0) {
            throw new QueryException("JSONDB Query Parse Error: At least one parameter expected for the \"link()\" extension.");
        }

        return $parsedClause;
    }

    protected function _parseFunction(string $unction)
    {
        preg_match('/(\w+)\((.*)\)/', $function, $parts);
        $name = isset($parts[2]) ? array_map(array(&$this, "_parseValue"), explode(';', $parts[2])) : false;

        switch ($name) {
            case 'sha1':
                if ($params === false) {
                    throw new QueryException("JSONDB Query Parse Error: There is no parameters for the function sha1(). Can't execute the query.");
                }
                if (count($params) > 1) {
                    throw new QueryException("JSONDB Query Parse Error: Too much parameters for the function sha1(), only one is required.");
                }
                return sha1($params[0]);

            case 'md5':
                if ($params === false) {
                    throw new QueryException("JSONDB Query Parse Error: There is no parameters for the function md5(). Can't execute the query.");
                }
                if (count($params) > 1) {
                    throw new QueryException("JSONDB Query Parse Error: Too much parameters for the function md5(), only one is required.");
                }
                return md5($params[0]);

            case 'time':
                if ($params !== false) {
                    throw new QueryException("JSONDB Query Parse Error: Too much parameters for the function time(), no one is required.");
                }
                return time();

            case 'now':
                if ($params === false) {
                    throw new QueryException("JSONDB Query Parse Error: There is no parameters for the function now(). Can't execute the query.");
                }
                if (count($params) > 1) {
                    throw new QueryException("JSONDB Query Parse Error: Too much parameters for the function now(), only one is required.");
                }
                return strftime($params[0]);

            case 'lowercase':
                if ($params === false) {
                    throw new QueryException("JSONDB Query Parse Error: There is no parameters for the function lowercase(). Can't execute the query.");
                }
                if (count($params) > 1) {
                    throw new QueryException("JSONDB Query Parse Error: Too much parameters for the function lowercase(), only one is required.");
                }
                return strtolower($params[0]);

            case 'uppercase':
                if ($params === false) {
                    throw new QueryException("JSONDB Query Parse Error: There is no parameters for the function uppercase(). Can't execute the query.");
                }
                if (count($params) > 1) {
                    throw new QueryException("JSONDB Query Parse Error: Too much parameters for the function uppercase(), only one is required.");
                }
                return strtoupper($params[0]);

            case 'ucfirst':
                if ($params === false) {
                    throw new QueryException("JSONDB Query Parse Error: There is no parameters for the function ucfirst(). Can't execute the query.");
                }
                if (count($params) > 1) {
                    throw new QueryException("JSONDB Query Parse Error: Too much parameters for the function ucfirst(), only one is required.");
                }
                return ucfirst($params[0]);

            case 'strlen':
                if ($params === false) {
                    throw new QueryException("JSONDB Query Parse Error: There is no parameters for the function strlen(). Can't execute the query.");
                }
                if (count($params) > 1) {
                    throw new QueryException("JSONDB Query Parse Error: Too much parameters for the function strlen(), only one is required.");
                }
                return strlen($params[0]);

            default:
                throw new QueryException("JSONDB Query Parse Error: Sorry but the function {$name}() is not implemented in JQL.");
        }
    }

    /**
     * Parses a value
     *
     * It will convert (cast if necessary) a value
     * to its true type.
     *
     * @param string $value
     * @return bool|int|string|null
     */
    protected function _parseValue(string $value)
    {
        $trim_value = trim($value);
        if (strpos($value, ':JSONDB::TO_BOOL:') !== false) {
            return bool_val(int_val(str_replace(':JSONDB::TO_BOOL:', '', $value)));
        } elseif (strtolower($value) === 'false') {
            return false;
        } elseif (strtolower($value) === 'true') {
            return true;
        } elseif (strpos($value, ':JSONDB::TO_NULL:') !== false || strtolower($value) === 'null') {
            return null;
        } elseif (strpos($value, ':JSONDB::TO_ARRAY:') !== false) {
            return (array)unserialize($this->_parseValue(str_replace(':JSONDB::TO_ARRAY:', '', $value)));
        } elseif ($trim_value[0] === "'" && $trim_value[strlen($trim_value) - 1] === "'") {
            return str_val(str_replace(array('{{quot}}', '{{comm}}', '{{dot}}', '{{pto}}', '{{ptc}}', '{{semi}}'), array('\'', ',', '.', '(', ')', ';'), trim($value, self::TRIM_CHAR)));
        } elseif (preg_match('/\w+\(.*\)/', $value)) {
            return $this->_parseFunction($value);
        } else {
            return int_val(trim($value, self::TRIM_CHAR));
        }
    }
}