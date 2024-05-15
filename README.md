# Database library for PHP

![Test](https://github.com/kirameki-php/database/actions/workflows/test.yml/badge.svg)
[![codecov](https://codecov.io/gh/kirameki-php/time/branch/main/graph/badge.svg?token=1PV8FB4O4O)](https://codecov.io/gh/kirameki-php/database)
![GitHub](https://img.shields.io/github/license/kirameki-php/database)

## Prerequisites

- PHP 8.2+

## Installation

```
composer require kirameki/database
```

# SQL Database Differences

## Session Level Timeout

| Database    | Supported | Description                                                                                                                 | Query                                           |
|-------------|-----------|-----------------------------------------------------------------------------------------------------------------------------|-------------------------------------------------|
| SQLite      | ╳         | No option exists.                                                                                                           |                                                 |
| PostgreSQL  | ◯         | Works. ([Docs](https://www.postgresql.org/docs/current/runtime-config-client.html#GUC-STATEMENT-TIMEOUT))                   | `SET statement_timeout={milliseconds}`          |
| MySQL       | △         | Only works for SELECT. ([Docs](https://dev.mysql.com/doc/refman/en/server-system-variables.html#sysvar_max_execution_time)) | `SET SESSION max_execution_time={milliseconds}` |
| MariaDB     | ◯         | Works. ([Docs](https://mariadb.com/kb/en/server-system-variables/#max_statement_time))                                      | `SET max_statement_time={seconds}`              |

## Upsert

| Database    | Supported | Description                                                                                                                                                                     | Query                                        |
|-------------|-----------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|----------------------------------------------|
| SQLite      | ◯         | Works. ([Docs](https://www.sqlite.org/lang_upsert.html))                                                                                                                        | `INSERT INTO … ON CONFLICT … DO UPDATE SET…` |
| PostgreSQL  | ◯         | Works. ([Docs](https://www.postgresql.org/docs/current/sql-insert.html))                                                                                                        | `INSERT INTO … ON CONFLICT … DO UPDATE SET…` |
| MySQL       | △         | Does not work as expected on tables with multiple unique indexes.<br>Use with caution. Read the docs carefully. ([Docs](https://dev.mysql.com/doc/en/insert-on-duplicate.html)) | `INSERT INTO … ON DUPLICATE KEY UPDATE …`    |
| MariaDB     | △         | *Same as MySQL* ([Docs](https://mariadb.com/kb/en/insert-on-duplicate-key-update))                                                                                              | *Same as MySQL*                              |


## License

This is an open-sourced software licensed under the [MIT License](LICENSE).
