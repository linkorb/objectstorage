<?php

namespace LinkORB\Component\ObjectStorage;

use LinkORB\Component\ObjectStorage\Client;
use Aws\S3\S3Client;
use RuntimeException;
use InvalidArgumentException;
use MongoClient;
use PDO;

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
        if (!isset($config['general']['driver'])) {
            throw new RuntimeException("Config file not valid, please check objectstore.conf.dist for an example");
        }
        return $config;
    }

    public static function getClientFromConfig($config)
    {
        $drivername = (string)$config['general']['driver'];
        $driverclassname = 'LinkORB\\Component\\ObjectStorage\\Driver\\' . $drivername . 'Driver';
        if (!class_exists($driverclassname)) {
            throw new RuntimeException("Driver class not found or supported: " . $driverclassname);
        }
        echo $driverclass;
        switch(strtolower($drivername)) {
            case "s3":
                $s3client = null;
                $key = (string)$config['s3']['access_key'];
                $secret = (string)$config['s3']['secret_key'];
                if (trim($key)=='') {
                    throw new InvalidArgumentException("No access key provided for s3 driver");
                }
                if (trim($secret)=='') {
                    throw new InvalidArgumentException("No secret key provided for s3 driver");
                }
                $client = S3Client::factory(array(
                    'key' => $key,
                    'secret' => $secret
                ));
                $driver = new $driverclassname($client, $config['s3']['bucketname']);
                break;

            case "file":
                $path = $config['file']['path'];
                $driver = new $driverclassname($path);
                break;

            case "gridfs":
                $server = (string)$config['gridfs']['server'];
                if (trim($server)=='') {
                    $server = 'mongodb://localhost:27017';
                }


                $dbname = (string)$config['gridfs']['dbname'];
                if (trim($dbname)=='') {
                    throw new InvalidArgumentException("No dbname specified for gridfs driver");
                }

                $mongoclient = new MongoClient($server);
                $db = $mongoclient->selectDB($dbname);
                $grid = $db->getGridFS();
                $driver = new $driverclassname($grid);
                break;

            case "pdo":
                $dsn = (string)$config['pdo']['dsn'];
                if (trim($dsn)=='') {
                    throw new InvalidArgumentException("No dsn specified for pdo driver");
                }

                $tablename = (string)$config['pdo']['tablename'];
                if (trim($tablename)=='') {
                    throw new InvalidArgumentException("No tablename specified for pdo driver");
                }

                $username = (string)$config['pdo']['username'];
                $password = (string)$config['pdo']['password'];

                $pdo = new PDO($dsn, $username, $password);    
                $driver = new $driverclassname($pdo, $tablename);
                break;
            default:
                throw new RuntimeException("Unsupported driver: " . $drivername);
                break;

        }
        $client = new Client($driver);
        return $client;
    }
}
