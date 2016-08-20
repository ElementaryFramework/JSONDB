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
     * Class Configuration
     *
     * @package     JSONDB
     * @subpackage  Utilities
     * @category    Configuration
     * @author      Nana Axel
     */
    class Configuration
    {
        /**
         * Adds a user in the inner configuration file
         * @param string $server   The path to the server
         * @param string $username The username
         * @param string $password The user's password
         */
        public function addUser($server, $username, $password)
        {
            $this->_writeConfig('users', array_merge($this->getConfig('users'), array($server => array('username' => sha1(md5($username)), 'password' => sha1(md5($password))))));
        }

        /**
         * Gets a JSONDB configuration file
         * @param string $filename The config file's name
         * @return array
         */
        public function getConfig($filename)
        {
            if ($this->_exists($filename)) {
                return json_decode(file_get_contents(realpath( dirname(__DIR__) . "/config/{$filename}.json")), TRUE);
            } else {
                $this->_writeConfig($filename, array());
                return array();
            }
        }

        /**
         * Writes a config file
         * @param string $filename
         * @param array $config
         * @return bool
         */
        private function _writeConfig($filename, array $config)
        {
            return (bool)file_put_contents(realpath( dirname(__DIR__) . "/config/{$filename}.json"), json_encode($config));
        }

        /**
         * Checks if a configuration file exist
         * @param string $filename
         * @return bool
         */
        private function _exists($filename)
        {
            return file_exists(realpath( dirname(__DIR__) . "/config/{$filename}.json"));
        }
    }