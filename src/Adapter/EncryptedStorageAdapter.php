<?php

namespace ObjectStorage\Adapter;

use ParagonIE\Halite\Alerts\CannotPerformOperation;
use ParagonIE\Halite\Halite;
use ParagonIE\Halite\KeyFactory;
use ParagonIE\Halite\Symmetric\Crypto;
use ParagonIE\Halite\Symmetric\EncryptionKey;
use ParagonIE\HiddenString\HiddenString;

/**
 * Decorates a storage adapter to encrypt and decrypt object data and the keys
 * by which the data are stored.
 */
class EncryptedStorageAdapter implements StorageAdapterInterface
{
    const CFG_ENCRYPTION_KEY = 'encryption_key';
    const CFG_ENCRYPTION_KEY_PATH = 'encryption_key_path';
    const CFG_STORAGE_ADAPTER = 'storage_adapter';

    protected $encryptionKey;
    protected $storageAdapter;

    public static function build(array $config)
    {
        if (!isset($config[self::CFG_ENCRYPT_STORAGE_ADAPTER])
            || !$config[self::CFG_ENCRYPT_STORAGE_ADAPTER] instanceof StorageAdapterInterface
        ) {
            throw new \InvalidArgumentException(
                'The build configuration for this storage adapter is missing an instance of StorageAdapterInterface, keyed as "'
                    . self::CFG_STORAGE_ADAPTER
                    . '"."'
            );
        }

        if (isset($config[self::CFG_ENCRYPTION_KEY])) {
            if (!$config[self::CFG_ENCRYPTION_KEY] instanceof EncryptionKey) {
                throw new \InvalidArgumentException(
                    '"' . self::CFG_ENCRYPTION_KEY . '"  must be an instance of EncryptionKey.'
                );
            }
            $encryptionKey = $config[self::CFG_ENCRYPTION_KEY];
        } elseif (isset($config[self::CFG_ENCRYPTION_KEY_PATH])) {
            try {
                $encryptionKey = KeyFactory::loadEncryptionKey($config[self::CFG_ENCRYPTION_KEY_PATH]);
            } catch (CannotPerformOperation $e) {
                throw new \InvalidArgumentException(
                    '"' . self::CFG_ENCRYPTION_KEY_PATH . '"  must be a readable file.'
                );
            }
        } else {
            throw new \InvalidArgumentException(
                'The build configuration for this storage adapter is missing an encryption key ("'
                    . self::CFG_ENCRYPTION_KEY
                    . '" or "'
                    . self::CFG_ENCRYPTION_KEY_PATH
                    . '").'
            );
        }

        return new self(
            $config[self::CFG_ENCRYPT_STORAGE_ADAPTER],
            $encryptionKey
        );
    }

    public function __construct(
        StorageAdapterInterface $storageAdapter,
        EncryptionKey $encryptionKey
    ) {
        $this->storageAdapter = $storageAdapter;
        $this->encryptionKey = $encryptionKey;
    }

    public function setAdapter(StorageAdapterInterface $storageAdapter)
    {
        $this->storageAdapter = $storageAdapter;
    }

    public function setEncryptionKey(EncryptionKey $encryptionKey)
    {
        $this->encryptionKey = $encryptionKey;
    }

    public function setData($key, $data)
    {
        try {
            $encryptedStorageKey = Crypto::encrypt(
                new HiddenString($key),
                $this->encryptionKey,
                Halite::ENCODE_BASE64URLSAFE
            );
        } catch (CannotPerformOperation $e) {
            throw new EncryptionFailureException('Failed to encrypt the storage key.', null, $e);
        }

        try {
            $encryptedData = Crypto::encrypt(
                new HiddenString($data),
                $this->encryptionKey,
                Halite::ENCODE_BASE64URLSAFE
            );
        } catch (CannotPerformOperation $e) {
            throw new EncryptionFailureException('Failed to encrypt the object data.', null, $e);
        }

        return $this->storageAdapter->setData($encryptedStorageKey, $encryptedData);
    }

    public function getData($key)
    {
        try {
            $encryptedStorageKey = Crypto::encrypt(
                new HiddenString($key),
                $this->encryptionKey,
                Halite::ENCODE_BASE64URLSAFE
            );
        } catch (CannotPerformOperation $e) {
            throw new EncryptionFailureException('Failed to encrypt the storage key.', null, $e);
        }

        $encryptedData = $this->storageAdapter->getData($encryptedStorageKey);

        try {
            $plaintextData = (string) Crypto::decrypt($encryptedData, $this->encryptionKey);
        } catch (CannotPerformOperation $e) {
            throw new EncryptionFailureException('Failed to decrypt the object data.', null, $e);
        }

        return $plaintextData;
    }

    public function deleteData($key)
    {
        try {
            $encryptedStorageKey = Crypto::encrypt(
                new HiddenString($key),
                $this->encryptionKey,
                Halite::ENCODE_BASE64URLSAFE
            );
        } catch (CannotPerformOperation $e) {
            throw new EncryptionFailureException('Failed to encrypt the storage key.', null, $e);
        }

        return $this->storageAdapter->deleteData($encryptedStorageKey);
    }
}
