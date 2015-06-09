<?php
namespace PHPScheduler;

class MysqlBackendTest extends AbstractBackendTest
{
    protected $dbName = null;
    protected $dbTable = null;
    protected $dbUser = null;
    protected $dbPassword = null;
    protected $dsn = null;
    protected $pdo = null;

    public function setUp()
    {
        $this->dbName = getenv('MYSQL_DB_NAME') ? getenv('MYSQL_DB_NAME') : 'dbtasks';
        $this->dbTable = getenv('MYSQL_TABLE_NAME') ? getenv('MYSQL_TABLE_NAME') : 'tasks';
        $this->dbUser = getenv('MYSQL_USER') ? getenv('MYSQL_USER') : 'root';
        $this->dbPassword = getenv('MYSQL_PASSWORD') ? getenv('MYSQL_PASSWORD') : 'root';
        $this->dsn = getenv('MYSQL_DSN') ? getenv('MYSQL_DSN') : "mysql:host=127.0.0.1;dbname={$this->dbName};charset=utf8"; // mysql:host=127.0.0.1;dbname=tasks

        try {
            $this->pdo = new \PDO($this->dsn, $this->dbUser, $this->dbPassword);
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            if ($e->getCode() == 1049) {
                // unknown database, explicitly fail.
                $this->fail("Connected to MySQL database at {$this->dsn} but database {$this->dbName} does not exist.");
            }
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