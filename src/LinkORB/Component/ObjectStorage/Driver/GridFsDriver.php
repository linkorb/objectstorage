<?php
/** 
 * This file implements the LinkORB_ObjectStore_S3 class
 */

namespace LinkORB\Component\ObjectStorage\Driver;

use InvalidArgumentException;

class GridFsDriver implements DriverInterface
{
    private $gridfs = null;

    public function __construct($gridfs)
    {
        $this->setGridFs($gridfs);
    }
    
    public function setGridFs($gridfs)
    {
        $this->gridfs = $gridfs;
    }
    
    public function set($key, $data)
    {
        $this->gridfs->storeBytes($data, array("filename" => $key, "_id" => $key));
    }

    public function get($key)
    {
        $file = $this->gridfs->get($key);
        return $file->getBytes();
    }
    
    public function delete($key)
    {
        $this->gridfs->delete($key);
    }

}
