<?php

namespace ObjectStorage\Adapter;

class Bzip2Adapter implements StorageAdapterInterface
{
    private $child;
    private $level;

    public function __construct(StorageAdapterInterface $child, $level)
    {
        $this->child = $child;
        $this->level = $level;
    }

    public function setData($key, $data)
    {
        $data = bzcompress($data, $this->level);

        return $this->child->setData($key, $data);
    }

    public function getData($key)
    {
        $data = $this->child->getData($key);
        $data = bzdecompress($data);

        return $data;
    }

    public function deleteData($key)
    {
        return $this->child->deleteData($key);
    }
}
