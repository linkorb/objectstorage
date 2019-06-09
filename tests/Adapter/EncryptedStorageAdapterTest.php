<?php

namespace ObjectStorage\Test\Adapter;

use ObjectStorage\Adapter\EncryptedStorageAdapter;
use ObjectStorage\Adapter\StorageAdapterInterface;
use ParagonIE\Halite\KeyFactory;
use ParagonIE\Halite\Symmetric\Crypto;
use ParagonIE\HiddenString\HiddenString;
use PHPUnit\Framework\Constraint\LogicalNot;
use PHPUnit\Framework\TestCase;

class EncryptedStorageAdapterTest extends TestCase
{
    private $authenticationKey;
    private $encryptedStorageAdapter;
    private $encryptionKey;
    private $storageAdapter;

    protected function setUp(): void
    {
        $this->authenticationKey = KeyFactory::generateAuthenticationKey();
        $this->encryptionKey = KeyFactory::generateEncryptionKey();

        $this->storageAdapter = $this->getMockBuilder(StorageAdapterInterface::class)
            ->getMockForAbstractClass()
        ;

        $this->encryptedStorageAdapter = new EncryptedStorageAdapter(
            $this->storageAdapter,
            $this->authenticationKey,
            $this->encryptionKey
        );
    }

    public function testSetDataDoesNotPassUnencryptedKeyOrDataToStorageAdapter()
    {
        $this->storageAdapter
            ->expects($this->once())
            ->method('setData')
            ->with(
                new LogicalNot('some-key'),
                new LogicalNot('some-keysome-data')
            )
        ;

        $this->encryptedStorageAdapter->setData('some-key', 'some-data');
    }

    public function testGetDataDoesNotPassUnencryptedKeyOrDataToStorageAdapter()
    {
        $this->storageAdapter
            ->expects($this->once())
            ->method('getData')
            ->with(new LogicalNot('some-key'))
            ->willReturn(Crypto::encrypt(new HiddenString('some-keysome-data'), $this->encryptionKey))
        ;

        $this->encryptedStorageAdapter->getData('some-key');
    }

    public function testDeleteDataDoesNotPassUnencryptedKeyOrDataToStorageAdapter()
    {
        $this->storageAdapter
            ->expects($this->once())
            ->method('deleteData')
            ->with(new LogicalNot('some-key'))
        ;

        $this->encryptedStorageAdapter->deleteData('some-key');
    }
}
