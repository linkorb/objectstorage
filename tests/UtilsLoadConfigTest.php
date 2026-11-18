<?php

namespace ObjectStorage\Test;

use ObjectStorage\Utils;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class UtilsLoadConfigTest extends TestCase
{
    private $createdEtcConfig = false;
    private $homeDirectory;
    private $originalCwd;
    private $originalHome;
    private $root;
    private $workDirectory;

    protected function setUp(): void
    {
        $this->originalCwd = getcwd();
        $this->originalHome = isset($_SERVER['HOME']) ? $_SERVER['HOME'] : null;
        $this->root = sys_get_temp_dir() . '/objectstorage-loadconfig-' . bin2hex(random_bytes(4));
        $this->workDirectory = $this->root . '/work';
        $this->homeDirectory = $this->root . '/home';

        mkdir($this->workDirectory, 0777, true);
        mkdir($this->homeDirectory, 0777, true);
        chdir($this->workDirectory);
        $_SERVER['HOME'] = $this->homeDirectory;
    }

    protected function tearDown(): void
    {
        if (is_string($this->originalCwd) && $this->originalCwd !== '') {
            chdir($this->originalCwd);
        }

        if ($this->originalHome === null) {
            unset($_SERVER['HOME']);
        } else {
            $_SERVER['HOME'] = $this->originalHome;
        }

        if ($this->createdEtcConfig && is_file('/etc/objectstorage.conf')) {
            unlink('/etc/objectstorage.conf');
        }

        $this->removeTree($this->root);
    }

    public function testLoadConfigReturnsParsedSectionsForAnExplicitFile()
    {
        $this->writeConfig(
            $this->workDirectory . '/objectstorage.conf',
            $this->iniWithAdapter('s3')
        );
        $given = $this->workDirectory . '/given.conf';
        $this->writeConfig(
            $given,
            "[general]\nadapter = file\n\n[file]\npath = \"/my/objectstorage/path\"\n"
        );

        $this->assertSame(
            [
                'general' => [
                    'adapter' => 'file',
                ],
                'file' => [
                    'path' => '/my/objectstorage/path',
                ],
            ],
            Utils::loadConfig($given)
        );
    }

    public function testLoadConfigThrowsWhenExplicitFileIsMissing()
    {
        $this->writeConfig(
            $this->workDirectory . '/objectstorage.conf',
            $this->iniWithAdapter('file')
        );

        try {
            Utils::loadConfig($this->workDirectory . '/missing.conf');
            $this->fail('Expected RuntimeException when the given config file is missing.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Config file not found', $exception->getMessage());
        }
    }

    public function testLoadConfigThrowsWhenAdapterIsMissing()
    {
        $given = $this->workDirectory . '/given.conf';
        $this->writeConfig($given, "[general]\npath = /tmp\n");

        try {
            Utils::loadConfig($given);
            $this->fail('Expected RuntimeException when general.adapter is missing.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Config file not valid, please check objectstore.conf.dist for an example',
                $exception->getMessage()
            );
        }
    }

    public function testLoadConfigReadsWorkingDirectoryConfigBeforeHomeConfig()
    {
        $this->writeConfig(
            $this->workDirectory . '/objectstorage.conf',
            $this->iniWithAdapter('file')
        );
        $this->writeConfig(
            $this->homeDirectory . '/.objectstorage.conf',
            $this->iniWithAdapter('s3')
        );

        $this->assertSame(
            [
                'general' => [
                    'adapter' => 'file',
                ],
            ],
            Utils::loadConfig()
        );
    }

    public function testLoadConfigReadsHomeConfigWhenWorkingDirectoryHasNone()
    {
        $this->writeConfig(
            $this->homeDirectory . '/.objectstorage.conf',
            $this->iniWithAdapter('s3')
        );
        $this->writeEtcConfig($this->iniWithAdapter('pdo'));

        $this->assertSame(
            [
                'general' => [
                    'adapter' => 's3',
                ],
            ],
            Utils::loadConfig()
        );
    }

    public function testLoadConfigReadsEtcConfigWhenNoLocalOrHomeConfig()
    {
        $this->writeEtcConfig($this->iniWithAdapter('pdo'));

        $this->assertSame(
            [
                'general' => [
                    'adapter' => 'pdo',
                ],
            ],
            Utils::loadConfig()
        );
    }

    public function testLoadConfigThrowsWhenNoConfigFileIsDetected()
    {
        try {
            Utils::loadConfig();
            $this->fail('Expected RuntimeException when no config file is detected.');
        } catch (RuntimeException $exception) {
            $this->assertSame('No configfile detected', $exception->getMessage());
        }
    }

    private function iniWithAdapter($adapter)
    {
        return "[general]\nadapter = " . $adapter . "\n";
    }

    private function writeConfig($path, $contents)
    {
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        if (file_put_contents($path, $contents) === false) {
            $this->fail('Could not write config file ' . $path);
        }
    }

    private function writeEtcConfig($contents)
    {
        if (file_exists('/etc/objectstorage.conf')) {
            $this->fail('Refusing to overwrite an existing /etc/objectstorage.conf.');
        }

        $this->writeConfig('/etc/objectstorage.conf', $contents);
        $this->createdEtcConfig = true;
    }

    private function removeTree($directory)
    {
        if (!is_string($directory) || !is_dir($directory)) {
            return;
        }

        $items = scandir($directory);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . '/' . $item;
            if (is_dir($path)) {
                $this->removeTree($path);
            } else {
                unlink($path);
            }
        }

        rmdir($directory);
    }
}
