<?php
namespace PHPScheduler;

if (class_exists("Redis")) {
    
    class RedisBackendTest extends \PHPUnit_Framework_TestCase
    {
        public function testRedisBackend()
        {
            $queueName = 'test_queue';
            
            $redis = new \Redis();
            $connected = $redis->connect('127.0.0.1');
            
            //assert that we are connected
            $this->assertTrue($connected);
            
            //ensure that we start with a fresh empty key
            $redis->delete($queueName);
            
            //create redis backend
            $backend = new TaskBackends\RedisBackend($redis, $queueName);
            
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
            
            //assert that we are again back to 0 in queue
            $this->assertEquals(0, $redis->zSize($queueName));
            
            $this->assertEquals('t1', $t1->run());
            $this->assertEquals('t2', $t2->run());
            $this->assertEquals('t3', $t3->run());
        }
    }
} else {
    print "\nNo \Redis class exists, skipping redis test\n\n";
}
