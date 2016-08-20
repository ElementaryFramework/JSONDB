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
     * Class Benchmark
     *
     * @package		JSONDB
     * @subpackage  Utilities
     * @category    Benchmark
     * @author		Nana Axel
     */
    class Benchmark {

        /**
         * The benchmark
         * @var array
         * @access private
         */
        private static $marker = array( ) ;

        /**
         * Add a benchmark point.
         * @param  string  $name  The name of the benchmark point
         * @return void
         */
        public function mark( $name ) {
            self::$marker[$name] = microtime( ) ;
        }

        /**
         * Calculate the elapsed time between two benchmark points.
         * @param  string  $point1    The name of the first benchmark point
         * @param  string  $point2    The name of the second benchmark point
         * @param  int     $decimals
         * @return mixed
         */
        public function elapsed_time( $point1 = '', $point2 = '', $decimals = 4 ) {
            if ( $point1 === '' ) {
                return '{elapsed_time}' ;
            }
            if ( !array_key_exists( $point1, self::$marker )) {
                return '' ;
            }
            if ( !array_key_exists( $point2, self::$marker )) {
                self::$marker[$point2] = microtime( ) ;
            }
            list( $sm, $ss ) = explode( ' ', self::$marker[$point1] ) ;
            list( $em, $es ) = explode( ' ', self::$marker[$point2] ) ;

            return number_format( ( $em + $es ) - ( $sm + $ss ), $decimals ) ;
        }

        /**
         * Calculate the memory usage of a benchmark point
         * @return int
         */
        public function memory_usage( ) {
            return memory_get_usage();
        }

    }
