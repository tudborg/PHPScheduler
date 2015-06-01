<?php
namespace PHPScheduler;

class FileBackendTest extends AbstractBackendTest
{
    protected $path;

    public function setUp()
    {
        $this->path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "FileBackendTest".time();
        if (!is_dir($this->path)) {
            assert(mkdir($this->path, 0777, true));
        }
    }

    public function tearDown()
    {
        rmdir($this->path);
    }

    public function testFileBackend()
    {
        $backend = new TaskBackends\FileBackend($this->path);
        $this->_testBackend($backend);
    }
}