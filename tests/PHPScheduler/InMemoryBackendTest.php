<?php
namespace PHPScheduler;

class InMemoryBackendTest extends AbstractBackendTest
{
    public function testInMemoryBackend()
    {
        $backend = new TaskBackends\InMemoryBackend();
        $this->_testBackend($backend);
    }
}