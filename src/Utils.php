<?php

namespace ObjectStorage;

use InvalidArgumentException;
use RuntimeException;
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
                throw new RuntimeException('No configfile detected');
            }
        }

        if (!file_exists($filename)) {
            throw new RuntimeException('Config file not found');
        }
        $config = parse_ini_file($filename, true);
        if (!isset($config['general']['adapter'])) {
            throw new RuntimeException('Config file not valid, please check objectstore.conf.dist for an example');
        }

        return $config;
    }

    public static function getServiceFromConfig($config)
    {
        $adaptername = (string) $config['general']['adapter'];
        $adapterclassname = 'ObjectStorage\\Adapter\\' . ucfirst($adaptername) . 'Adapter';
        if (!class_exists($adapterclassname)) {
            throw new RuntimeException('Adapter class not found or supported: ' . $adapterclassname);
        }
        if (!array_key_exists($adaptername, $config)) {
            throw new InvalidArgumentException(
                "Unable to configure \"{$adaptername}\" adapter: missing \"{$adaptername}\" section from configuration."
            );
        }

        $adapter = $adapterclassname::build($config[$adaptername]);

        if (isset($config['encryption'])) {
            throw new RuntimeException('It is no longer possible to configure encrypted storage from objectstore.conf.');
        }

        if (isset($config['bzip2'])) {
            $level = (string) $config['bzip2']['level'];

            // Wrap the real adapter into the bzip2 compression adapter
            $adapter = new Bzip2Adapter($adapter, $level);
        }

        $service = new Service($adapter);

        return $service;
    }
}
