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
        self::$database->createServer('__phpunit_test_server', '__phpunit', '', TRUE);
        self::$database->createDatabase('__phpunit_test_database')->setDatabase('__phpunit_test_database');
        self::$database->createTable('__phpunit_test_table', array('php' => array('type' => 'string'), 'unit' => array('type' => 'int')));
        self::$database->disconnect();
    }

    /**
     * @expectedException \JSONDB\Exception
     */
    public function testExceptionIsRaisedForExistingServer()
    {
        self::$database->createServer('__phpunit_test_server', '__phpunit', '', TRUE);
    }

    /**
     * @expectedException \JSONDB\Exception
     */
    public function testExceptionIsRaisedForNonExistingServer()
    {
        self::$database->connect('NotExistingServerTest', '__phpunit', '');
    }

    /**
     * @expectedException \JSONDB\Exception
     */
    public function testExceptionIsRaisedForInvalidUser()
    {
        self::$database->connect('__phpunit_test_server', 'InvalidUser', '');
    }

    /**
     * @expectedException \JSONDB\Exception
     */
    public function testExceptionIsRaisedForInvalidPassword()
    {
        self::$database->connect('__phpunit_test_server', '__phpunit', 'InvalidPassword');
    }

    /**
     * @expectedException \JSONDB\Exception
     */
    public function testExceptionIsRaisedForBadDatabaseConnection()
    {
        self::$database->disconnect();
        self::$database->createDatabase('__phpunit_test_database');
    }

    /**
     * @expectedException \JSONDB\Exception
     */
    public function testExceptionIsRaisedForExistingDatabase()
    {
        self::$database->disconnect();
        self::$database->connect('__phpunit_test_server', '__phpunit', '');
        self::$database->createDatabase('__phpunit_test_database');
    }

    /**
     * @expectedException \JSONDB\Exception
     */
    public function testExceptionIsRaisedForBadDatabaseConnection2()
    {
        self::$database->disconnect();
        self::$database->createTable('__phpunit_test_table', array('php' => array('type' => 'string'), 'unit' => array('type' => 'int')));
    }

    /**
     * @expectedException \JSONDB\Exception
     */
    public function testExceptionIsRaisedForExistingTable()
    {
        self::$database->disconnect();
        self::$database->connect('__phpunit_test_server', '__phpunit', '', '__phpunit_test_database');
        self::$database->createTable('__phpunit_test_table', array('php' => array('type' => 'string'), 'unit' => array('type' => 'int')));
    }

    public function testForInsertQuery()
    {
        self::$database->disconnect();
        self::$database->connect('__phpunit_test_server', '__phpunit', '', '__phpunit_test_database');
        $bool = self::$database->query('__phpunit_test_table.insert(\'hello\', 0)');
        $this->assertTrue($bool);
    }

    public function testForUpdateQuery()
    {
        self::$database->disconnect();
        self::$database->connect('__phpunit_test_server', '__phpunit', '', '__phpunit_test_database');
        $bool = self::$database->query('__phpunit_test_table.update(php, unit).with(\'world\', 1)');
        $this->assertTrue($bool);
    }

    public function testForReplaceQuery()
    {
        self::$database->disconnect();
        self::$database->connect('__phpunit_test_server', '__phpunit', '', '__phpunit_test_database');
        $bool = self::$database->query('__phpunit_test_table.replace(php, unit).with(\'nice\', 2)');
        $this->assertTrue($bool);
    }

    public function testForSelectQuery()
    {
        self::$database->disconnect();
        self::$database->connect('__phpunit_test_server', '__phpunit', '', '__phpunit_test_database');
        $r = self::$database->query('__phpunit_test_table.select(php, unit)');
        $this->assertInstanceOf('\JSONDB\\QueryResult', $r);
    }
}