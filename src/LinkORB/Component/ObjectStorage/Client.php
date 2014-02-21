<?php

namespace LinkORB\Component\ObjectStorage;

use RuntimeException;

class Client
{
    private $driver;
    private $prefix;

    public function __construct($driver, $prefix = '')
    {
        $this->driver = $driver;
        $this->prefix = $prefix;
    }

    public function get($key)
    {
        return $this->driver->get($this->prefix . $key);
    }

    public function set($key, $data)
    {
        $this->driver->set($this->prefix . $key, $data);
    }

    public function delete($key)
    {
        $this->driver->delete($this->prefix . $key);
    }

    public function upload($key, $filename)
    {
        if (!file_exists($filename)) {
            throw new RuntimeException("File not found: " . $filename);
        }

        $data = file_get_contents($filename);
        $this->set($this->prefix . $key, $data);
    }

    public function download($key, $filename)
    {
        $data = $this->get($this->prefix . $key);
        file_put_contents($filename, $data);
    }

}