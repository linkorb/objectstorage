<?php

namespace ObjectStorage\Test\Adapter;

use InvalidArgumentException;
use ObjectStorage\Adapter\PdoAdapter;
use PDO;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class PdoAdapterBuildTest extends TestCase
{
    public function testBuildRejectsMissingDsn()
    {
        $this->assertBuildRejects(
            ['tablename' => 'items'],
            'Unable to build PdoAdapter: missing "dsn" from configuration.'
        );
    }

    public function testBuildRejectsBlankDsn()
    {
        $this->assertBuildRejects(
            [
                'dsn' => '   ',
                'tablename' => 'items',
            ],
            'Unable to build PdoAdapter: missing "dsn" from configuration.'
        );
    }

    public function testBuildRejectsMissingTablename()
    {
        $this->assertBuildRejects(
            ['dsn' => 'sqlite::memory:'],
            'Unable to build PdoAdapter: missing "tablename" from configuration.'
        );
    }

    public function testBuildRejectsBlankTablename()
    {
        $this->assertBuildRejects(
            [
                'dsn' => 'sqlite::memory:',
                'tablename' => '   ',
            ],
            'Unable to build PdoAdapter: missing "tablename" from configuration.'
        );
    }

    public function testBuildWithCredentialsReturnsAdapterUsingTrimmedDsnAndTablename()
    {
        $adapter = PdoAdapter::build([
            'dsn' => '  sqlite::memory:  ',
            'tablename' => '  items  ',
            'username' => '  dbuser  ',
            'password' => '  secret  ',
        ]);

        $this->assertInstanceOf(PdoAdapter::class, $adapter);
        $this->assertSame('items', $this->property($adapter, 'tablename'));
        $this->assertSqlite($this->property($adapter, 'pdo'));
    }

    public function testBuildWithoutCredentialsReturnsAdapter()
    {
        $adapter = PdoAdapter::build([
            'dsn' => 'sqlite::memory:',
            'tablename' => 'objects',
        ]);

        $this->assertInstanceOf(PdoAdapter::class, $adapter);
        $this->assertSame('objects', $this->property($adapter, 'tablename'));
        $this->assertSqlite($this->property($adapter, 'pdo'));
    }

    private function assertBuildRejects(array $config, $message)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        PdoAdapter::build($config);
    }

    private function assertSqlite($pdo)
    {
        $this->assertInstanceOf(PDO::class, $pdo);
        $this->assertSame('sqlite', $pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
    }

    private function property(PdoAdapter $adapter, $name)
    {
        $property = new ReflectionProperty(PdoAdapter::class, $name);
        $property->setAccessible(true);

        return $property->getValue($adapter);
    }
}
