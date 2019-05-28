<?php

namespace ObjectStorage\Adapter;

use InvalidArgumentException;

use Aws\S3\S3Client;

class S3Adapter implements BuildableAdapterInterface, StorageAdapterInterface
{
    private $s3client = null;
    private $bucketname = null;
    private $defaultacl = 'public-read';
    private $prefix = '';

    public static function build(array $config)
    {
        if (!array_key_exists('access_key', $config)
            || trim($config['access_key']) === ''
        ) {
            throw new InvalidArgumentException(
                'Unable to build S3Adapter: missing "access_key" from configuration.'
            );
        }
        if (!array_key_exists('secret_key', $config)
            || trim($config['secret_key']) == ''
        ) {
            throw new InvalidArgumentException(
                'Unable to build S3Adapter: missing "secret_key" from configuration.'
            );
        }
        if (!array_key_exists('bucketname', $config)
            || trim($config['bucketname']) == ''
        ) {
            throw new InvalidArgumentException(
                'Unable to build S3Adapter: missing "bucketname" from configuration.'
            );
        }
        $prefix = '';
        if (isset($config['prefix'])) {
            $prefix = trim($config['prefix']);
        }

        $client = S3Client::factory(
            [
                'key' => trim($config['access_key']),
                'secret' => trim($config['secret_key']),
            ]
        );

        return new self($client, trim($config['bucketname']), $prefix);
    }

    public function __construct($s3client, $bucketname, $prefix = '')
    {
        $this->setS3Client($s3client);
        $this->setBucketName($bucketname);
        $this->setPrefix($prefix);
    }
    
    public function setS3Client($s3client)
    {
        $this->s3client = $s3client;
    }

    public function setBucketName($bucketname)
    {
        if (trim($bucketname)=='') {
            throw new InvalidArgumentException("Invalid bucketname: " . $bucketname);
        }
        $this->bucketname = $bucketname;
    }
    
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }
    
    public function setData($key, $data)
    {
        $key = $this->prefix . $key;
        $this->s3client->putObject(
            array(
                'Bucket' => $this->bucketname,
                'Key' => $key,
                'Body' => $data,
                'ACL' => $this->defaultacl
            )
        );
    }

    public function getData($key)
    {
        $key = $this->prefix . $key;
        $result = $this->s3client->getObject(
            array(
                'Bucket' => $this->bucketname, 
                'Key' => $key,
            )
        );
        return (string)$result['Body'];
    }
    
    public function deleteData($key)
    {
        $key = $this->prefix . $key;
        $this->s3client->deleteObject(
            array(
                'Bucket' => $this->bucketname, 
                'Key' => $key,
            )
        );
    }
}
