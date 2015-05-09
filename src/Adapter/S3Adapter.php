<?php

namespace ObjectStorage\Adapter;

use InvalidArgumentException;

class S3Adapter implements StorageAdapterInterface
{
    private $s3client = null;
    private $bucketname = null;
    private $defaultacl = 'public-read';
    private $prefix = '';

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
