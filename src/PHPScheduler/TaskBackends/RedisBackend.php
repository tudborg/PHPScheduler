<?php

namespace PHPScheduler\TaskBackends;

use \PHPScheduler\ITaskBackend;
use \PHPScheduler\ITask;

/**
 * Stores tasks in memory.
 * Obviously mostly useful for debugging.
 */
class RedisBackend implements ITaskBackend
{
    protected $redis;
    protected $queueName;

    public function __construct($redis, $queueName)
    {
        $this->redis = $redis;
        $this->queueName = $queueName;
    }

    /**
     * Generate something to use as unique token
     * @return string unique token
     */
    protected function genId()
    {
        return sprintf(
            '%011x%05x',
            (int)(microtime(true)*1000),
            mt_rand(0, 0xfffff)
        );
    }

    /**
     * Return time, formattet as a set score
     * Just a wrapper, if we decide to do something with the time at some point
     * @return string
     */
    protected function timeFormat($time)
    {
        return (string)($time*1000);
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

        //we need to add some uniqueness to the job
        //since we use a redis set. If we didn't, we could not do
        //dublicate tasks (and we want to!)
        $data = serialize([
            $this->genId(),
            $task
        ]);

        //store it
        $ret = $this->redis->zAdd(
            $this->queueName,
            $this->timeFormat($runAt),
            $data
        );

        return (bool)$ret;
    }


    /**
     * @{@inheritdoc}
     */
    public function retrieve()
    {
        $ret = $this->redis->zRangeByScore(
            $this->queueName,
            "-inf",
            $this->timeFormat(microtime(true)),
            [ 'limit' => [0, 1] ]
        );

        if (count($ret) > 0) {
            $data = $ret[0];
            //we got a value
            list($id, $task) = unserialize($data);
            //we have the task, remove from set
            $removed = $this->redis->zDelete($this->queueName, $data);
            //if removed, return task
            if ($removed === 1) {
                return $task;
            //else, someone else already removed it, call retrieve recursively
            } else {
                return $this->retrieve();
            }
        } else {
            return null;
        }
    }
}
