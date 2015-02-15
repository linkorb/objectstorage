<?php

namespace ObjectStorage\Adapter;

use RuntimeException;
use InvalidArgumentException;

class FileAdapter implements StorageAdapterInterface
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

    public function setData($key, $data)
    {
        $pathinfo = $this->key2PathInfo($key);
        $this->ensureDirectory($this->path . $pathinfo['dirname']);
        file_put_contents($this->path . $pathinfo['dirname'] . "/" . $pathinfo['filename'], $data);
    }

    public function getData($key)
    {
        $pathinfo = $this->key2PathInfo($key);
        if (!file_exists($this->path . $pathinfo['dirname'] . "/" . $pathinfo['filename'])) {
            throw new RuntimeException("Key not found: " . $key);
        }
        $data = file_get_contents($this->path . $pathinfo['dirname'] . "/" . $pathinfo['filename'], $filename);
        return $data;
    }

    public function deleteData($key)
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
