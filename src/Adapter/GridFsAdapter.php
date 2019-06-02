<?php

namespace ObjectStorage\Adapter;

use InvalidArgumentException;
use MongoClient;
use MongoGridFS;

class GridFsAdapter implements BuildableAdapterInterface, StorageAdapterInterface
{
    private $gridfs = null;

    public static function build(array $config)
    {
        if (!array_key_exists('server', $config)) {
            throw new InvalidArgumentException(
                'Unable to build GridFsAdapter: missing "server" from configuration.'
            );
        }
        if (!array_key_exists('dbname', $config)
            || '' === trim($config['dbname'])
        ) {
            throw new InvalidArgumentException(
                'Unable to build GridFsAdapter: missing "dbname" from configuration.'
            );
        }

        $server = 'mongodb://localhost:27017';
        if (isset($config['server'])) {
            $server = trim($config['server']);
        }
        $dbname = trim($config['dbname']);

        $mongoclient = new MongoClient($server);
        $grid = $mongoclient->selectDB($dbname)->getGridFS();

        return new self($grid);
    }

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
        $this->gridfs->storeBytes($data, ['filename' => $key, '_id' => $key]);
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
