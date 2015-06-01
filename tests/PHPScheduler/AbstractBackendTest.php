<?php
namespace PHPScheduler;


abstract class AbstractBackendTest extends \PHPUnit_Framework_TestCase
{

    protected function _testBackend($backend)
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

}