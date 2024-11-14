<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Config;

use Kirameki\Database\Config\SqliteConfig;
use Tests\Kirameki\Database\DatabaseTestCase;

class SqliteConfigTest extends DatabaseTestCase
{
    public function test_getAdapterName(): void
    {
        $config = new SqliteConfig(':memory:');
        $this->assertSame('sqlite', $config->getAdapterName());
    }

    public function test_getDatabaseName(): void
    {
        $name = '/run/test.db';
        $config = new SqliteConfig($name);
        $this->assertSame($name, $config->getDatabaseName());
    }

    public function test_getTableSchema(): void
    {
        $config = new SqliteConfig(':memory:');
        $this->assertSame('sqlite', $config->getTableSchema());
    }

    public function test_isReadOnly(): void
    {
        $config = new SqliteConfig(':memory:');
        $this->assertFalse($config->isReadOnly());
        $config = new SqliteConfig(':memory:', readOnly: true);
        $this->assertTrue($config->isReadOnly());
    }
}
