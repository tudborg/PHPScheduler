<?php

namespace PHPScheduler\TaskBackends;

use \PHPScheduler\ITaskBackend;
use \PHPScheduler\ITask;

/**
 * Stores tasks in memory.
 * Obviously mostly useful for debugging.
 */
class InMemoryBackend implements ITaskBackend
{
    protected $taskStorage;

    public function __construct()
    {
        $this->taskStorage = [];
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

        //store it
        $this->taskStorage[] = [$runAt, $task];

        //sort it
        usort($this->taskStorage, function ($a, $b) {
            if ($a[0] === $b[0]) {
                return 0;
            }
            return ($a[0] < $b[0]) ? -1 : 1;
        });

        return true;
    }


    /**
     * @{@inheritdoc}
     */
    public function retrieve()
    {
        if (count($this->taskStorage) > 0) {
            //filter array, leaving only tasks to be run
            $filtered = array_filter($this->taskStorage, function ($spec) {
                return $spec[0] <= microtime(true);
            });
            //get match
            $match = array_shift($filtered);
            if ($match === null) {
                //if no match, return null
                return null;
            } else {
                //else return task part of match
                return $match[1];
            }
        } else {
            return null;
        }

    }
}
