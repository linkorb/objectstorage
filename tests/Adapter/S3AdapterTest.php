<?php

namespace ObjectStorage\Test\Adapter;

use Aws\S3\S3Client;
use InvalidArgumentException;
use ObjectStorage\Adapter\S3Adapter;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class S3AdapterTest extends TestCase
{
    /**
     * @dataProvider provideIncompleteConfiguration
     */
    public function testBuildRejectsIncompleteConfiguration(array $config, $message)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        S3Adapter::build($config);
    }

    public function provideIncompleteConfiguration()
    {
        $config = [
            'access_key' => 'AKIAtest',
            'secret_key' => 'secret',
            'bucketname' => 'my-bucket',
            'region' => 'eu-west-1',
        ];

        $missingAccessKey = $config;
        unset($missingAccessKey['access_key']);
        $blankAccessKey = $config;
        $blankAccessKey['access_key'] = '   ';

        $missingSecretKey = $config;
        unset($missingSecretKey['secret_key']);
        $blankSecretKey = $config;
        $blankSecretKey['secret_key'] = '   ';

        $missingBucketName = $config;
        unset($missingBucketName['bucketname']);
        $blankBucketName = $config;
        $blankBucketName['bucketname'] = '   ';

        $missingRegion = $config;
        unset($missingRegion['region']);
        $blankRegion = $config;
        $blankRegion['region'] = '   ';

        return [
            'missing access_key' => [
                $missingAccessKey,
                'Unable to build S3Adapter: missing "access_key" from configuration.',
            ],
            'blank access_key' => [
                $blankAccessKey,
                'Unable to build S3Adapter: missing "access_key" from configuration.',
            ],
            'missing secret_key' => [
                $missingSecretKey,
                'Unable to build S3Adapter: missing "secret_key" from configuration.',
            ],
            'blank secret_key' => [
                $blankSecretKey,
                'Unable to build S3Adapter: missing "secret_key" from configuration.',
            ],
            'missing bucketname' => [
                $missingBucketName,
                'Unable to build S3Adapter: missing "bucketname" from configuration.',
            ],
            'blank bucketname' => [
                $blankBucketName,
                'Unable to build S3Adapter: missing "bucketname" from configuration.',
            ],
            'missing region' => [
                $missingRegion,
                'Unable to build S3Adapter: missing "region" from configuration.',
            ],
            'blank region' => [
                $blankRegion,
                'Unable to build S3Adapter: missing "region" from configuration.',
            ],
        ];
    }

    public function testBuildDefaultsVersionAndStoresEmptyPrefixAndCannedAcl()
    {
        $adapter = S3Adapter::build([
            'access_key' => 'AKIAtest',
            'secret_key' => 'secret',
            'bucketname' => 'my-bucket',
            'region' => 'eu-west-1',
        ]);

        $this->assertInstanceOf(S3Adapter::class, $adapter);
        $this->assertSame('my-bucket', $this->property($adapter, 'bucketname'));
        $this->assertSame('', $this->property($adapter, 'prefix'));
        // build() passes '' when canned_acl_for_objects is absent, so the
        // private default ("private") is replaced.
        $this->assertSame('', $this->property($adapter, 'cannedAcl'));
        $this->assertSame('2006-03-01', S3Adapter::DEFAULT_API_VERSION);

        $client = $this->property($adapter, 's3client');
        $this->assertInstanceOf(S3Client::class, $client);
        $this->assertSame('eu-west-1', $client->getRegion());
        $this->assertSame(S3Adapter::DEFAULT_API_VERSION, $client->getApi()->getApiVersion());

        $credentials = $client->getCredentials()->wait();
        $this->assertSame('AKIAtest', $credentials->getAccessKeyId());
        $this->assertSame('secret', $credentials->getSecretKey());
    }

    public function testBuildTrimsCredentialsVersionPrefixAndCannedAcl()
    {
        $adapter = S3Adapter::build([
            'access_key' => '  AKIAtest  ',
            'secret_key' => '  secret  ',
            'bucketname' => '  my-bucket  ',
            'region' => '  eu-west-1  ',
            'version' => '  2006-03-01  ',
            'prefix' => '  uploads/  ',
            'canned_acl_for_objects' => '  public-read  ',
        ]);

        $this->assertSame('my-bucket', $this->property($adapter, 'bucketname'));
        $this->assertSame('uploads/', $this->property($adapter, 'prefix'));
        $this->assertSame('public-read', $this->property($adapter, 'cannedAcl'));

        $client = $this->property($adapter, 's3client');
        $this->assertSame('eu-west-1', $client->getRegion());
        $this->assertSame('2006-03-01', $client->getApi()->getApiVersion());

        $credentials = $client->getCredentials()->wait();
        $this->assertSame('AKIAtest', $credentials->getAccessKeyId());
        $this->assertSame('secret', $credentials->getSecretKey());
    }

    private function property(S3Adapter $adapter, $name)
    {
        $property = new ReflectionProperty(S3Adapter::class, $name);
        $property->setAccessible(true);

        return $property->getValue($adapter);
    }
}
