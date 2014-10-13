<?php

namespace PHPScheduler\TaskBackends;

use \PHPScheduler\ITaskBackend;
use \PHPScheduler\ITask;


/**
 * Stores tasks in memory.
 * Obviously mostly useful for debugging.
 */
class MySQLBackend implements ITaskBackend
{
    protected $pdo;
    protected $database;
    protected $table;

    const QUERY_TABLE_CREATE = "
    CREATE TABLE IF NOT EXISTS tasks.scheduled_tasks (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `run_at` DATETIME NOT NULL,
        `serialized_task` BLOB NOT NULL,
        PRIMARY KEY (`id`));
    ";

    const QUERY_TABLE_REMOVE = "
    DROP TABLE IF EXISTS `%s`.`%s` ;
    ";

    const QUERY_TASK_INSERT = "
    INSERT INTO `%s`.`%s` (run_at, serialized_task) VALUES (NOW() + INTERVAL ? SECOND, ?);
    ";
    const QUERY_TASK_RETRIEVE_FIND = "
    SELECT id, serialized_task FROM `%s`.`%s`
    WHERE run_at <= NOW()
    ORDER BY run_at ASC
    LIMIT 1
    FOR UPDATE
    ";
    const QUERY_TASK_RETRIEVE_DELETE = "
    DELETE FROM `%s`.`%s` WHERE id = ?;
    ";

    /**
     * Construct a new MySQL storage backend.
     * It is assumed that the database used exists,
     * and that the PDO object have INSERT, DELETE and SELECT privs.
     * 
     * You can create the table by calling ->createTable()
     * 
     * @param $pdo \PDO a connected PDO object
     * @param $database string name of database used for task storage.
     * @param $table string name of table used to store tasks.
     */
    public function __construct(\PDO $pdo, $database, $table)
    {
        $this->pdo = $pdo;
        $this->database = $database;
        $this->table = $table;
    }

    protected function setQueryTable($query)
    {
        return sprintf($query, $this->database, $this->table);
    }

    /**
     * Create database tables if not already present.
     * To do a clean install, call removeTables() first
     */
    public function createTable()
    {
        $prepared = $this->pdo->prepare($this->setQueryTable(self::QUERY_TABLE_CREATE));
        $ok = $prepared->execute();
        return $ok;
    }

    /**
     * Remove task table
     */
    public function removeTable()
    {
        $prepared = $this->pdo->prepare($this->setQueryTable(self::QUERY_TABLE_REMOVE));
        $ok = $prepared->execute();
        return $ok;
    }

    /**
     * @{@inheritdoc}
     */
    public function schedule(ITask $task, $runAt = null)
    {
        //if runAt is null, set it to run now
        if ($runAt === null) {
            $runAt = microtime(true);
        } else {
            assert(is_float($runAt) || is_int($runAt));
        }

        //we use diff from now to utilize the database' NOW() + diff
        //instead of setting it absolute.
        //this way, task schedulers and runners can time drift
        //without inpacting scheduling times
        $diff = ($runAt - microtime(true));

        $serialized = serialize($task);

        $prepared = $this->pdo->prepare($this->setQueryTable(self::QUERY_TASK_INSERT));

        $ok = $prepared->execute([
            $diff, $serialized
        ]);
        return $ok;
    }


    /**
     * @{@inheritdoc}
     */
    public function retrieve()
    {

        $this->pdo->beginTransaction();
        $findStmnt = $this->pdo->prepare($this->setQueryTable(self::QUERY_TASK_RETRIEVE_FIND));
        $findStmnt->execute();

        $findStmnt->bindColumn(1, $id);
        $findStmnt->bindColumn(2, $data);

        $success = $findStmnt->fetch(\PDO::FETCH_BOUND);

        if ($success === false) {
            //no row found, we can return early
            $this->pdo->commit();
            return null;
        } else {
            //we need to delete the row from the database now
            $deleteStmnt = $this->pdo->prepare($this->setQueryTable(self::QUERY_TASK_RETRIEVE_DELETE));
            $deleteStmnt->execute([$id]);
            $this->pdo->commit();

            $task = unserialize($data);

            return $task;
        }

    }
}
