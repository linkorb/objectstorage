<?php
/** 
 * This file implements the LinkORB_ObjectStore_S3 class
 */

namespace LinkORB\Component\ObjectStorage\Driver;

use InvalidArgumentException;

class S3Driver implements DriverInterface
{
    private $s3client = null;
    private $bucketname = null;

    public function __construct($s3client, $bucketname)
    {
        $this->setS3Client($s3client);
        $this->setBucketName($bucketname);
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
    
    public function set($key, $data)
    {
        $this->s3client->putObject(
            array(
                'Bucket' => $this->bucketname, 
                'Key' => $key,
                'Body' => $data
            )
        );
    }

    public function get($key)
    {
        $result = $this->s3client->getObject(
            array(
                'Bucket' => $this->bucketname, 
                'Key' => $key,
            )
        );
        return (string)$result['Body'];
    }
    
    public function delete($key)
    {
        $this->s3client->deleteObject(
            array(
                'Bucket' => $this->bucketname, 
                'Key' => $key,
            )
        );
    }

}
