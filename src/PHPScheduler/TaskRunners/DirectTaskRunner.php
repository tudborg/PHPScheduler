<?php

namespace PHPScheduler\TaskRunners;

use PHPScheduler\ITaskRunner;
use PHPScheduler\ITask;
use PHPScheduler\TaskException;

/**
 * The DirectTaskRunner runs - as it's name implies - a task directly in
 * the running script. It is pretty much just invoking $task->run() and
 * returning the result.
 * Depending on what you need, this might be fine. For leaky or long running
 * stuff, you might want to isolate the run in a seperate process.
 */
class DirectTaskRunner implements ITaskRunner
{
    /**
     * Run $task
     * @param  ITask  $task
     * @return mixed return value of task run
     */
    public function runTask(ITask $task)
    {
        try {
            return $task->run();
        } catch (\Exception $e) {
            throw new TaskException('Error in task: '.$e->getMessage(), $e->getCode(), $e);
        }
    }
}
