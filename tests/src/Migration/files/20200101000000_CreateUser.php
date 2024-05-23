<?php declare(strict_types=1);

use Kirameki\Database\Migration\Migration;
use Kirameki\Database\Schema\Statements\CreateTableBuilder;

class CreateUser extends Migration
{
    public function forward(): void
    {
        $this->use('migration_test')
            ->createTable('User')->tap(function(CreateTableBuilder $t) {
                $t->uuid('id')->primaryKey()->nullable();
                $t->string('name', 100)->default('Anonymous');
                $t->timestamps();
                $t->index('name')->unique();
            });
    }

    public function backward(): void
    {
        $this->use('migration_test')
            ->dropTable('User');
    }
}
