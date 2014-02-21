<?php

namespace LinkORB\Component\ObjectStorage\Driver;

use LinkORB_Log;
use RuntimeException;
use InvalidArgumentException;


class FileDriver implements DriverInterface
{
    private $path = null;
    public $type = null;

    public function __construct($path)
    {
        $this->setPath($path);
    }

    public function setPath($path)
    {
        if (!file_exists($path)) {
            throw new RuntimeException("Path does not exist: " . $path);
        }
        $this->path = $path;
    }

    public function set($key, $data)
    {
        $pathinfo = $this->key2PathInfo($key);
        $this->ensureDirectory($this->path . $pathinfo['dirname']);
        file_put_contents($this->path . $pathinfo['dirname'] . "/" . $pathinfo['filename'], $data);
    }

    public function get($key)
    {
        $pathinfo = $this->key2PathInfo($key);
        if (!file_exists($this->path . $pathinfo['dirname'] . "/" . $pathinfo['filename'])) {
            throw new RuntimeException("Key not found: " . $key);
        }
        $data = file_get_contents($this->path . $pathinfo['dirname'] . "/" . $pathinfo['filename'], $filename);
        return $data;
    }

    public function delete($key)
    {

        $pathinfo = $this->key2PathInfo($key);
        $filename = $this->path . $pathinfo['dirname'] . "/" . $pathinfo['filename'];
        if (!file_exists($filename)) {
            throw new InvalidArgumentException("Key does not exist: " . $key);
        }

        unlink($filename);
    }

    private function key2PathInfo($key)
    {
        $info = pathinfo($key);
        return array("dirname" => $info['dirname'], "filename" => $info['basename']);
    }

    private function ensureDirectory($path)
    {
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
    }
   
}
