<?php

namespace PHPScheduler\TaskBackends;

use \PHPScheduler\ITaskBackend;
use \PHPScheduler\ITask;

/**
 * Generate a (hopefully) unique id
 * @return string a 16 character hexadecimal string
 */
function genId()
{
    return sprintf(
        '%011x%05x',
        (int)(microtime(true)*1000),
        mt_rand(0, 0xfffff)
    );
}
/**
 * Store tasks on local filesystem
 */
class FileTaskBackend implements ITaskBackend
{

    protected $path;

    public function __construct($path = '/tmp/PHPSchedulerTasks')
    {
        //create dir
        if (!is_dir($path)) {
            assert(mkdir($path, 0777, true));
        }

        $this->path = $path;
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

        $id = genId();
        //create the data
        $filename = sprintf('%010.4f-%s.task', $runAt, $id);
        $data = serialize($task);

        //save it to disk
        $written = file_put_contents("{$this->path}/{$filename}", $data);
        
        return $written > 0;
    }


    /**
     * @{@inheritdoc}
     */
    public function retrieve()
    {
        $taskData = $this->fetchNextPendingTaskData();
        if ($taskData === false) {
            return null;
        } else {
            return unserialize($taskData);
        }
    }

    /**
     * Loads next pending serialized task data from disk, false on failure
     * @return string serialized task or false on failure
     */
    protected function fetchNextPendingTaskData()
    {
        //instead of while(true), we use a safeguard to avoid infinite loops.
        //100 max iterations should be plenty, even on a busy server.
        //You shound be careful using the FileTaskBackend anyway.
        $safeGuard = 100;
        while ($safeGuard-- > 0) {
            //get files
            $files = glob("{$this->path}/*.task");
            //sort files
            sort($files);
            //filter out not-yet-to-be-run tasks
            $files = array_filter($files, function ($file) {
                //we know files are named as "{timestamp}-{id}.task",
                //so we can get basename of file (removing the .task and path parts)
                //explode on '-', and unpack.
                list($timestamp, $id) = explode('-', basename($file));
                //include in result if timestamp is <= now
                return ((float)$timestamp) <= microtime(true);
            });
            //get first file if it exists, else no tasks
            if (count($files) > 0) {
                $taskData = file_get_contents($files[0]);
                //unlink it, if it fails, someone else already loaded it and we should try again
                if (unlink($files[0])) {
                    //task was successfully unlinked from disk, we are now
                    //the only one with the task data. Return to caller
                    return $taskData;
                } else {
                    //Someone else already unlinked that file.
                    //Do another iteration, looking for next available file.
                    continue;
                }
            } else {
                //count of files is not > 0, return with false
                return false;
            }
        }
        //the safeguard ran out. This is really bad. Throw an exception
        throw new \Exception(
            'FileTaskBackend have problems accessing tasks on filesystem.'
            .'Even if some tasks still complete, this will lead to extremely'
            .'poor performance. Consider using a different backend.'
        );
    }
}
