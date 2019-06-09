<?php

namespace ObjectStorage\Adapter;

use ParagonIE\Halite\Alerts\CannotPerformOperation;
use ParagonIE\Halite\Halite;
use ParagonIE\Halite\KeyFactory;
use ParagonIE\Halite\Symmetric\Crypto;
use ParagonIE\Halite\Symmetric\EncryptionKey;
use ParagonIE\HiddenString\HiddenString;

/**
 * Decorates a storage adapter to encrypt and decrypt the object data.
 *
 * Does not encrypt the keys by which data are stored.
 */
class PlaintextStorageKeyEncryptedStorageAdapter extends AbstractEncryptedStorageAdapter implements StorageAdapterInterface
{
    public static function build(array $config)
    {
        if (!isset($config[self::CFG_STORAGE_ADAPTER])
            || !$config[self::CFG_STORAGE_ADAPTER] instanceof StorageAdapterInterface
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
            $config[self::CFG_STORAGE_ADAPTER],
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

    public function setData($key, $data)
    {
        try {
            $encryptedData = Crypto::encrypt(
                new HiddenString($data),
                $this->encryptionKey,
                Halite::ENCODE_BASE64URLSAFE
            );
        } catch (CannotPerformOperation $e) {
            throw new EncryptionFailureException('Failed to encrypt the object data.', null, $e);
        }

        return $this->storageAdapter->setData($key, $encryptedData);
    }

    public function getData($key)
    {
        $encryptedData = $this->storageAdapter->getData($key);

        try {
            $plaintextData = (string) Crypto::decrypt($encryptedData, $this->encryptionKey);
        } catch (CannotPerformOperation $e) {
            throw new EncryptionFailureException('Failed to decrypt the object data.', null, $e);
        }

        return $plaintextData;
    }

    public function deleteData($key)
    {
        return $this->storageAdapter->deleteData($key);
    }
}
