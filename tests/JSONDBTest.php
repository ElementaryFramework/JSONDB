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
    }

    /**
     * @expectedException \JSONDB\Exception
     */
    public function testExceptionIsRaisedForNewServer()
    {
        self::$database->createServer('__phpunit_test_server', '__phpunit', '');
    }
    
}