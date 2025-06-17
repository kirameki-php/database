<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Schema;

use function implode;
use const PHP_EOL;

class SchemaHandlerSqliteTest extends SchemaHandlerTestAbstract
{
    protected string $connection = 'sqlite';

    public function test_createTable(): void
    {
        $handler = $this->connect()->schema();
        $table = $handler->createTable('temp');
        $table->int('id')->nullable()->primaryKey();
        $table->execute();

        $this->assertSame('CREATE TABLE "temp" ("id" INTEGER PRIMARY KEY) WITHOUT ROWID;', $table->toDdl());
    }

    public function test_createTemporaryTable(): void
    {
        $handler = $this->connect()->schema();
        $table = $handler->createTemporaryTable('temp');
        $table->int('id')->nullable()->primaryKey();
        $table->execute();

        $this->assertSame('CREATE TEMPORARY TABLE "temp" ("id" INTEGER PRIMARY KEY) WITHOUT ROWID;', $table->toDdl());
    }

    public function test_alterTable(): void
    {
        $handler = $this->connect()->schema();

        $table = $handler->createTable('temp');
        $table->int('id')->nullable()->primaryKey();
        $table->execute();

        $table = $handler->alterTable('temp');
        $table->addColumn('name')->string(10);
        $table->execute();

        $this->assertSame('ALTER TABLE "temp" ADD COLUMN "name" TEXT CHECK (length("name") <= 10) NOT NULL;', $table->toDdl());
    }

    public function test_renameTable(): void
    {
        $handler = $this->connect()->schema();

        $table = $handler->createTable('temp');
        $table->int('id')->nullable()->primaryKey();
        $table->execute();

        $table = $handler->renameTable('temp', 'new_temp');
        $table->execute();

        $this->assertSame('ALTER TABLE "temp" RENAME TO "new_temp";', $table->toDdl());
    }

    public function test_renameTables(): void
    {
        $handler = $this->connect()->schema();

        $table1 = $handler->createTable('temp1');
        $table1->int('id')->nullable()->primaryKey();
        $table1->execute();

        $table2 = $handler->createTable('temp2');
        $table2->int('id')->nullable()->primaryKey();
        $table2->execute();

        $builder = $handler->renameTables()
            ->rename('temp1', 'new_temp1')
            ->rename('temp2', 'new_temp2');
        $builder->execute();

        $this->assertSame(implode(PHP_EOL, [
            'ALTER TABLE "temp1" RENAME TO "new_temp1";',
            'ALTER TABLE "temp2" RENAME TO "new_temp2";'
        ]), $builder->toDdl());
    }

    public function test_dropIndexByName(): void
    {
        $handler = $this->connect()->schema();
        $table = $handler->createTable('temp');
        $table->int('id')->nullable()->primaryKey();
        $table->string('name');
        $table->execute();

        $index = $handler->createIndex('temp', ['name'])->name('idx_t1');
        $index->execute();

        $drop = $handler->dropIndexByName('temp', 'idx_t1');
        $drop->execute();

        $this->assertSame('DROP INDEX "idx_t1";', $drop->toDdl());
    }

    public function test_dropIndexByColumns(): void
    {
        $handler = $this->connect()->schema();
        $table = $handler->createTable('temp');
        $table->int('id')->nullable()->primaryKey();
        $table->string('name');
        $table->execute();

        $index = $handler->createIndex('temp', ['name']);
        $index->execute();

        $drop = $handler->dropIndexByColumns('temp', ['name']);
        $drop->execute();

        $this->assertSame('DROP INDEX "idx_temp_name";', $drop->toDdl());
    }
}
