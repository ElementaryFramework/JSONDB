<?php

use JSONDB\JSONDB;

class JSONDBTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \JSONDB\Database
     */
    private static $database;

    /**
     * @var \JSONDB\JSONDB
     */
    private static $jsondb;

    public static function setUpBeforeClass()
    {
        // create db
        self::$jsondb = new JSONDB();
        self::$database = self::$jsondb->createServer('__phpunit_test_server', '__phpunit', '', TRUE);
        self::$database->createDatabase('__phpunit_test_database')->setDatabase('__phpunit_test_database');
        self::$database->createTable('__phpunit_test_table_npk', array('php' => array('type' => 'string'), 'unit' => array('type' => 'int')));
        self::$database->createTable('__phpunit_test_table_pk', array('id' => array('auto_increment' => TRUE)));
        self::$database->disconnect();
    }

    /**
     * @expectedException \JSONDB\Exception
     */
    public function testExceptionIsRaisedForExistingServer()
    {
        self::$jsondb->createServer('__phpunit_test_server', '__phpunit', '', TRUE);
    }

    /**
     * @expectedException \JSONDB\Exception
     */
    public function testExceptionIsRaisedForNonExistingServer()
    {
        self::$jsondb->connect('NotExistingServerTest', '__phpunit', '');
    }

    /**
     * @expectedException \JSONDB\Exception
     */
    public function testExceptionIsRaisedForInvalidUser()
    {
        self::$jsondb->connect('__phpunit_test_server', 'InvalidUser', '');
    }

    /**
     * @expectedException \JSONDB\Exception
     */
    public function testExceptionIsRaisedForInvalidPassword()
    {
        self::$jsondb->connect('__phpunit_test_server', '__phpunit', 'InvalidPassword');
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
        self::$database = self::$jsondb->connect('__phpunit_test_server', '__phpunit', '');
        self::$database->createDatabase('__phpunit_test_database');
    }

    /**
     * @expectedException \JSONDB\Exception
     */
    public function testExceptionIsRaisedForBadDatabaseConnection2()
    {
        self::$database->disconnect();
        self::$database->createTable('__phpunit_test_table_npk', array('php' => array('type' => 'string'), 'unit' => array('type' => 'int')));
    }

    /**
     * @expectedException \JSONDB\Exception
     */
    public function testExceptionIsRaisedForExistingTable()
    {
        self::$database->disconnect();
        self::$database = self::$jsondb->connect('__phpunit_test_server', '__phpunit', '', '__phpunit_test_database');
        self::$database->createTable('__phpunit_test_table_npk', array('php' => array('type' => 'string'), 'unit' => array('type' => 'int')));
    }

    public function testForInsertQuery()
    {
        self::$database->disconnect();
        self::$database = self::$jsondb->connect('__phpunit_test_server', '__phpunit', '', '__phpunit_test_database');
        $bool = self::$database->query('__phpunit_test_table_npk.insert(\'hello\', 0)');
        $this->assertTrue($bool);
    }

    public function testForUpdateQuery()
    {
        self::$database->disconnect();
        self::$database = self::$jsondb->connect('__phpunit_test_server', '__phpunit', '', '__phpunit_test_database');
        $bool = self::$database->query('__phpunit_test_table_npk.update(php, unit).with(\'world\', 1)');
        $this->assertTrue($bool);
    }

    public function testForReplaceQuery()
    {
        self::$database->disconnect();
        self::$database = self::$jsondb->connect('__phpunit_test_server', '__phpunit', '', '__phpunit_test_database');
        $bool = self::$database->query('__phpunit_test_table_npk.replace(\'nice\', 2)');
        $this->assertTrue($bool);
    }

    public function testForSelectQuery()
    {
        self::$database->disconnect();
        self::$database = self::$jsondb->connect('__phpunit_test_server', '__phpunit', '', '__phpunit_test_database');
        $r = self::$database->query('__phpunit_test_table_npk.select(php, unit)');
        $this->assertInstanceOf('\JSONDB\\QueryResult', $r);
    }

    public function testForSelectQueryFetchObject()
    {
        self::$database->disconnect();
        self::$database = self::$jsondb->connect('__phpunit_test_server', '__phpunit', '', '__phpunit_test_database');
        $r = self::$database->query('__phpunit_test_table_npk.select(php, unit)')->fetch(JSONDB::FETCH_OBJECT);
        $this->assertInstanceOf('\JSONDB\\QueryResultObject', $r);
    }

    public function testForDeleteQuery()
    {
        self::$database->disconnect();
        self::$database = self::$jsondb->connect('__phpunit_test_server', '__phpunit', '', '__phpunit_test_database');
        $bool = self::$database->query('__phpunit_test_table_npk.delete()');
        $this->assertTrue($bool);
    }

    public function testForTruncateQuery()
    {
        self::$database->disconnect();
        self::$database = self::$jsondb->connect('__phpunit_test_server', '__phpunit', '', '__phpunit_test_database');
        $bool = self::$database->query('__phpunit_test_table_npk.truncate()');
        $this->assertTrue($bool);
    }

    public function testForMultipleInsertion()
    {
        self::$database->disconnect();
        self::$database = self::$jsondb->connect('__phpunit_test_server', '__phpunit', '', '__phpunit_test_database');
        $bool = self::$database->query('__phpunit_test_table_pk.insert(null).and(null).and(null)');
        $this->assertTrue($bool);
    }

    /**
     * @expectedException \JSONDB\Exception
     */
    public function testExceptionIsRaisedForDuplicatePKUKOnInsert() {
        self::$database->disconnect();
        self::$database = self::$jsondb->connect('__phpunit_test_server', '__phpunit', '', '__phpunit_test_database');
        self::$database->query('__phpunit_test_table_pk.insert(1)');
    }

    /**
     * @expectedException \JSONDB\Exception
     */
    public function testExceptionIsRaisedForDuplicatePKUKOnUpdate() {
        self::$database->disconnect();
        self::$database = self::$jsondb->connect('__phpunit_test_server', '__phpunit', '', '__phpunit_test_database');
        self::$database->query('__phpunit_test_table_pk.update(id).with(1)');
    }

    /**
     * @expectedException \JSONDB\Exception
     */
    public function testExceptionIsRaisedForDuplicatePKUKOnReplace() {
        self::$database->disconnect();
        self::$database = self::$jsondb->connect('__phpunit_test_server', '__phpunit', '', '__phpunit_test_database');
        self::$database->query('__phpunit_test_table_pk.replace(2)');
    }

    /**
     * @expectedException \JSONDB\Exception
     */
    public function testExceptionIsRaisedForFetchClassMode() {
        $r = new \JSONDB\QueryResult(array(array('testVar1' => 'foo', 'testVar2' => 'bar')), self::$database);
        $r->setFetchMode(\JSONDB\JSONDB::FETCH_CLASS, 'FakeClass');
        $r->fetch();
    }

    /**
     * @expectedException \JSONDB\Exception
     */
    public function testExceptionIsRaisedForFetchClassMode2() {
        $r = new \JSONDB\QueryResult(array(array('testVar1' => 'foo', 'testVar3' => 'bar')), self::$database);
        $r->setFetchMode(\JSONDB\JSONDB::FETCH_CLASS, 'TestClass');
        $r->fetch();
    }

    /**
     * @expectedException \JSONDB\Exception
     */
    public function testExceptionIsRaisedForFetchClassMode3() {
        $r = new \JSONDB\QueryResult(array(array('testVar1' => 'foo', 'testVar4' => 'bar')), self::$database);
        $r->setFetchMode(\JSONDB\JSONDB::FETCH_CLASS, 'TestClass');
        $r->fetch();
    }

    public function testFetchClassMode() {
        $r = new \JSONDB\QueryResult(array(array('testVar1' => 'foo', 'testVar2' => 'bar')), self::$database);
        $r->setFetchMode(\JSONDB\JSONDB::FETCH_CLASS, 'TestClass');
        $value = $r->current();
        $this->assertInstanceOf('TestClass', $value);
    }

    public function testFetchClassMode2() {
        $r = new \JSONDB\QueryResult(array(array('testVar1' => 'foo', 'testVar2' => 'bar')), self::$database);
        $r->setFetchMode(\JSONDB\JSONDB::FETCH_CLASS, 'TestClass');
        $value = $r->current();
        $expected = new TestClass();
        $expected->testVar1 = 'foo';
        $expected->testVar2 = 'bar';
        $this->assertEquals($expected, $value);
    }
}

class TestClass {
    public $testVar1;
    public $testVar2;
    private $testVar3;
}