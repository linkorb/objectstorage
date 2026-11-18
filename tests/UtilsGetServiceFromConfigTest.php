<?php

namespace ObjectStorage\Test;

use InvalidArgumentException;
use ObjectStorage\Service;
use ObjectStorage\Utils;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class UtilsGetServiceFromConfigTest extends TestCase
{
    private $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/objectstorage-getservice-' . bin2hex(random_bytes(4));
        mkdir($this->root, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->root);
    }

    public function testGetServiceFromConfigThrowsWhenAdapterClassIsMissing()
    {
        try {
            Utils::getServiceFromConfig([
                'general' => [
                    'adapter' => 'missing',
                ],
            ]);
            $this->fail('Expected RuntimeException when the adapter class does not exist.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Adapter class not found or supported: ObjectStorage\\Adapter\\MissingAdapter',
                $exception->getMessage()
            );
        }
    }

    public function testGetServiceFromConfigThrowsWhenAdapterSectionIsMissing()
    {
        try {
            Utils::getServiceFromConfig([
                'general' => [
                    'adapter' => 'file',
                ],
            ]);
            $this->fail('Expected InvalidArgumentException when the adapter section is missing.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame(
                'Unable to configure "file" adapter: missing "file" section from configuration.',
                $exception->getMessage()
            );
        }
    }

    public function testGetServiceFromConfigThrowsWhenEncryptionSectionIsPresent()
    {
        $path = $this->storagePath();

        try {
            Utils::getServiceFromConfig($this->fileConfig($path, [
                'encryption' => [
                    'key' => 'unused',
                ],
            ]));
            $this->fail('Expected RuntimeException when encryption is configured.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'It is no longer possible to configure encrypted storage from objectstore.conf.',
                $exception->getMessage()
            );
        }
    }

    public function testGetServiceFromConfigWrapsTheAdapterWhenBzip2IsConfigured()
    {
        $path = $this->storagePath();
        $service = Utils::getServiceFromConfig($this->fileConfig($path, [
            'bzip2' => [
                'level' => '1',
            ],
        ]));

        $this->assertInstanceOf(Service::class, $service);
        $service->set('folder/name.txt', 'hello');

        $this->assertSame(bzcompress('hello', 1), file_get_contents($path . 'folder/name.txt'));
        $this->assertSame('hello', $service->get('folder/name.txt'));
    }

    public function testGetServiceFromConfigReturnsAServiceForTheConfiguredAdapter()
    {
        $path = $this->storagePath();
        $service = Utils::getServiceFromConfig($this->fileConfig($path));

        $this->assertInstanceOf(Service::class, $service);
        $service->set('folder/name.txt', 'hello');

        $this->assertSame('hello', file_get_contents($path . 'folder/name.txt'));
        $this->assertSame('hello', $service->get('folder/name.txt'));
    }

    private function storagePath()
    {
        $path = $this->root . '/storage/';
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        return $path;
    }

    private function fileConfig($path, array $extra = [])
    {
        return array_merge([
            'general' => [
                'adapter' => 'file',
            ],
            'file' => [
                'path' => $path,
            ],
        ], $extra);
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
