<?php
namespace PHPScheduler;


class RedisBackendTest extends AbstractBackendTest
{
    private $redis;
    private $host;

    public function setUp()
    {
        $this->host = $host = getenv('REDIS_HOST') ? getenv('REDIS_HOST') : '127.0.0.1';
        if (!class_exists("\\Redis")) {
            $this->markTestIncomplete("Class \\Redis does not exist.");
        } else {
            $this->redis = new \Redis();
            $connected = $this->redis->connect($host);
            if (!$connected) {
                $this->markTestIncomplete("Could not connect to Redis at {$host}");
            }
        }

    }

    public function testRedisBackend()
    {
        $queueName = 'test_queue';
        $redis = $this->redis;

        //ensure that we start with a fresh empty key
        $redis->delete($queueName);
        
        //create redis backend
        $backend = new TaskBackends\RedisBackend($redis, $queueName);

        $this->_testBackend($backend);

        //assert that we are again back to 0 in queue
        $this->assertEquals(0, $redis->zSize($queueName));
    }
}