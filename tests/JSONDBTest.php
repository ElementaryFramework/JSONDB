<?php

use JSONDB\JSONDB;

class JSONDBTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var \JSONDB\JSONDB
     */
    private static $database;

    public static function setUpBeforeClass()
    {
        // create db
        self::$database = new JSONDB();
        self::$database->createServer('__phpunit_test_server', '__phpunit', '');
    }

    /**
     * @expectedException \JSONDB\Exception
     */
    public function testExceptionIsRaisedForNonExistingServer()
    {
        self::$database->connect('NotExistingServerTest', '__phpunit', '');
    }
    
}