<?php declare(strict_types=1);

use Kirameki\Database\Migration\Migration;
use Kirameki\Database\Schema\Statements\CreateTableBuilder;

return new class extends Migration
{
    protected ?string $connection = 'migration_test';

    public function forward(): void
    {
        $this->createTable('User', function(CreateTableBuilder $t) {
            $t->uuid('id')->primaryKey()->nullable();
            $t->string('name', 100)->default('Anonymous');
            $t->timestamps();
            $t->uniqueIndex('name');
        });
    }

    public function backward(): void
    {
        $this->dropTable('User');
    }
};
