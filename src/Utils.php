<?php

namespace ObjectStorage;

use ObjectStorage\Service;
use Aws\S3\S3Client;
use RuntimeException;
use InvalidArgumentException;
use MongoClient;
use PDO;
use ObjectStorage\Adapter\EncryptionAdapter;
use ObjectStorage\Adapter\Bzip2Adapter;

class Utils
{
    public static function loadConfig($filename = null)
    {
        if (!$filename) {
            if (file_exists('objectstorage.conf')) {
                $filename = 'objectstorage.conf';
            } elseif (file_exists($_SERVER['HOME'] . '/.objectstorage.conf')) {
                $filename = $_SERVER['HOME'] . '/.objectstorage.conf';
            } elseif (file_exists('/etc/objectstorage.conf')) {
                $filename = '/etc/objectstorage.conf';
            } else {
                throw new RuntimeException("No configfile detected");
            }
        }

        if (!file_exists($filename)) {
            throw new RuntimeException("Config file not found");
        }
        $config = parse_ini_file($filename, true);
        if (!isset($config['general']['storageadapter'])) {
            throw new RuntimeException("Config file not valid, please check objectstore.conf.dist for an example");
        }
        return $config;
    }

    public static function getServiceFromConfig($config)
    {
        $adaptername = (string)$config['general']['storageadapter'];
        $adapterclassname = 'ObjectStorage\\Adapter\\' . $adaptername . 'Adapter';
        if (!class_exists($adapterclassname)) {
            throw new RuntimeException("Adapter class not found or supported: " . $adapterclassname);
        }
        //echo $adapterclassname;
        switch(strtolower($adaptername)) {
            case "s3":
                $s3client = null;
                $key = (string)$config['s3']['access_key'];
                $secret = (string)$config['s3']['secret_key'];
                if (trim($key)=='') {
                    throw new InvalidArgumentException("No access key provided for s3 adapter");
                }
                if (trim($secret)=='') {
                    throw new InvalidArgumentException("No secret key provided for s3 adapter");
                }
                $client = S3Client::factory(array(
                    'key' => $key,
                    'secret' => $secret
                ));
                $adapter = new $adapterclassname($client, $config['s3']['bucketname']);
                break;

            case "file":
                $path = $config['file']['path'];
                $adapter = new $adapterclassname($path);
                break;

            case "gridfs":
                $server = (string)$config['gridfs']['server'];
                if (trim($server)=='') {
                    $server = 'mongodb://localhost:27017';
                }


                $dbname = (string)$config['gridfs']['dbname'];
                if (trim($dbname)=='') {
                    throw new InvalidArgumentException("No dbname specified for gridfs adapter");
                }

                $mongoclient = new MongoClient($server);
                $db = $mongoclient->selectDB($dbname);
                $grid = $db->getGridFS();
                $adapter = new $adapterclassname($grid);
                break;

            case "pdo":
                $dsn = (string)$config['pdo']['dsn'];
                if (trim($dsn)=='') {
                    throw new InvalidArgumentException("No dsn specified for pdo adapter");
                }

                $tablename = (string)$config['pdo']['tablename'];
                if (trim($tablename)=='') {
                    throw new InvalidArgumentException("No tablename specified for pdo adapter");
                }

                $username = (string)$config['pdo']['username'];
                $password = (string)$config['pdo']['password'];

                $pdo = new PDO($dsn, $username, $password);    
                $adapter = new $adapterclassname($pdo, $tablename);
                break;
            default:
                throw new RuntimeException("Unsupported adapter: " . $adaptername);
                break;

        }

        if (isset($config['encryption'])) {
            $key = (string)$config['encryption']['key'];
            $iv = (string)$config['encryption']['iv'];
            
            // Wrap the real adapter into the encryption adapter
            $adapter = new EncryptionAdapter($adapter, $key, $iv);
        }

        if (isset($config['bzip2'])) {
            $level = (string)$config['bzip2']['level'];

            // Wrap the real adapter into the bzip2 compression adapter
            $adapter = new Bzip2Adapter($adapter, $level);
        }
        
        $service = new Service($adapter);
        return $service;
    }
}
