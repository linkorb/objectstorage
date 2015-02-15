<?php

namespace ObjectStorage;

use RuntimeException;

class Service
{
    private $storageadapter;
    private $prefix;

    public function __construct($storageadapter)
    {
        $this->storageadapter = $storageadapter;
    }
    
    public function setKeyPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    public function get($key)
    {
        return $this->storageadapter->getData($this->prefix . $key);
    }

    public function set($key, $data)
    {
        $this->storageadapter->setData($this->prefix . $key, $data);
    }

    public function delete($key)
    {
        $this->storageadapter->deleteData($this->prefix . $key);
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
