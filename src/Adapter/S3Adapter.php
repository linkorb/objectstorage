<?php

namespace ObjectStorage\Adapter;

use Aws\S3\S3Client;
use InvalidArgumentException;

class S3Adapter implements BuildableAdapterInterface, StorageAdapterInterface
{
    const DEFAULT_ACL = 'private';
    const DEFAULT_API_VERSION = '2006-03-01';

    private $s3client = null;
    private $bucketname = null;
    private $cannedAcl = self::DEFAULT_ACL;
    private $prefix = '';

    public static function build(array $config)
    {
        if (!array_key_exists('access_key', $config)
            || '' === trim($config['access_key'])
        ) {
            throw new InvalidArgumentException(
                'Unable to build S3Adapter: missing "access_key" from configuration.'
            );
        }
        if (!array_key_exists('secret_key', $config)
            || '' === trim($config['secret_key'])
        ) {
            throw new InvalidArgumentException(
                'Unable to build S3Adapter: missing "secret_key" from configuration.'
            );
        }
        if (!array_key_exists('bucketname', $config)
            || '' === trim($config['bucketname'])
        ) {
            throw new InvalidArgumentException(
                'Unable to build S3Adapter: missing "bucketname" from configuration.'
            );
        }
        if (!array_key_exists('region', $config)
            || '' === trim($config['region'])
        ) {
            throw new InvalidArgumentException(
                'Unable to build S3Adapter: missing "region" from configuration.'
            );
        }
        if (!array_key_exists('version', $config)) {
            $config['version'] = self::DEFAULT_API_VERSION;
        }
        $prefix = '';
        if (isset($config['prefix'])) {
            $prefix = trim($config['prefix']);
        }
        $cannedAcl = '';
        if (isset($config['canned_acl_for_objects'])) {
            $cannedAcl = trim($config['canned_acl_for_objects']);
        }

        $client = new S3Client(
            [
                'credentials' => [
                    'key' => trim($config['access_key']),
                    'secret' => trim($config['secret_key']),
                ],
                'region' => trim($config['region']),
                'version' => trim($config['version']),
            ]
        );

        return new self($client, trim($config['bucketname']), $prefix, $cannedAcl);
    }

    public function __construct(S3Client $s3client, $bucketname, $prefix = '', $cannedAcl = null)
    {
        $this->s3client = $s3client;
        $this->setBucketName($bucketname);
        $this->prefix = $prefix;
        if (null !== $cannedAcl) {
            $this->cannedAcl = $cannedAcl;
        }
    }

    public function setS3Client(S3Client $s3client)
    {
        $this->s3client = $s3client;
    }

    public function setBucketName($bucketname)
    {
        if ('' === trim($bucketname)) {
            throw new InvalidArgumentException('An empty bucketname is an invalid bucketname.');
        }
        $this->bucketname = $bucketname;
    }

    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    public function setCannedAcl(string $cannedAcl)
    {
        $this->cannedAcl = $cannedAcl;
    }

    public function setData($key, $data)
    {
        $this->s3client->putObject(
            [
                'Bucket' => $this->bucketname,
                'Key' => $this->prefix . $key,
                'Body' => $data,
                'ACL' => $this->cannedAcl,
            ]
        );
    }

    public function getData($key)
    {
        $result = $this->s3client->getObject(
            [
                'Bucket' => $this->bucketname,
                'Key' => $this->prefix . $key,
            ]
        );

        return (string) $result['Body'];
    }

    public function deleteData($key)
    {
        $this->s3client->deleteObject(
            [
                'Bucket' => $this->bucketname,
                'Key' => $this->prefix . $key,
            ]
        );
    }
}
