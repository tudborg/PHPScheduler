<?php
namespace PHPScheduler;

class MysqlBackendTest extends AbstractBackendTest
{
    protected $dbName = "tasks";
    protected $dbTable = "scheduled_tasks";
    protected $dsn = "mysql:host=127.0.0.1;dbname=tasks";
    protected $pdo = null;

    public function setUp()
    {
        try {
            $this->pdo = new \PDO($this->dsn, "root", "password");
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            $this->markTestIncomplete(
              'Could not connect to MySQL on '.$this->dsn
            );
        }
    }

    public function testMysqlBackend()
    {
        //create redis backend
        $backend = new TaskBackends\MySQLBackend($this->pdo, $this->dbName, $this->dbTable);
        
        //create the task table
        $this->assertTrue($backend->createTable());

        try {
            $this->_testBackend($backend);
        } finally {
            //and remove it again
            $backend->removeTable();
        }

    }
}