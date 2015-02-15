<?php

namespace ObjectStorage\Adapter;

interface StorageAdapterInterface
{
    public function getData($key);
    public function setData($key, $data);
    public function deleteData($key);
}
