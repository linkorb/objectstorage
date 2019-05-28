<?php

namespace ObjectStorage\Adapter;

interface BuildableAdapterInterface
{
    /**
     * Build an instance of the adapter.
     *
     * @param array $config
     *
     * @return \ObjectStorage\Adapter\StorageAdapterInterface
     */
    public static function build(array $config);
}
