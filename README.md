PHPScheduler
============

A Scheduler / Task runner for PHP with plugable backends.
------------

_note: This is an experiment. You should probably not use this in production!_


Create tasks objects and schedule them to run somewhere else.
You can use PHPScheduler to schedule jobs to run in the future, or simply
schedule them to run right away.


Task storage is plugable, so you can choose where you want to store the tasks.
Currently, the following backends are planned (but you can easily add more):

### InMemory
_done_
Useful for debugging and task buffering.

### File
_done_
Running with a single server? No access to a database or Redis?
This one is for you. Goes nicely with a minutely cronjob for running the tasks.

### Redis
_done_
Use Redis as a task backend.
Useful if you have multiple servers (or task workers).

Implemented with redis' sorted sets

### MySQL
_done_
Store tasks in a MySQL table.
A lot slower than Redis, but gets the job done.

### SQLite (via PDO)
_pending_
Store tasks in a SQLite



Notes
-----------

The Redis and MySQL backends need serious testing, with multiple
schedulers and workers.
Havn't gotten to that part yet.
I don't recommend you use MySQL or Redis storage in production yet.
I'll tag a v1.0.0 when this is ready for use.




Usage
------------

### Example

#### Schedule script

This is the script that adds a task to the scheduler.

```php
<?php
// ... autoloader here

use \PHPScheduler\Scheduler;
use \PHPScheduler\TaskBackends\FileBackend;
use \PHPScheduler\Tasks\EchoTask;


//create a scheduler
$scheduler = new Scheduler(
    //Create a file backend.
    //The tasks will be stored locally on disk.
    new TaskBackends\FileBackend('/tmp/tasks')
);

//now we can add tasks to the scheduler.
$task = new EchoTask('hello world');

//lets schedule it 5 seconds into the future
$scheduler->schedule($task, microtime(true)+5);

//the closing tag is for the highlighter. You should never close your files.
?>
```

#### Worker script.

This is a script that reads from the queue and processes everything that is
pending, and then exits.
This could be triggered by a cron-job or similar.

```php
<?php
// ... autoloader here

use \PHPScheduler\Scheduler;
use \PHPScheduler\TaskBackends\FileBackend;

//create a scheduler. It should match the above, if you want to
//load tasks from it.
$scheduler = new Scheduler(
    //Create a file backend.
    //The tasks will be loaded from disk.
    new TaskBackends\FileBackend('/tmp/tasks')
);

//run() will run all pending tasks.
//If you run this script before the above 5 seconds have passed,
//nothing will happen, since no task is pending.
$scheduler->run();

//the closing tag is for the highlighter. You should never close your files.
?>
```


#### Long running worker

This script processes pending tasks, then waits a second, and repeats.

```php
<?php
// ... autoloader here

use \PHPScheduler\Scheduler;
use \PHPScheduler\TaskBackends\FileBackend;

//create a scheduler. It should match the above, if you want to
//load tasks from it.
$scheduler = new Scheduler(
    //Create a file backend.
    //The tasks will be loaded from disk.
    new TaskBackends\FileBackend('/tmp/tasks')
);

while (true) {
    $scheduler->run();
    sleep(1);
}

//the closing tag is for the highlighter. You should never close your files.
?>
```

Now, using a long running script is nice if your environment allows it.
To avoid memory leaks from your tasks, you can sandbox the execution of
each task with a custom task runner:

```php
<?php
// ... autoloader here

use \PHPScheduler\Scheduler;
use \PHPScheduler\TaskBackends\FileBackend;
use \PHPScheduler\TaskRunners\SandboxTaskRunner;

//create a scheduler. It should match the above, if you want to
//load tasks from it.
$scheduler = new Scheduler(
    //Create a file backend.
    //The tasks will be loaded from disk.
    new TaskBackends\FileBackend('/tmp/tasks'),
    //this time we also define a custom task runner.
    //The sandbox runner generates a script for each task
    //and executes it in it's own process.
    new TaskRunners\SandboxTaskRunner([
        //we have to specify how to get a hold of the autoloader,
        //to be able to load stuff from our codebase.
        dirname(__DIR__) . "/vendor/autoloader.php" //or something like this
    ])
);

//forever
while (true) {
    $scheduler->run();
    sleep(1);
}

//the closing tag is for the highlighter. You should never close your files.
?>
```



Backends
-----------

### Redis
```php
<?php
// ... autoloader here

use \PHPScheduler\Scheduler;
use \PHPScheduler\TaskBackends\RedisBackend;
use \PHPScheduler\Tasks\EchoTask;


$redis = new \Redis();
$redis->connect('127.0.0.1');

//create a scheduler
$scheduler = new Scheduler(
    //Create a Redis backend.
    //The tasks will be stored in redis.
    new RedisBackend($redis, "my_task_queue")
);

//now we can add tasks to the scheduler.
$task = new EchoTask('hello world');
//lets schedule it 5 seconds into the future
$scheduler->schedule($task, microtime(true)+5);

//the closing tag is for the highlighter. You should never close your files.
?>
```


### MySQL
```php
<?php
// ... autoloader here

use \PHPScheduler\Scheduler;
use \PHPScheduler\TaskBackends\MySQLBackend;
use \PHPScheduler\Tasks\EchoTask;


$pdo = new \PDO($dsn, $user, $pass); //your PDO here


//create a scheduler
$scheduler = new Scheduler(
    //Create a MySQL backend.
    //The tasks will be stored in the MySQL database table given
    new MySQLBackend($pdo, "my_db_name", "my_scheduled_tasks_table")
);

//now we can add tasks to the scheduler.
$task = new EchoTask('hello world');
//lets schedule it 5 seconds into the future
$scheduler->schedule($task, microtime(true)+5);

//the closing tag is for the highlighter. You should never close your files.
?>
```

