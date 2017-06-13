<?php

namespace ObjectStorage\Adapter;

interface StorageAdapterInterface
{
    public function getData($key);
    public function setData($key, $data);
    public function deleteData($key);
    /**
     * Build an instance of the adapter.
     *
     * @param array $config
     * @return \ObjectStorage\Adapter\StorageAdapterInterface
     */
    //public static function build(array $config);
}
