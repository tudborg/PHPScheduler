<?php

namespace PHPScheduler;

/**
 * A task is an object describing how to perform a specific task.
 * It must be serializable.
 */
interface ITask extends \Serializable
{
    /**
     * Run task, optionally return a value.
     * @return mixed
     */
    public function run();
}
