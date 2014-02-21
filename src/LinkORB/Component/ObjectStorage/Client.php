<?php

namespace LinkORB\Component\ObjectStorage;

use RuntimeException;

class Client
{
    private $driver;

    public function __construct($driver)
    {
        $this->driver = $driver;
    }

    public function get($key)
    {
        return $this->driver->get($key);
    }

    public function set($key, $data)
    {
        $this->driver->set($key, $data);
    }

    public function delete($key)
    {
        $this->driver->delete($key);
    }

    public function upload($key, $filename)
    {
        if (!file_exists($filename)) {
            throw new RuntimeException("File not found: " . $filename);
        }

        $data = file_get_contents($filename);
        $this->set($key, $data);
    }

    public function download($key, $filename)
    {
        $data = $this->get($key);
        file_put_contents($filename, $data);
    }

}