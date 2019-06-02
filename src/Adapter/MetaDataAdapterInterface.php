<?php

namespace ObjectStorage\Adapter;

interface MetaDataAdapterInterface
{
    public function listKeys($key);

    public function setKey($key, $data);

    public function deleteKey($key);
}
