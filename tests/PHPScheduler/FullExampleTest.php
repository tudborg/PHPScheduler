<?php

namespace PHPScheduler;

class SchedulerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test the Scheduler and FileTaskBackend
     */
    public function testSchedulerFileTaskBackend()
    {
        $backendPath = '/tmp/PHPSchedulerTasks/'.uniqid();
        mkdir($backendPath);

        $str = 'hello world';
        //create our demo task
        $task = new Tasks\EchoTask($str);
        
        $scheduler = new Scheduler(
            new TaskBackends\FileTaskBackend($backendPath)
        );

        //schedule it
        $this->assertTrue($scheduler->schedule($task));
        //and retrieve it
        $retrievedTask = $scheduler->retrieve();
        //run the task, to be sure that it runs as expected
        $this->assertEquals($task->run(), $retrievedTask->run());
        //and clean backend path, it should be empty since we removed all tasks
        rmdir($backendPath);
    }

    /**
     * Test the Scheduler and InMemoryBackend
     */
    public function testSchedulerInMemoryBackend()
    {
        //create our demo task
        $task = new Tasks\EchoTask('hello world');
        $scheduler = new Scheduler(
            new TaskBackends\InMemoryBackend()
        );
        //do the dance
        $this->assertTrue($scheduler->schedule($task));
        $retrievedTask = $scheduler->retrieve();
        $this->assertEquals($task->run(), $retrievedTask->run());
    }

    /**
     * @expectedException     \PHPScheduler\TaskException
     */
    public function testSchedulerDirectTaskRunner()
    {
        $scheduler = new Scheduler(
            new TaskBackends\InMemoryBackend(),
            new TaskRunners\DirectTaskRunner()
        );

        $task = new Tasks\ExceptionTask('\\Exception', 'hello');
        //schedule task
        $scheduler->schedule($task);
        //try to run next task
        $result = $scheduler->runNextTask();

    }

    /**
     */
    public function testSchedulerSandboxtTaskRunner()
    {
        $scheduler = new Scheduler(
            new TaskBackends\InMemoryBackend(),
            new TaskRunners\SandboxTaskRunner([
                //we have to specify how to get a hold of the autoloader
                //(here embedded inside our test bootstrapper)
                //to reconstruct the serialized task
                dirname(__DIR__) . "/bootstrap.php"
            ])
        );

        $input = 'hello';

        $task = new Tasks\EchoTask($input);
        //schedule task
        $scheduler->schedule($task);
        //try to run next task
        $output = $scheduler->runNextTask();
        //assert that inputting $input with EchoTask always
        //return $output that is equal to $input.
        $this->assertEquals($input, $output);
    }


    /**
     * expect thrown exception should be reraised in this script, even
     * when run inside a sandbox.
     * @expectedException     \PHPScheduler\PHPSchedulerException
     */
    public function testSchedulerSandboxtTaskRunnerException()
    {
        $scheduler = new Scheduler(
            new TaskBackends\InMemoryBackend(),
            new TaskRunners\SandboxTaskRunner([
                //we have to specify how to get a hold of the autoloader
                //(here embedded inside our test bootstrapper)
                //to reconstruct the serialized task
                dirname(__DIR__) . "/bootstrap.php"
            ])
        );
        $task = new Tasks\ExceptionTask('\\PHPScheduler\\PHPSchedulerException', 'test');
        //schedule task
        $scheduler->schedule($task);
        //try to run next task
        $output = $scheduler->runNextTask();
    }
}
