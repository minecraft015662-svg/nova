<?php

declare(strict_types=1);

namespace Nexa;

use PDO;

final class Database
{
    private PDO $pdo;

    public function __construct(private readonly string $path)
    {
        $directory = dirname($this->path);
        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException('Could not create the database directory: ' . $directory);
        }

        $this->pdo = new PDO('sqlite:' . $this->path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        $this->pdo->exec('PRAGMA journal_mode = WAL');
        $this->pdo->exec('PRAGMA foreign_keys = ON');
        $this->pdo->exec('PRAGMA busy_timeout = 5000');

        $this->migrate();
    }

    private function migrate(): void
    {
        $this->pdo->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                role TEXT NOT NULL DEFAULT 'admin',
                created_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS licenses (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                key_hash TEXT NOT NULL UNIQUE,
                key_prefix TEXT NOT NULL,
                plan TEXT NOT NULL DEFAULT 'pro',
                email TEXT NOT NULL DEFAULT '',
                note TEXT NOT NULL DEFAULT '',
                max_activations INTEGER NOT NULL DEFAULT 1,
                status TEXT NOT NULL DEFAULT 'active',
                expires_at TEXT NULL,
                last_checked_at TEXT NULL,
                created_by INTEGER NULL,
                created_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS license_activations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                license_id INTEGER NOT NULL,
                domain TEXT NOT NULL,
                instance_id TEXT NOT NULL DEFAULT '',
                ip_hash TEXT NOT NULL DEFAULT '',
                created_at TEXT NOT NULL,
                last_seen_at TEXT NOT NULL,
                UNIQUE (license_id, domain, instance_id),
                FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS api_keys (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                key_hash TEXT NOT NULL UNIQUE,
                key_prefix TEXT NOT NULL,
                scopes TEXT NOT NULL DEFAULT '["*"]',
                status TEXT NOT NULL DEFAULT 'active',
                last_used_at TEXT NULL,
                created_by INTEGER NULL,
                created_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS bots (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                server_id INTEGER NOT NULL,
                server_uuid TEXT NOT NULL,
                server_identifier TEXT NOT NULL,
                node_id INTEGER NOT NULL DEFAULT 0,
                nest_id INTEGER NOT NULL DEFAULT 0,
                egg_id INTEGER NOT NULL DEFAULT 0,
                memory INTEGER NOT NULL DEFAULT 512,
                disk INTEGER NOT NULL DEFAULT 2048,
                cpu INTEGER NOT NULL DEFAULT 100,
                owner_id INTEGER NOT NULL DEFAULT 0,
                owner_email TEXT NOT NULL DEFAULT '',
                status TEXT NOT NULL DEFAULT 'installing',
                status_checked_at TEXT NULL,
                created_by INTEGER NULL,
                created_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS settings (
                key TEXT PRIMARY KEY,
                value TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS rate_limits (
                bucket TEXT PRIMARY KEY,
                hits INTEGER NOT NULL DEFAULT 0,
                reset_at INTEGER NOT NULL
            );

            CREATE INDEX IF NOT EXISTS idx_licenses_status ON licenses(status);
            CREATE INDEX IF NOT EXISTS idx_activations_license ON license_activations(license_id);
            CREATE INDEX IF NOT EXISTS idx_api_keys_status ON api_keys(status);
            CREATE INDEX IF NOT EXISTS idx_bots_server ON bots(server_id);
            SQL);

        if (random_int(1, 100) === 1) {
            $this->pdo->exec('DELETE FROM rate_limits WHERE reset_at < ' . (time() - 3600));
        }
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function run(string $sql, array $params = []): \PDOStatement
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return $statement;
    }

    public function get(string $sql, array $params = []): ?array
    {
        $row = $this->run($sql, $params)->fetch();

        return $row === false ? null : $row;
    }

    public function all(string $sql, array $params = []): array
    {
        return $this->run($sql, $params)->fetchAll();
    }

    public function value(string $sql, array $params = []): mixed
    {
        $value = $this->run($sql, $params)->fetchColumn();

        return $value === false ? null : $value;
    }

    public function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_map(static fn (string $column): string => ':' . $column, $columns);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $params = [];
        foreach ($data as $column => $value) {
            $params[':' . $column] = $value;
        }

        $this->run($sql, $params);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, string $where, array $whereParams = []): void
    {
        $sets = [];
        $params = [];
        foreach ($data as $column => $value) {
            $sets[] = $column . ' = :' . $column;
            $params[':' . $column] = $value;
        }

        foreach ($whereParams as $key => $value) {
            $params[$key] = $value;
        }

        $this->run(
            sprintf('UPDATE %s SET %s WHERE %s', $table, implode(', ', $sets), $where),
            $params
        );
    }

    public function transaction(callable $callback): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $result = $callback();
            $this->pdo->commit();

            return $result;
        } catch (\Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }
}
