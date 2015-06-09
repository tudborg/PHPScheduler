<?php

namespace PHPScheduler\Tasks;

use PHPScheduler\ITask;

/**
 * Returns whatever was stored when task was constructed
 */
class EchoTask implements ITask
{
    protected $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function serialize()
    {
        return serialize($this->message);
    }

    public function unserialize($str)
    {
        $this->message = unserialize($str);
    }

    public function run()
    {
        return $this->message;
    }
}
