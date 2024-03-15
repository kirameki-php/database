<?php declare(strict_types=1);

use Kirameki\Database\Migration\Migration;
use Kirameki\Database\Statements\Schema\CreateTableBuilder;

class CreateUser extends Migration
{
    public function up(): void
    {
        $this->use('migration_test')
            ->createTable('User')->tap(function(CreateTableBuilder $t) {
                $t->uuid('id')->primaryKey()->notNull();
                $t->string('name', 100)->default('Anonymous');
                $t->timestamps();
                $t->index('name')->unique();
            });
    }

    public function down(): void
    {
        $this->use('migration_test')
            ->dropTable('User');
    }
}
