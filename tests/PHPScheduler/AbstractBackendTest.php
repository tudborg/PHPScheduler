<?php
namespace PHPScheduler;


abstract class AbstractBackendTest extends \PHPUnit_Framework_TestCase
{

    protected function _testBackend($backend)
    {
        $this->_test1($backend);
        $this->_test2($backend);
    }

    protected function _test1($backend)
    {
        //create dummy tasks
        $original_t1 = new Tasks\EchoTask('t1');
        $original_t2 = new Tasks\EchoTask('t2');
        $original_t3 = new Tasks\EchoTask('t3');
        
        //try to retrieve without anything in queue
        $noTask = $backend->retrieve();
        $this->assertNull($noTask);
        
        //schedule tasks
        $this->assertTrue($backend->schedule($original_t2, microtime(true) - 1));
         //schedule t2 first, in the past
        $this->assertTrue($backend->schedule($original_t3, microtime(true)));
         //and t3 now
        $this->assertTrue($backend->schedule($original_t1, microtime(true) - 2));
         //and t1 before any of the above
        //our set should now be sorted as
        //t1, t2, t3
        
        //retrieve first task, should be t1
        $t1 = $backend->retrieve();
        
        //retrieve second task, should be t2
        $t2 = $backend->retrieve();
        
        //And third
        $t3 = $backend->retrieve();
        
        $this->assertEquals('t1', $t1->run());
        $this->assertEquals('t2', $t2->run());
        $this->assertEquals('t3', $t3->run());
    }

    protected function _test2($backend)
    {
        $tasks = [];
        $taskCount = 100;
        // create a few tasks
        for ($i=0; $i < $taskCount; $i++) { 
            $tasks[] = new Tasks\EchoTask($i);
        }

        //try to retrieve without anything in queue
        $noTask = $backend->retrieve();
        $this->assertNull($noTask);

        // schedule them in the order they apear
        for ($i=0; $i < $taskCount; $i++) {
            // the - 10 part is to make sure that all tasks should be run _now_
            $this->assertTrue($backend->schedule($tasks[$i], microtime(true) - 10 + ($i/1000) ));
        }

        // now for each tasks in that order,
        // assert that the task retrieved from the backend is in same order.
        $output = [];
        foreach ($tasks as $task) {
            $retrived = $backend->retrieve();
            $this->assertEquals($task->serialize(), $retrived->serialize());
            $output[] = $retrived->run();
        }

        // assert that the order is in fact correct by checkout output of echo tasks
        $prevValue = -1;
        foreach ($output as $value) {
            $this->assertGreaterThan($prevValue, $value);
            $prevValue = $value;
        }
    }

}