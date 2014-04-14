<?php

namespace PHPScheduler\Tasks;

use PHPScheduler\ITask;

/**
 * Throws exception when run
 */
class ExceptionTask implements ITask
{
    protected $message;
    protected $exceptionFQN;

    public function __construct($exceptionFQN, $message = null)
    {
        $this->exceptionFQN = $exceptionFQN;
        $this->message = $message;
    }

    public function serialize()
    {
        return serialize([$this->exceptionFQN, $this->message]);
    }

    public function unserialize($str)
    {
        list($this->exceptionFQN, $this->message) = unserialize($str);
    }

    public function run()
    {
        //throw exception named whatever was passed when constructed.
        throw new $this->exceptionFQN($this->message);
    }

    public function __tostring()
    {
        return sprintf('<%s %s("%s")>', __CLASS__, $this->exceptionFQN, $this->message);
    }
}
