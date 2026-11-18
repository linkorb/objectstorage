<?php

namespace ObjectStorage\Test\Adapter;

use Bergen\Client\ClientOptions;
use Error;
use GuzzleHttp\RequestOptions;
use ObjectStorage\Adapter\BergenAdapter;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class BergenAdapterBuildTest extends TestCase
{
    public function testBuildMissingHostRaisesUnresolvedInvalidArgumentException()
    {
        $this->expectException(Error::class);
        $this->expectExceptionMessage('ObjectStorage\\Adapter\\InvalidArgumentException');

        BergenAdapter::build([]);
    }

    public function testBuildWithHostOnlyStoresTrimmedHost()
    {
        $adapter = BergenAdapter::build([
            'host' => '  bergen.example  ',
        ]);

        $this->assertInstanceOf(BergenAdapter::class, $adapter);
        $this->assertSame(
            [ClientOptions::API_HOST => 'bergen.example'],
            $this->clientOptions($adapter)
        );
    }

    public function testBuildWithUsernameAndNoPasswordOmitsAuthAndCastsSecure()
    {
        $adapter = BergenAdapter::build([
            'host' => 'bergen.example',
            'username' => 'user',
            'secure' => 0,
        ]);

        $this->assertSame(
            [
                ClientOptions::API_HOST => 'bergen.example',
                ClientOptions::SECURE_HTTP => false,
            ],
            $this->clientOptions($adapter)
        );
    }

    public function testBuildWithUsernameAndPasswordStoresUntrimmedPassword()
    {
        $adapter = BergenAdapter::build([
            'host' => 'bergen.example',
            'username' => '  user  ',
            'password' => '  secret  ',
        ]);

        $this->assertSame(
            [
                ClientOptions::API_HOST => 'bergen.example',
                RequestOptions::AUTH => ['user', '  secret  '],
            ],
            $this->clientOptions($adapter)
        );
    }

    private function clientOptions(BergenAdapter $adapter)
    {
        $client = $this->property($adapter, 'client');
        $builder = $this->property($client, 'builder');

        return $this->property($builder, 'config');
    }

    private function property($object, $name)
    {
        $property = new ReflectionProperty(get_class($object), $name);

        return $property->getValue($object);
    }
}

namespace Bergen\Client;

if (!class_exists(__NAMESPACE__ . '\\ClientOptions', false)) {
    class ClientOptions
    {
        const API_HOST = 'api_host';
        const SECURE_HTTP = 'secure';
    }
}

if (!class_exists(__NAMESPACE__ . '\\ClientBuilder', false)) {
    class ClientBuilder
    {
        private $config;

        public function __construct($config)
        {
            $this->config = $config;
        }
    }
}

namespace Bergen\Client\V1;

use Bergen\Client\ClientBuilder;

if (!class_exists(__NAMESPACE__ . '\\V1StorageClient', false)) {
    class V1StorageClient
    {
        private $builder;

        public function __construct(ClientBuilder $builder)
        {
            $this->builder = $builder;
        }
    }
}
