<?php

declare(strict_types=1);

namespace MagmaSoftwareEngineering\Rollout\Storage;

use PDO;

/**
 * Storage adapter using PDO.
 *
 * @author Baldur Rensch <brensch@gmail.com>
 */
class PDOStorageAdapter implements StorageInterface
{
    private const STMT_SELECT = 'SELECT settings FROM :table WHERE name = :key';
    private const STMT_INSERT = 'INSERT INTO :table (name, settings) VALUES (:key, :value)';
    private const STMT_UPDATE = 'UPDATE :table SET settings = :value WHERE name = :key';
    private const STMT_DELETE = 'DELETE FROM :table WHERE name = :key';

    public function __construct(
        private readonly PDO $pdoConnection,
        private readonly string $tableName = 'rollout_feature',
    ) {}

    #[\Override]
    public function get(string $key): ?string
    {
        $statement = $this->pdoConnection->prepare($this->getSQLStatement(self::STMT_SELECT));
        $statement->bindParam(':key', $key);
        $statement->setFetchMode(PDO::FETCH_ASSOC);
        $statement->execute();

        $result = $statement->fetch();
        if ($result === false) {
            return null;
        }

        return (string) $result['settings'];
    }

    #[\Override]
    public function set(string $key, string $value): void
    {
        $sql = $this->get($key) === null ? self::STMT_INSERT : self::STMT_UPDATE;

        $statement = $this->pdoConnection->prepare($this->getSQLStatement($sql));
        $statement->bindParam(':key', $key);
        $statement->bindParam(':value', $value);
        $statement->execute();
    }

    #[\Override]
    public function remove(string $key): void
    {
        $statement = $this->pdoConnection->prepare($this->getSQLStatement(self::STMT_DELETE));
        $statement->bindParam(':key', $key);
        $statement->execute();
    }

    private function getSQLStatement(string $sql): string
    {
        return str_replace(':table', $this->tableName, $sql);
    }
}
