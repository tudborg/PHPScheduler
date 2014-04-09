<?php

namespace PHPScheduler;


/**
 * The scheduler binds the task backend and task runner together
 */
class Scheduler
{
    protected $taskBackend;
    protected $taskRunner;

    public function __construct(
        ITaskBackend $taskBackend,
        ITaskRunner $taskRunner = null
    ) {
        //check if we need to default to a DirectTaskRunner
        if ($taskRunner === null) {
            $taskRunner = new TaskRunners\DirectTaskRunner();
        }

        $this->taskBackend = $taskBackend;
        $this->taskRunner = $taskRunner;
    }

    /**
     * Schedule a task on this backend, optionally with a timestamp
     * of when to run. A task reference is returned
     * @param  ITask  $task
     * @param  int    $runAt
     * @return  boolean true on success, else false.
     */
    public function schedule(ITask $task, $runAt = null)
    {
        return $this->taskBackend->schedule($task, $runAt);
    }

    /**
     * Retrieve a task from the scheduler, or null if no tasks exist
     * @return ITask
     */
    public function retrieve()
    {
        return $this->taskBackend->retrieve();
    }

    /**
     * Run next task
     * @return mixed result from task, or false if no task
     */
    public function runNextTask()
    {
        //try fetch task
        $task = $this->retrieve();
        if ($task === null) {
           //if no task, return false
            return false;
        } else {
            return $this->taskRunner->runTask($task);
        }
    }
}
