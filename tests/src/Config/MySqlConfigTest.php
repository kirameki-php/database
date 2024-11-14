<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Config;

use Kirameki\Database\Config\MySqlConfig;
use Tests\Kirameki\Database\DatabaseTestCase;
use function rand;

class MySqlConfigTest extends DatabaseTestCase
{
    public function test_getAdapterName(): void
    {
        $config = new MySqlConfig();
        $this->assertSame('mysql', $config->getAdapterName());
    }

    public function test_getDatabaseName(): void
    {
        $name = 'test_' . rand();
        $config = new MySqlConfig(database: $name);
        $this->assertSame($name, $config->getDatabaseName());
    }

    public function test_getTableSchema(): void
    {
        $name = 'test_' . rand();
        $config = new MySqlConfig(database: $name);
        $this->assertSame($name, $config->getTableSchema());
    }

    public function test_isReadOnly(): void
    {
        $config = new MySqlConfig();
        $this->assertFalse($config->isReadOnly());
        $config = new MySqlConfig(readOnly: true);
        $this->assertTrue($config->isReadOnly());
    }
}
