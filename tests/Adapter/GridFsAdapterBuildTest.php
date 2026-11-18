<?php

namespace ObjectStorage\Test\Adapter;

use InvalidArgumentException;
use MongoGridFS;
use ObjectStorage\Adapter\GridFsAdapter;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

require_once __DIR__.'/GridFsAdapterBuildDouble.php';

class GridFsAdapterBuildTest extends TestCase
{
    public function testBuildRejectsMissingServer()
    {
        $this->assertBuildRejects(
            ['dbname' => 'files'],
            'Unable to build GridFsAdapter: missing "server" from configuration.'
        );
    }

    public function testBuildRejectsMissingDbname()
    {
        $this->assertBuildRejects(
            ['server' => 'mongodb://localhost:27017'],
            'Unable to build GridFsAdapter: missing "dbname" from configuration.'
        );
    }

    public function testBuildRejectsBlankDbname()
    {
        $this->assertBuildRejects(
            [
                'server' => 'mongodb://localhost:27017',
                'dbname' => '   ',
            ],
            'Unable to build GridFsAdapter: missing "dbname" from configuration.'
        );
    }

    public function testBuildWithNullServerUsesTheDefaultServer()
    {
        $adapter = GridFsAdapter::build([
            'server' => null,
            'dbname' => 'files',
        ]);

        $this->assertGrid($adapter, 'mongodb://localhost:27017', 'files');
    }

    public function testBuildTrimsServerAndDbname()
    {
        $adapter = GridFsAdapter::build([
            'server' => '  mongodb://db.example:27017  ',
            'dbname' => '  files  ',
        ]);

        $this->assertGrid($adapter, 'mongodb://db.example:27017', 'files');
    }

    private function assertBuildRejects(array $config, $message)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        GridFsAdapter::build($config);
    }

    private function assertGrid(GridFsAdapter $adapter, $server, $dbname)
    {
        $gridfs = $this->gridfs($adapter);

        $this->assertInstanceOf(GridFsAdapter::class, $adapter);
        $this->assertInstanceOf(MongoGridFS::class, $gridfs);
        $this->assertSame($server, $gridfs->server);
        $this->assertSame($dbname, $gridfs->dbname);
    }

    private function gridfs(GridFsAdapter $adapter)
    {
        $property = new ReflectionProperty(GridFsAdapter::class, 'gridfs');
        $property->setAccessible(true);

        return $property->getValue($adapter);
    }
}
