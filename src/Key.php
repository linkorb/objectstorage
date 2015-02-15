<?php

namespace ObjectStorage;

class Key
{
    private $key;
    private $metadata;

    public function __construct($key)
    {
        $this->key = $key;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function setMetaData($data)
    {
        $this->metadata = $data;
    }
    public function getMetaData()
    {
        return $this->metadata;
    }
}
