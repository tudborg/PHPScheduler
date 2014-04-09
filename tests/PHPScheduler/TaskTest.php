<?php

namespace PHPScheduler;

class TastTest extends \PHPUnit_Framework_TestCase
{
    public function testEchoTask()
    {
        $str = 'hello world';
        //create our demo task
        $task = new Tasks\EchoTask($str);
        //check that it is run correctly
        $this->assertEquals($str, $task->run());
        //now do the dance
        $task = unserialize(serialize($task));
        //check that it is still correct (and survived the serialization)
        $this->assertEquals($str, $task->run());
    }

    /**
     * @expectedException     \PHPScheduler\PHPSchedulerException
     */
    public function testExceptionTask()
    {
        $task = new Tasks\ExceptionTask('\\PHPScheduler\\PHPSchedulerException', 'hello from ExceptionTask');
        //now do the dance
        $task = unserialize(serialize($task));
        //go go
        $task->run();
    }
}
