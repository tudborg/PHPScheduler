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
        $exception = $data['exception'];
        $stdout = $data['stdout'];

        //relay runner's stdout
        if (!empty($stdout)) {
            print $stdout;
        }
        //raise exceptions from runner
        if ($exception) {
            throw $exception;
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
        ob_start(); //start output buffering, since we need the stdout for com.
                    //with host script

        $requireString

        //load task from stdin
        \$serializedTask = '';
        while (FALSE !== (\$line = fgets(STDIN))) {
           \$serializedTask .= \$line;
        }

        //unserialize task
        \$task = unserialize(\$serializedTask);

        \$result = null;
        \$exc = null;
        try {
            \$result = \$task->run();
        } catch (\\Exception \$e) {
            \$exc = \$e;
        }
        \$output = serialize(
            [
                'result' => \$result,
                'exception' => \$exc,
                'stdout' => ob_get_contents(),
            ]
        );
        ob_end_clean();
        print \$output;
EOL;
    }
}
