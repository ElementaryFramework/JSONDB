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

namespace ElementaryFramework\JSONDB\Utilities;

/**
 * Class Configuration
 *
 * @package  JSONDB
 * @category Utilities
 * @author   Axel Nana <ax.lnana@outlook.com>
 * @link     http://php.jsondb.na2axl.tk/docs/api/jsondb/utilities/configuration
 */
class Configuration
{
    /**
     * Removes a server from the list of registered server.
     *
     * @param string server The name of the server
     */
    public static function removeServer(string $server)
    {
        $config = self::getConfig("users");
        unset($config[$server]);

        self::_writeConfig("users", $config);
    }

    /**
     * Adds an user in the inner configuration file
     *
     * @param string $server   The path to the server
     * @param string $username The username
     * @param string $password The user's password
     */
    public static function addUser(string $server, string $username, string $password)
    {
        self::_writeConfig('users', array_merge(self::getConfig('users'), array($server => array('username' => Util::crypt($username), 'password' => Util::crypt($password)))));
    }

    /**
     * Removes an user in the inner configuration file
     *
     * @param string $server   The path to the server
     * @param string $username The username
     */
    public static function removeUser(string $server, string $username)
    {
        $config = self::getConfig("users");
        $i = 0;

        if (!array_key_exists($server, $config)) {
            return;
        }

        foreach ($config[$server] as $user) {
            if ($user["username"] === Util::crypt($username)) {
                unset($config[$server][$i]);
            }
            ++$i;
        }

        self::_writeConfig('users', $config);
    }

    /**
     * Gets a JSONDB configuration file
     *
     * @param string $filename The config file's name
     *
     * @return array
     */
    public static function getConfig(string $filename): array
    {
        if (self::_exists($filename)) {
            return json_decode(file_get_contents(realpath(dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "{$filename}.json")), true);
        } else {
            self::_writeConfig($filename, array());
            return array();
        }
    }

    /**
     * Writes a config file
     *
     * @param string $filename
     * @param array $config
     *
     * @return bool
     */
    private static function _writeConfig(string $filename, array $config): bool
    {
        return (bool)file_put_contents(realpath(dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "{$filename}.json"), json_encode($config));
    }

    /**
     * Checks if a configuration file exist
     *
     * @param string $filename
     *
     * @return bool
     */
    private static function _exists(string $filename): bool
    {
        return file_exists(realpath(dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "{$filename}.json"));
    }
}