<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Tests\Database;

use PDO;
use RuntimeException;

final class SchemaCharacterizationTest extends DatabaseTestCase
{
    public function testCleanInstallAppliesTheNinePublishedMigrations(): void
    {
        $versions = $this->pdo
            ->query('SELECT version FROM phinxlog ORDER BY version')
            ->fetchAll(PDO::FETCH_COLUMN);

        self::assertSame([
            20240101000100,
            20240101000200,
            20240101000300,
            20240101000400,
            20240101000500,
            20240101000600,
            20240101000700,
            20240101000800,
            20250101000100,
        ], array_map('intval', $versions));
    }

    public function testCleanInstallContainsTheExpectedTablesAndLateColumns(): void
    {
        self::assertSame([
            'files',
            'meta_data',
            'phinxlog',
            'posts',
            'rate_limit',
            'term_relationships',
            'terms',
            'users',
        ], $this->tableNames());

        self::assertContains('role', $this->columnNames('users'));
        self::assertContains('display_updated_at', $this->columnNames('posts'));
    }

    public function testCurrentSchemaDoesNotDefineForeignKeys(): void
    {
        foreach (
            [
                'posts',
                'terms',
                'term_relationships',
                'meta_data',
            ] as $table
        ) {
            $foreignKeys = $this->pdo
                ->query("PRAGMA foreign_key_list({$table})")
                ->fetchAll(PDO::FETCH_ASSOC);

            self::assertSame([], $foreignKeys, $table);
        }
    }

    public function testUpdatedAtCurrentlyDoesNotChangeOnUpdate(): void
    {
        $id = $this->insertPost();
        $before = $this->postValue($id, 'updated_at');

        $statement = $this->pdo->prepare(
            'UPDATE posts SET title = :title WHERE id = :id'
        );
        $statement->execute(['title' => 'Changed', 'id' => $id]);

        self::assertSame($before, $this->postValue($id, 'updated_at'));
    }

    public function testEmptyParentIdIsCurrentlyStoredAsText(): void
    {
        $id = $this->insertPost('');
        $statement = $this->pdo->prepare(
            'SELECT typeof(parent_id) FROM posts WHERE id = :id'
        );
        $statement->execute(['id' => $id]);

        self::assertSame('text', $statement->fetchColumn());
    }

    public function testSlugIsUniqueWithinEachPostType(): void
    {
        $this->insertPostWithTypeAndSlug('post', 'shared-slug');
        $this->insertPostWithTypeAndSlug('sample', 'shared-slug');

        $this->expectException(\PDOException::class);
        $this->insertPostWithTypeAndSlug('post', 'shared-slug');
    }

    /** @return list<string> */
    private function tableNames(): array
    {
        $statement = $this->pdo->query(
            "SELECT name FROM sqlite_master "
            . "WHERE type = 'table' AND name NOT LIKE 'sqlite_%' "
            . 'ORDER BY name'
        );

        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }

    /** @return list<string> */
    private function columnNames(string $table): array
    {
        if (!in_array($table, ['users', 'posts'], true)) {
            throw new RuntimeException('Unexpected table name.');
        }

        $columns = $this->pdo
            ->query("PRAGMA table_info({$table})")
            ->fetchAll(PDO::FETCH_ASSOC);

        return array_column($columns, 'name');
    }

    private function insertPost(mixed $parentId = null): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO posts '
            . '(post_type, title, slug, parent_id, status, creator_id) '
            . 'VALUES (:post_type, :title, :slug, :parent_id, :status, :creator_id)'
        );
        $statement->execute([
            'post_type' => 'post',
            'title' => 'Characterization',
            'slug' => 'characterization-' . bin2hex(random_bytes(4)),
            'parent_id' => $parentId,
            'status' => 'published',
            'creator_id' => 1,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    private function insertPostWithTypeAndSlug(
        string $postType,
        string $slug
    ): void {
        $statement = $this->pdo->prepare(
            'INSERT INTO posts '
            . '(post_type, title, slug, status, creator_id) '
            . 'VALUES (:post_type, :title, :slug, :status, :creator_id)'
        );
        $statement->execute([
            'post_type' => $postType,
            'title' => 'Unique slug characterization',
            'slug' => $slug,
            'status' => 'published',
            'creator_id' => 1,
        ]);
    }

    private function postValue(int $id, string $column): mixed
    {
        if (!in_array($column, ['updated_at'], true)) {
            throw new RuntimeException('Unexpected column name.');
        }

        $statement = $this->pdo->prepare(
            "SELECT {$column} FROM posts WHERE id = :id"
        );
        $statement->execute(['id' => $id]);

        return $statement->fetchColumn();
    }
}
