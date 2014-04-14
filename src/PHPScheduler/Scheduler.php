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
     * @return mixed result from task
     */
    public function runNextTask()
    {
        $result = null;
        $this->run(function ($e) {
            throw $e;
        }, 1, $result);
        return $result;
    }

    /**
     * Run scheduled work
     * @param  callable $excHandler Optionally handle exceptions here.
     *                              If no handler is given, exceptions are ignored.
     *                              Handler is called with $excHandler(\Exception $e)
     * @param integer $max          Maximum number of tasks to run
     * @return integer              number of succesful tasks
     */
    public function run(callable $excHandler = null, $max = null, &$lastResult = null)
    {
        $s = 0;//success
        while ($max === null || (is_int($max) && $max-- > 0)) {//forever

            $task = $this->retrieve();
            if (!($task instanceof ITask)) {
                break;
            }

            try {
                //ignore output, since we have no way of storing it anyway.
                //If output is important, it should be handled by task
                $lastResult = $this->taskRunner->runTask($task);
                $s += 1;//success, count 1
            } catch (TaskException $e) {
                //error in task, hand the error to $excHandler if any
                if (is_callable($excHandler)) {
                    call_user_func($excHandler, $e);
                }
            }
        }
        return $s; //return number of successes.
    }
}
