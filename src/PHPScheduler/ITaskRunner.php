<?php

namespace PHPScheduler;

interface ITaskRunner
{
    /**
     * Run $task
     * @param  ITask  $task
     * @return mixed _CAN_ return the return value of the task
     */
    public function runTask(ITask $task);
}
