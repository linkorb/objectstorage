<?php

namespace LinkORB\Component\ObjectStorage\Driver;

use InvalidArgumentException;

class PdoDriver implements DriverInterface
{
    private $pdo = null;
    private $tablename = 'objectstorage';

    public function __construct($pdo, $tablename = 'objectstorage')
    {
        $this->setPdo($pdo);
        $this->setTablename($tablename);
    }
    
    public function setPdo($pdo)
    {
        $this->pdo = $pdo;
    }
    
    public function setTablename($tablename)
    {
        $this->tablename = $tablename;
    }
    
    public function set($key, $data)
    {
        $sql = sprintf(
            "INSERT INTO `%s`
            (`objectkey`, `objectdata`)
            VALUES
            ('%s', '%s')
            ON DUPLICATE KEY UPDATE
            `objectdata` = '%s'",
            $this->tablename,
            $key, $data,
            $data
        );
        $this->pdo->query($sql);
    }

    public function get($key)
    {
        $sql = sprintf(
            "SELECT objectdata FROM `%s` WHERE objectkey = '%s'",
            $this->tablename,
            $key
        );
        $res = $this->pdo->query($sql);
        foreach($res as $r) {
            return (string)$r['objectdata'];
        }
        return '';
    }
    
    public function delete($key)
    {
        $sql = sprintf(
            "DELETE FROM `%s` WHERE objectkey = '%s'",
            $this->tablename,
            $key
        );
        $res = $this->pdo->query($sql);
    }

}
