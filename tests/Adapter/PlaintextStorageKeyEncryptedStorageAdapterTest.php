<?php

namespace ObjectStorage\Test\Adapter;

use ObjectStorage\Adapter\PlaintextStorageKeyEncryptedStorageAdapter;
use ObjectStorage\Adapter\StorageAdapterInterface;
use ParagonIE\Halite\KeyFactory;
use ParagonIE\Halite\Symmetric\Crypto;
use ParagonIE\HiddenString\HiddenString;
use PHPUnit\Framework\Constraint\LogicalNot;
use PHPUnit\Framework\TestCase;

class PlaintextStorageKeyEncryptedStorageAdapterTest extends TestCase
{
    private $encryptedStorageAdapter;
    private $encryptionKey;
    private $storageAdapter;

    protected function setUp(): void
    {
        $this->encryptionKey = KeyFactory::generateEncryptionKey();

        $this->storageAdapter = $this->getMockBuilder(StorageAdapterInterface::class)
            ->getMockForAbstractClass()
        ;

        $this->encryptedStorageAdapter = new PlaintextStorageKeyEncryptedStorageAdapter(
            $this->storageAdapter,
            $this->encryptionKey
        );
    }

    public function testSetDataDoesNotPassUnencryptedDataToStorageAdapter()
    {
        $this->storageAdapter
            ->expects($this->once())
            ->method('setData')
            ->with(
                $this->identicalTo('some-key'),
                new LogicalNot('some-data')
            )
        ;

        $this->encryptedStorageAdapter->setData('some-key', 'some-data');
    }

    public function testGetDataDoesNotPassUnencryptedDataToStorageAdapter()
    {
        $this->storageAdapter
            ->expects($this->once())
            ->method('getData')
            ->with($this->identicalTo('some-key'))
            ->willReturn(Crypto::encrypt(new HiddenString('some-data'), $this->encryptionKey))
        ;

        $this->encryptedStorageAdapter->getData('some-key');
    }

    public function testDeleteDataDoesNotPassUnencryptedDataToStorageAdapter()
    {
        $this->storageAdapter
            ->expects($this->once())
            ->method('deleteData')
            ->with($this->identicalTo('some-key'))
        ;

        $this->encryptedStorageAdapter->deleteData('some-key');
    }
}
