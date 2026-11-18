<?php

namespace ObjectStorage\Test\Adapter;

use InvalidArgumentException;
use ObjectStorage\Adapter\PlaintextStorageKeyEncryptedStorageAdapter;
use ObjectStorage\Adapter\StorageAdapterInterface;
use ParagonIE\Halite\KeyFactory;
use ParagonIE\Halite\Symmetric\EncryptionKey;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class PlaintextStorageKeyEncryptedStorageAdapterBuildTest extends TestCase
{
    public function testBuildRejectsMissingStorageAdapter()
    {
        $this->assertBuildRejects(
            [],
            'The build configuration for this storage adapter is missing an instance of StorageAdapterInterface, keyed as "storage_adapter"."'
        );
    }

    public function testBuildRejectsStorageAdapterOfTheWrongType()
    {
        $this->assertBuildRejects(
            ['storage_adapter' => 'not-an-adapter'],
            'The build configuration for this storage adapter is missing an instance of StorageAdapterInterface, keyed as "storage_adapter"."'
        );
    }

    public function testBuildRejectsMissingEncryptionKey()
    {
        $this->assertBuildRejects(
            ['storage_adapter' => $this->storageAdapter()],
            'The build configuration for this storage adapter is missing an encryption key ("encryption_key" or "encryption_key_path").'
        );
    }

    public function testBuildRejectsEncryptionKeyOfTheWrongType()
    {
        $this->assertBuildRejects(
            [
                'storage_adapter' => $this->storageAdapter(),
                'encryption_key' => KeyFactory::generateAuthenticationKey(),
            ],
            '"encryption_key"  must be an instance of EncryptionKey.'
        );
    }

    public function testBuildRejectsUnreadableEncryptionKeyPath()
    {
        $this->assertBuildRejects(
            [
                'storage_adapter' => $this->storageAdapter(),
                'encryption_key_path' => sys_get_temp_dir().'/objectstorage-missing-encryption.key',
            ],
            '"encryption_key_path"  must be a readable file.'
        );
    }

    public function testBuildFromKeyObjectStoresTheGivenAdapterAndKey()
    {
        $storageAdapter = $this->storageAdapter();
        $encryptionKey = KeyFactory::generateEncryptionKey();

        $adapter = PlaintextStorageKeyEncryptedStorageAdapter::build([
            'storage_adapter' => $storageAdapter,
            'encryption_key' => $encryptionKey,
        ]);

        $this->assertInstanceOf(PlaintextStorageKeyEncryptedStorageAdapter::class, $adapter);
        $this->assertSame($storageAdapter, $this->property($adapter, 'storageAdapter'));
        $this->assertSame($encryptionKey, $this->property($adapter, 'encryptionKey'));
    }

    public function testBuildFromKeyPathLoadsTheKeyFromThatFile()
    {
        $storageAdapter = $this->storageAdapter();
        $encryptionKey = KeyFactory::generateEncryptionKey();
        $encryptionKeyPath = tempnam(sys_get_temp_dir(), 'enc-key-');
        KeyFactory::save($encryptionKey, $encryptionKeyPath);

        try {
            $adapter = PlaintextStorageKeyEncryptedStorageAdapter::build([
                'storage_adapter' => $storageAdapter,
                'encryption_key_path' => $encryptionKeyPath,
            ]);
        } finally {
            unlink($encryptionKeyPath);
        }

        $loadedEncryptionKey = $this->property($adapter, 'encryptionKey');

        $this->assertInstanceOf(PlaintextStorageKeyEncryptedStorageAdapter::class, $adapter);
        $this->assertSame($storageAdapter, $this->property($adapter, 'storageAdapter'));
        $this->assertInstanceOf(EncryptionKey::class, $loadedEncryptionKey);
        $this->assertSame(
            $encryptionKey->getRawKeyMaterial(),
            $loadedEncryptionKey->getRawKeyMaterial()
        );
    }

    private function assertBuildRejects(array $config, $message)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        PlaintextStorageKeyEncryptedStorageAdapter::build($config);
    }

    private function storageAdapter()
    {
        return $this->getMockBuilder(StorageAdapterInterface::class)
            ->getMockForAbstractClass()
        ;
    }

    private function property(PlaintextStorageKeyEncryptedStorageAdapter $adapter, $name)
    {
        $property = new ReflectionProperty(PlaintextStorageKeyEncryptedStorageAdapter::class, $name);
        $property->setAccessible(true);

        return $property->getValue($adapter);
    }
}
