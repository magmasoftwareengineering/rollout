<?php

declare(strict_types=1);

namespace MagmaSoftwareEngineering\Rollout\Tests\Unit\Storage;

use Codeception\Test\Unit;
use MagmaSoftwareEngineering\Rollout\Storage\PDOStorageAdapter;
use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\Test;

/**
 * Mock-driven tests. The PDOStorageAdapter against a real DB is exercised
 * via integration tests when those land; this suite verifies the SQL shape
 * and the get/set/remove dispatch logic in isolation.
 */
class PDOStorageAdapterTest extends Unit
{
    #[Test]
    public function getDispatchesSelectStatement(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->expects(self::once())->method('bindParam')->with(':key', 'test');
        $statement->expects(self::once())->method('execute');
        $statement->expects(self::once())
            ->method('fetch')
            ->willReturn(['settings' => 'success']);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects(self::once())
            ->method('prepare')
            ->with($this->expectedSql('SELECT settings FROM rollout_feature WHERE name = :key'))
            ->willReturn($statement);

        $adapter = new PDOStorageAdapter($pdo);

        self::assertSame('success', $adapter->get('test'));
    }

    #[Test]
    public function getReturnsNullWhenNoRow(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('fetch')->willReturn(false);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($statement);

        $adapter = new PDOStorageAdapter($pdo);

        self::assertNull($adapter->get('missing'));
    }

    #[Test]
    public function customTableNameIsInjectedIntoSql(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('fetch')->willReturn(false);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects(self::once())
            ->method('prepare')
            ->with($this->expectedSql('SELECT settings FROM rollout_feature2 WHERE name = :key'))
            ->willReturn($statement);

        $adapter = new PDOStorageAdapter($pdo, 'rollout_feature2');

        $adapter->get('test');
    }

    #[Test]
    public function setIssuesInsertWhenKeyMissing(): void
    {
        $getStatement = $this->createMock(PDOStatement::class);
        $getStatement->method('fetch')->willReturn(false);

        $setStatement = $this->createMock(PDOStatement::class);
        $setStatement->expects(self::exactly(2))->method('bindParam');
        $setStatement->expects(self::once())->method('execute');

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturnCallback(static function (string $sql) use ($getStatement, $setStatement) {
            return str_starts_with($sql, 'SELECT') ? $getStatement : $setStatement;
        });
        $pdo->expects(self::exactly(2))->method('prepare');

        $adapter = new PDOStorageAdapter($pdo);

        $adapter->set('test', 'value');
    }

    #[Test]
    public function setIssuesUpdateWhenKeyExists(): void
    {
        $getStatement = $this->createMock(PDOStatement::class);
        $getStatement->method('fetch')->willReturn(['settings' => 'previous']);

        $setStatement = $this->createMock(PDOStatement::class);
        $setStatement->expects(self::exactly(2))->method('bindParam');
        $setStatement->expects(self::once())->method('execute');

        $captured = null;
        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturnCallback(
            static function (string $sql) use ($getStatement, $setStatement, &$captured) {
                if (str_starts_with($sql, 'SELECT')) {
                    return $getStatement;
                }
                $captured = $sql;
                return $setStatement;
            },
        );

        $adapter = new PDOStorageAdapter($pdo);
        $adapter->set('test', 'value');

        self::assertNotNull($captured);
        self::assertStringStartsWith('UPDATE', $captured);
    }

    #[Test]
    public function removeIssuesDeleteStatement(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->expects(self::once())->method('bindParam')->with(':key', 'test');
        $statement->expects(self::once())->method('execute');

        $pdo = $this->createMock(PDO::class);
        $pdo->expects(self::once())
            ->method('prepare')
            ->with($this->expectedSql('DELETE FROM rollout_feature WHERE name = :key'))
            ->willReturn($statement);

        $adapter = new PDOStorageAdapter($pdo);

        $adapter->remove('test');
    }

    private function expectedSql(string $sql): string
    {
        return $sql;
    }
}
