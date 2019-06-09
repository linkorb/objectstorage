<?php

namespace ObjectStorage\Adapter;

use ParagonIE\Halite\Symmetric\EncryptionKey;

/**
 * Base class for encrypted storage adapters.
 */
abstract class AbstractEncryptedStorageAdapter
{
    const CFG_AUTHENTICATION_KEY = 'authentication_key';
    const CFG_AUTHENTICATION_KEY_PATH = 'authentication_key_path';
    const CFG_ENCRYPTION_KEY = 'encryption_key';
    const CFG_ENCRYPTION_KEY_PATH = 'encryption_key_path';
    const CFG_STORAGE_ADAPTER = 'storage_adapter';

    protected $encryptionKey;
    protected $storageAdapter;

    public function setAdapter(StorageAdapterInterface $storageAdapter)
    {
        $this->storageAdapter = $storageAdapter;
    }

    public function setEncryptionKey(EncryptionKey $encryptionKey)
    {
        $this->encryptionKey = $encryptionKey;
    }
}
