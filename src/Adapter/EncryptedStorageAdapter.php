<?php

namespace ObjectStorage\Adapter;

use ParagonIE\Halite\Alerts\CannotPerformOperation;
use ParagonIE\Halite\Halite;
use ParagonIE\Halite\KeyFactory;
use ParagonIE\Halite\Symmetric\AuthenticationKey;
use ParagonIE\Halite\Symmetric\Crypto;
use ParagonIE\Halite\Symmetric\EncryptionKey;
use ParagonIE\HiddenString\HiddenString;

/**
 * Decorates a storage adapter to encrypt and decrypt object data and the keys
 * by which the data are stored.
 */
class EncryptedStorageAdapter extends AbstractEncryptedStorageAdapter implements StorageAdapterInterface
{
    protected $authenticationKey;
    protected $encryptionKey;
    protected $storageAdapter;

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

        if (isset($config[self::CFG_AUTHENTICATION_KEY])) {
            if (!$config[self::CFG_AUTHENTICATION_KEY] instanceof AuthenticationKey) {
                throw new \InvalidArgumentException(
                    '"' . self::CFG_AUTHENTICATION_KEY . '"  must be an instance of AuthenticationKey.'
                );
            }
            $authenticationKey = $config[self::CFG_AUTHENTICATION_KEY];
        } elseif (isset($config[self::CFG_AUTHENTICATION_KEY_PATH])) {
            try {
                $authenticationKey = KeyFactory::loadAuthenticationKey($config[self::CFG_AUTHENTICATION_KEY_PATH]);
            } catch (CannotPerformOperation $e) {
                throw new \InvalidArgumentException(
                    '"' . self::CFG_AUTHENTICATION_KEY_PATH . '"  must be a readable file.'
                );
            }
        } else {
            throw new \InvalidArgumentException(
                'The build configuration for this storage adapter is missing an authentication key ("'
                    . self::CFG_AUTHENTICATION_KEY
                    . '" or "'
                    . self::CFG_AUTHENTICATION_KEY_PATH
                    . '").'
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
            $authenticationKey,
            $encryptionKey
        );
    }

    public function __construct(
        StorageAdapterInterface $storageAdapter,
        AuthenticationKey $authenticationKey,
        EncryptionKey $encryptionKey
    ) {
        $this->storageAdapter = $storageAdapter;
        $this->authenticationKey = $authenticationKey;
        $this->encryptionKey = $encryptionKey;
    }

    public function setAuthenticationKey(AuthenticationKey $authenticationKey)
    {
        $this->authenticationKey = $authenticationKey;
    }

    public function setData($key, $data)
    {
        try {
            $authenticStorageKey = Crypto::authenticate(
                $key,
                $this->authenticationKey,
                Halite::ENCODE_BASE64URLSAFE
            );
        } catch (CannotPerformOperation $e) {
            throw new EncryptionFailureException('Failed to hash the storage key.', null, $e);
        }

        try {
            $encryptedBlob = Crypto::encrypt(
                new HiddenString($key . $data),
                $this->encryptionKey,
                Halite::ENCODE_BASE64URLSAFE
            );
        } catch (CannotPerformOperation $e) {
            throw new EncryptionFailureException('Failed to encrypt the storage key and object data.', null, $e);
        }

        return $this->storageAdapter->setData($authenticStorageKey, $encryptedBlob);
    }

    public function getData($key)
    {
        try {
            $authenticStorageKey = Crypto::authenticate(
                $key,
                $this->authenticationKey,
                Halite::ENCODE_BASE64URLSAFE
            );
        } catch (CannotPerformOperation $e) {
            throw new EncryptionFailureException('Failed to hash the storage key.', null, $e);
        }

        $encryptedBlob = $this->storageAdapter->getData($authenticStorageKey);

        try {
            $plaintextBlob = (string) Crypto::decrypt($encryptedBlob, $this->encryptionKey);
        } catch (CannotPerformOperation $e) {
            throw new EncryptionFailureException('Failed to decrypt the storage key and object data.', null, $e);
        }

        $storageKeyLength = \strlen($key);

        if (false === \hash_equals($key, \substr($plaintextBlob, 0, $storageKeyLength))) {
            // The $plaintextBlob was definitely encrypted with our encryption key,
            // but the storage key is not the one in that blob.
            throw new EncryptionFailureException(
                'The object data is not the expected one for the supplied storage key. The store has been corrupted or tampered with.'
            );
        }

        return \substr($plaintextBlob, $storageKeyLength);
    }

    public function deleteData($key)
    {
        try {
            $authenticStorageKey = Crypto::authenticate(
                $key,
                $this->authenticationKey,
                Halite::ENCODE_BASE64URLSAFE
            );
        } catch (CannotPerformOperation $e) {
            throw new EncryptionFailureException('Failed to hash the storage key.', null, $e);
        }

        return $this->storageAdapter->deleteData($authenticStorageKey);
    }
}
