# Database library for PHP

![Test](https://github.com/kirameki-php/database/actions/workflows/test.yml/badge.svg)
[![codecov](https://codecov.io/gh/kirameki-php/database/branch/main/graph/badge.svg?token=1PV8FB4O4O)](https://codecov.io/gh/kirameki-php/database)
![GitHub](https://img.shields.io/github/license/kirameki-php/database)

## Prerequisites

- PHP 8.2+

## Installation

```
composer require kirameki/database
```

# Isolation level

Kirameki will set the isolation level to `SERIALIZABLE` for all transactions.
Lower isolation level is [risky and is not worth the small gain in performance for most apps](https://fauna.com/blog/introduction-to-transaction-isolation-levels#what-isolation-level-should-you-choose).
You can change the isolation level by passing `IsolationLevel` to a `transaction(...)`.

# SQL Database Nuances

## Session Level Timeout

| Database    | Supported | Description                                                                                                                 | Query                                           |
|-------------|-----------|-----------------------------------------------------------------------------------------------------------------------------|-------------------------------------------------|
| SQLite      | ╳         | No option exists.                                                                                                           |                                                 |
| PostgreSQL  | ◯         | Works. ([Docs](https://www.postgresql.org/docs/current/runtime-config-client.html#GUC-STATEMENT-TIMEOUT))                   | `SET statement_timeout={milliseconds}`          |
| MySQL       | △         | Only works for SELECT. ([Docs](https://dev.mysql.com/doc/refman/en/server-system-variables.html#sysvar_max_execution_time)) | `SET SESSION max_execution_time={milliseconds}` |
| MariaDB     | ◯         | Works. ([Docs](https://mariadb.com/kb/en/server-system-variables/#max_statement_time))                                      | `SET max_statement_time={seconds}`              |

## Isolation Level Changes Per Transaction

| Database    | Supported | Description                                                                                                              | Query                                    |
|-------------|-----------|--------------------------------------------------------------------------------------------------------------------------|------------------------------------------|
| SQLite      | ╳         | Only supports read_uncommitted per connection via `PRAGMA`.                                                              |                                          |
| PostgreSQL  | ◯         | Works. Must call within the open transaction. ([Docs](https://www.postgresql.org/docs/current/sql-set-transaction.html)) | `SET TRANSACTION {mode}`                 |
| MySQL       | ◯         | Works. Must call before BEGIN. ([Docs](https://dev.mysql.com/doc/en/set-transaction.html))                               | `SET TRANSACTION ISOLATION LEVEL {mode}` |
| MariaDB     | ◯         | *Same as MySQL* ([Docs](https://mariadb.com/kb/en/set-transaction/))                                                     | *Same as MySQL*                          |

## Upsert

| Database    | Supported | Description                                                                                                                                                                     | Query                                        |
|-------------|-----------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|----------------------------------------------|
| SQLite      | ◯         | Works. ([Docs](https://www.sqlite.org/lang_upsert.html))                                                                                                                        | `INSERT INTO … ON CONFLICT … DO UPDATE SET…` |
| PostgreSQL  | ◯         | Works. ([Docs](https://www.postgresql.org/docs/current/sql-insert.html))                                                                                                        | `INSERT INTO … ON CONFLICT … DO UPDATE SET…` |
| MySQL       | △         | Does not work as expected on tables with multiple unique indexes.<br>Use with caution. Read the docs carefully. ([Docs](https://dev.mysql.com/doc/en/insert-on-duplicate.html)) | `INSERT INTO … ON DUPLICATE KEY UPDATE …`    |
| MariaDB     | △         | *Same as MySQL* ([Docs](https://mariadb.com/kb/en/insert-on-duplicate-key-update))                                                                                              | *Same as MySQL*                              |

## License

This is an open-sourced software licensed under the [MIT License](LICENSE).
