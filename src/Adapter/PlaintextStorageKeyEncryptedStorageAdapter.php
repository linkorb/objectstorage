<?php

namespace ObjectStorage\Adapter;

use ParagonIE\Halite\Alerts\CannotPerformOperation;
use ParagonIE\Halite\Halite;
use ParagonIE\Halite\Symmetric\Crypto;
use ParagonIE\HiddenString\HiddenString;

/**
 * Decorates a storage adapter to encrypt and decrypt the object data.
 *
 * Does not encrypt the keys by which data are stored.
 */
class PlaintextStorageKeyEncryptedStorageAdapter extends EncryptedStorageAdapter
{
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
