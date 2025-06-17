<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Schema;

class SchemaHandlerMySqlTest extends SchemaHandlerTestAbstract
{
    protected string $connection = 'mysql';

    public function test_createTable(): void
    {
        $handler = $this->connect()->schema();
        $table = $handler->createTable('temp');
        $table->int('id')->nullable()->primaryKey();
        $table->execute();

        $this->assertSame('CREATE TABLE "temp" ("id" BIGINT PRIMARY KEY);', $table->toDdl());
    }

    public function test_createTemporaryTable(): void
    {
        $handler = $this->connect()->schema();
        $table = $handler->createTemporaryTable('temp');
        $table->int('id')->nullable()->primaryKey();
        $table->execute();

        $this->assertSame('CREATE TEMPORARY TABLE "temp" ("id" BIGINT PRIMARY KEY);', $table->toDdl());
    }

    public function test_alterTable(): void
    {
        $handler = $this->connect()->schema();

        $table = $handler->createTemporaryTable('temp');
        $table->int('id')->nullable()->primaryKey();
        $table->execute();

        $table = $handler->alterTable('temp');
        $table->addColumn('name')->string(10);
        $table->execute();

        $this->assertSame('ALTER TABLE "temp" ADD COLUMN "name" VARCHAR(10) NOT NULL;', $table->toDdl());
    }

    public function test_renameTable(): void
    {
        $handler = $this->connect()->schema();

        $table = $handler->createTable('temp');
        $table->int('id')->nullable()->primaryKey();
        $table->execute();

        $table = $handler->renameTable('temp', 'new_temp');
        $table->execute();

        $this->assertSame('RENAME TABLE "temp" TO "new_temp";', $table->toDdl());
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

        $this->assertSame('RENAME TABLE "temp1" TO "new_temp1", "temp2" TO "new_temp2";', $builder->toDdl());
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

        $this->assertSame('DROP INDEX "idx_t1" ON "temp";', $drop->toDdl());
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

        $this->assertSame('DROP INDEX "idx_temp_name" ON "temp";', $drop->toDdl());
    }
}
