<?php

namespace ObjectStorage\Adapter;

use MongoGridFS;
use InvalidArgumentException;

class GridFsAdapter implements StorageAdapterInterface
{
    private $gridfs = null;

    public function __construct(MongoGridFS $gridfs)
    {
        $this->setGridFs($gridfs);
    }
    
    public function setGridFs($gridfs)
    {
        $this->gridfs = $gridfs;
    }
    
    public function setData($key, $data)
    {
        $this->gridfs->storeBytes($data, array("filename" => $key, "_id" => $key));
    }

    public function getData($key)
    {
        $file = $this->gridfs->get($key);
        return $file->getBytes();
    }
    
    public function deleteData($key)
    {
        $this->gridfs->delete($key);
    }

}
