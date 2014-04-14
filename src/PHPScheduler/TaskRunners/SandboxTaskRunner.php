<?php

namespace PHPScheduler\TaskRunners;

use PHPScheduler\ITaskRunner;
use PHPScheduler\ITask;
use PHPScheduler\TaskException;
use PHPScheduler\PHPSchedulerException;

/**
 * 
 */
class SandboxTaskRunner implements ITaskRunner
{
    protected $runnerScriptPath;
    protected $bootstrappers;

    public function __construct($bootstrappers = [], $basepath = '/tmp')
    {
        $this->bootstrappers = $bootstrappers;
        $this->runnerScriptPath = rtrim($basepath).'/runner_'.uniqid().'.php';
    }

    /**
     * Run $task
     * @param  ITask  $task
     * @return mixed return value of task run
     */
    public function runTask(ITask $task)
    {
        //serialize
        $taskData = serialize($task);
        //run script
        $stdout = $this->runScript($taskData);
        //unserialize output
        $data = unserialize($stdout);

        $result = $data['result'];
        $exceptionData = $data['exception'];
        $stdout = $data['stdout'];
        //relay runner's stdout
        if (!empty($stdout)) {
            print $stdout;
        }
        //raise exceptions from runner
        if ($exceptionData) {
            //reconstruct a proxy exception we can throw
            $e = self::array2exception($exceptionData);
            throw $e;
        }
        //return result
        return $result;
    }

    /**
     * Return stdout from script
     * @param  string $stdin string to sent to stdin
     * @return string        data from stdout
     */
    protected function runScript($stdin)
    {
        $descriptorspec = [
            ['pipe', 'r'],
            ['pipe', 'w'],
            ['pipe', 'w'],
        ];

        //install script
        $this->installRunnerScript();

        //current working dir is the dir of installed script
        $cwd = dirname($this->runnerScriptPath);
        //invoke process
        $proc = proc_open("php {$this->runnerScriptPath}", $descriptorspec, $pipes, $cwd);

        if (is_resource($proc)) {
            //success, write the task to the process stdin
            fwrite($pipes[0], $stdin);
            //and close stdin
            fclose($pipes[0]);

            $stdout = "";
            $stderr = "";

            $r = $reads = [$pipes[1], $pipes[2]];
            $w = null;
            $e = null;
            while ($numStreams = stream_select($r, $w, $e, 3)) {
                foreach ($r as $input => $fd) {
                    if ($fd === $pipes[1]) {
                        $line = fgets($pipes[1]);
                        if (strlen($line) === 0) {
                            //END OF FILE, remove from read array
                            $i = array_search($pipes[1], $reads);
                            unset($reads[$i]);
                            //and continue loop
                            continue;
                        } else {
                            //append line to
                            $stdout .= $line;
                        }
                    } else {
                        $line = fgets($pipes[2]);
                        if (strlen($line) === 0) {
                            //END OF FILE, remove from read array
                            $i = array_search($pipes[2], $reads);
                            unset($reads[$i]);
                            //and continue loop
                            continue;
                        } else {
                            //append line to
                            $stderr .= $line;
                        }
                    }
                }

                if (count($reads) > 0) {
                    $r = $reads;
                } else {
                    break;
                }
            }
            //close stdout and stderr
            fclose($pipes[1]);
            fclose($pipes[2]);
            $returnCode = proc_close($proc);

            //remove runner script again
            $this->removeRunnerScript();

            //check for stderr (not allowed)
            if (!empty($stderr)) {
                throw new PHPSchedulerException('Sandbox stderr:'.$stderr);
            }

            //return stdout
            return $stdout;
        } else {
            //remove runner script again
            $this->removeRunnerScript();
            //if we didnt get a process
            throw new PHPSchedulerException('Could not create new PHP process for sandboxing');
        }
    }

    /**
     * Write runnerscript to disk, ready to be run
     * @return [type] [description]
     */
    protected function installRunnerScript()
    {
        $script = $this->generateSandboxScript($this->bootstrappers);
        file_put_contents($this->runnerScriptPath, $script);
    }

    /**
     * Remove runner script from disk
     * @return void
     */
    protected function removeRunnerScript()
    {
        unlink($this->runnerScriptPath);
    }

    /**
     * Return a PHP script that recieves task via STDIN and sends result on STDOUT
     * @param  array $bootstrapScripts array of absolute filepaths to bootstrapping scripts
     *                                 like your autoloaders.
     * @return string                  runner script.
     */
    protected function generateSandboxScript($bootstrapScripts = [])
    {
        $requireString = "";
        foreach ($bootstrapScripts as $fileName) {
            $requireString .= "require_once ('{$fileName}');\n";
        }
        //We embed the runner script directly. It is easier.
        return <<<EOL
<?php
        /*
            This is an autogenerated file used to run tasks
            for the SandboxTaskRunner
         */
        //autoloaders etc.
        $requireString
        //invoke runner
        print \\PHPScheduler\\TaskRunners\\SandboxTaskRunner::__runner();
EOL;
    }

    /**
     * Runner helper. Call from inside sandbox
     * @return string runner output, used to signal invoker
     */
    public static function __runner()
    {
        ob_start(); //start output buffering, since we need the stdout for com.
                    //with host script

        //load task from stdin
        $serializedTask = '';
        while (false !== ($line = fgets(STDIN))) {
            $serializedTask .= $line;
        }
        //unserialize task
        $task = unserialize($serializedTask);

        //run task, catch any exceptions
        $result = null;
        $exc = null;

        try {
            $result = $task->run();
        } catch (\Exception $e) {
            //There is no easy way to serialize an exception reliably, so
            //we just don't. Just send the important parts.
            //If dev needs more detail, dev should implement logging
            //from task itself.
            $exc = self::exception2array($e);
        }

        //return data for invoker
        $output = serialize(
            [
                'result' => $result,
                'exception' => $exc,
                'stdout' => ob_get_contents(),
            ]
        );
        //stop OB
        ob_end_clean();
        //return output to caller
        return $output;
    }

    /**
     * "Serialize" an exception. Since we can't correctly serialize an exception
     * we just pack it in a nice array, easy to print out in parent process and debug.
     * You should implement exception handling on the Task side of things.
     * This is just for debugging those cases that falls through.
     * @param  Exception $exc exception to pack
     * @return array          assoc array to be serialized and sent to parent process
     */
    protected static function exception2array($e)
    {
        return [
            'class' => get_class($e),
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            //to string the trace here, since it could contain closured
            //that serialize really horribly (as in, they don't serialize at all)
            'trace_string' => $e->getTraceAsString(),
            //and recurse into previous
            'previous' => is_null($e->getPrevious()) ? null : self::exception2array($e->getPrevious()),
        ];
    }

    /**
     * Wraps an exception array from the sandbox in a TaskException for throwing
     * @param  array $data array from exception2array static method
     * @return Exception       proxy exception to be thrown
     */
    protected static function array2exception($data)
    {
        //ignores previous for now
        return new TaskException(sprintf(
            "Proxy exception from sandbox: %s - %s\n%s:%d\nTrace:\n%s",
            $data['class'],
            $data['message'],
            $data['file'],
            $data['line'],
            $data['trace_string']
        ), $data['code']);
    }
}
