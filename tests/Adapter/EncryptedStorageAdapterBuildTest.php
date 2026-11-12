<?php

namespace ObjectStorage\Test\Adapter;

use InvalidArgumentException;
use ObjectStorage\Adapter\EncryptedStorageAdapter;
use ObjectStorage\Adapter\StorageAdapterInterface;
use ParagonIE\Halite\KeyFactory;
use ParagonIE\Halite\Symmetric\AuthenticationKey;
use ParagonIE\Halite\Symmetric\EncryptionKey;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class EncryptedStorageAdapterBuildTest extends TestCase
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

    public function testBuildRejectsMissingAuthenticationKey()
    {
        $this->assertBuildRejects(
            ['storage_adapter' => $this->storageAdapter()],
            'The build configuration for this storage adapter is missing an authentication key ("authentication_key" or "authentication_key_path").'
        );
    }

    public function testBuildRejectsAuthenticationKeyOfTheWrongType()
    {
        $this->assertBuildRejects(
            [
                'storage_adapter' => $this->storageAdapter(),
                'authentication_key' => KeyFactory::generateEncryptionKey(),
            ],
            '"authentication_key"  must be an instance of AuthenticationKey.'
        );
    }

    public function testBuildRejectsUnreadableAuthenticationKeyPath()
    {
        $this->assertBuildRejects(
            [
                'storage_adapter' => $this->storageAdapter(),
                'authentication_key_path' => $this->missingKeyPath('authentication'),
            ],
            '"authentication_key_path"  must be a readable file.'
        );
    }

    public function testBuildRejectsMissingEncryptionKey()
    {
        $this->assertBuildRejects(
            [
                'storage_adapter' => $this->storageAdapter(),
                'authentication_key' => KeyFactory::generateAuthenticationKey(),
            ],
            'The build configuration for this storage adapter is missing an encryption key ("encryption_key" or "encryption_key_path").'
        );
    }

    public function testBuildRejectsEncryptionKeyOfTheWrongType()
    {
        $this->assertBuildRejects(
            [
                'storage_adapter' => $this->storageAdapter(),
                'authentication_key' => KeyFactory::generateAuthenticationKey(),
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
                'authentication_key' => KeyFactory::generateAuthenticationKey(),
                'encryption_key_path' => $this->missingKeyPath('encryption'),
            ],
            '"encryption_key_path"  must be a readable file.'
        );
    }

    public function testBuildFromKeyObjectsStoresTheGivenAdapterAndKeys()
    {
        $storageAdapter = $this->storageAdapter();
        $authenticationKey = KeyFactory::generateAuthenticationKey();
        $encryptionKey = KeyFactory::generateEncryptionKey();

        $adapter = EncryptedStorageAdapter::build([
            'storage_adapter' => $storageAdapter,
            'authentication_key' => $authenticationKey,
            'encryption_key' => $encryptionKey,
        ]);

        $this->assertInstanceOf(EncryptedStorageAdapter::class, $adapter);
        $this->assertSame($storageAdapter, $this->property($adapter, 'storageAdapter'));
        $this->assertSame($authenticationKey, $this->property($adapter, 'authenticationKey'));
        $this->assertSame($encryptionKey, $this->property($adapter, 'encryptionKey'));
    }

    public function testBuildFromKeyPathsLoadsTheKeysFromThoseFiles()
    {
        $storageAdapter = $this->storageAdapter();
        $authenticationKey = KeyFactory::generateAuthenticationKey();
        $encryptionKey = KeyFactory::generateEncryptionKey();
        $authenticationKeyPath = tempnam(sys_get_temp_dir(), 'auth-key-');
        $encryptionKeyPath = tempnam(sys_get_temp_dir(), 'enc-key-');
        KeyFactory::save($authenticationKey, $authenticationKeyPath);
        KeyFactory::save($encryptionKey, $encryptionKeyPath);

        try {
            $adapter = EncryptedStorageAdapter::build([
                'storage_adapter' => $storageAdapter,
                'authentication_key_path' => $authenticationKeyPath,
                'encryption_key_path' => $encryptionKeyPath,
            ]);
        } finally {
            unlink($authenticationKeyPath);
            unlink($encryptionKeyPath);
        }

        $loadedAuthenticationKey = $this->property($adapter, 'authenticationKey');
        $loadedEncryptionKey = $this->property($adapter, 'encryptionKey');

        $this->assertInstanceOf(EncryptedStorageAdapter::class, $adapter);
        $this->assertSame($storageAdapter, $this->property($adapter, 'storageAdapter'));
        $this->assertInstanceOf(AuthenticationKey::class, $loadedAuthenticationKey);
        $this->assertInstanceOf(EncryptionKey::class, $loadedEncryptionKey);
        $this->assertSame(
            $authenticationKey->getRawKeyMaterial(),
            $loadedAuthenticationKey->getRawKeyMaterial()
        );
        $this->assertSame(
            $encryptionKey->getRawKeyMaterial(),
            $loadedEncryptionKey->getRawKeyMaterial()
        );
    }

    private function assertBuildRejects(array $config, $message)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        EncryptedStorageAdapter::build($config);
    }

    private function storageAdapter()
    {
        return $this->getMockBuilder(StorageAdapterInterface::class)
            ->getMockForAbstractClass()
        ;
    }

    private function missingKeyPath($name)
    {
        return sys_get_temp_dir().'/objectstorage-missing-'.$name.'.key';
    }

    private function property(EncryptedStorageAdapter $adapter, $name)
    {
        $property = new ReflectionProperty(EncryptedStorageAdapter::class, $name);
        $property->setAccessible(true);

        return $property->getValue($adapter);
    }
}
