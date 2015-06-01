<?php
namespace PHPScheduler;


class RedisBackendTest extends AbstractBackendTest
{
    public function setUp()
    {
        if (!class_exists("\\Redis")) {
            $this->markTestIncomplete("Class \\Redis does not exist.");
        }
    }

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

        $this->_testBackend($backend);

        //assert that we are again back to 0 in queue
        $this->assertEquals(0, $redis->zSize($queueName));
    }
}