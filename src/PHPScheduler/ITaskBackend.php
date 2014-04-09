<?php

namespace PHPScheduler;

/**
 * A TaskBackend object is responsible for all communication
 * to whatever backend is used for scheduling (like Redis)
 */
interface ITaskBackend
{
    /**
     * Schedule a task on this backend, optionally with a timestamp
     * of when to run. A task reference is returned
     * @param  ITask  $task
     * @param  int    $runAt
     * @return boolean
     */
    public function schedule(ITask $task, $runAt = null);

    /**
     * Retrieve a task from the scheduler, or null if no tasks exist
     * @return ITask
     */
    public function retrieve();
}
