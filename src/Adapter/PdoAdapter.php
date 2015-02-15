<?php

namespace ObjectStorage\Adapter;

use PDO;
use ObjectStorage\Key;
use InvalidArgumentException;

class PdoAdapter implements StorageAdapterInterface
{
    private $pdo;
    private $tablename;

    public function __construct(PDO $pdo, $tablename = 'objectstorage')
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
        if (!ctype_alnum($tablename)) {
            throw new InvalidArgumentException("Only alphanumeric tablenames allowed");
        }
        $this->tablename = $tablename;
    }
    
    public function setData($key, $data)
    {
        $statement = $this->pdo->prepare(
            "INSERT INTO " . $this->tablename . "
            (`objectkey`, `objectdata`)
            VALUES
            (:key, :data)
            ON DUPLICATE KEY UPDATE
            `objectdata` = :data"
        );
        $statement->bindParam(":data", $data, PDO::PARAM_STR);
        $statement->bindParam(":key", $key, PDO::PARAM_STR);
        $statement->execute();
    }

    public function getData($key)
    {
        $statement = $this->pdo->prepare(
            "SELECT objectdata FROM " . $this->tablename . "
            WHERE objectkey = :key"
        );
        
        $statement->bindParam(":key", $key, PDO::PARAM_STR);
        $statement->execute();
        $res = $statement->fetchAll();
        foreach($res as $r) {
            return (string)$r['objectdata'];
        }
        return null;
    }
    
    public function deleteData($key)
    {
        $statement = $this->pdo->prepare(
            "DELETE FROM " . $this->tablename . "
            WHERE objectkey = :key"
        );
        
        $statement->bindParam(":key", $key, PDO::PARAM_STR);
        $statement->execute();
    }
}
