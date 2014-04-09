<?php

namespace PHPScheduler;

/**
 * The TaskException describes errors thrown inside a task run.
 * It overide the __tostring for a nice view of the $previous exception
 */
class TaskException extends PHPSchedulerException
{
    public function __tostring()
    {
        $previous = $this->getPrevious();
        if ($previous) {
            //we have a previous, custom formatting:
            return sprintf("%s (%s)\n%s", __CLASS__, get_class($previous), parent::__tostring());
        } else {
            //default tostring method
            return parent::__tostring();
        }
    }
}
