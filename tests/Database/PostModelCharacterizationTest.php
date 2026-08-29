<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Tests\Database;

use Aura\Session\SessionFactory;
use Carbon\Carbon;
use Jidaikobo\Kontiki\Core\Auth;
use Jidaikobo\Kontiki\Core\Database;
use Jidaikobo\Kontiki\Models\PostModel;
use Jidaikobo\Kontiki\Models\UserModel;
use Jidaikobo\Kontiki\Services\ValidationService;
use PDO;
use ReflectionMethod;
use Valitron\Validator;

final class PostModelCharacterizationTest extends DatabaseTestCase
{
    private PostModel $model;

    protected function setUp(): void
    {
        parent::setUp();

        $database = new Database([
            'driver' => 'sqlite',
            'database' => $this->databasePath(),
            'prefix' => '',
        ]);
        $userModel = new UserModel(
            $database,
            $this->validationService($database)
        );
        $auth = new Auth(
            (new SessionFactory())->newInstance([]),
            $userModel
        );
        $this->model = new PostModel(
            $database,
            $this->validationService($database),
            $auth,
            $userModel
        );
    }

    public function testCreateReadUpdateAndDeleteMetadata(): void
    {
        $id = $this->createPost('metadata');

        $this->model->createMetaData($id, 'excerpt', 'First');
        self::assertSame('First', $this->model->getMetaData($id, 'excerpt'));
        self::assertSame('First', $this->model->getById($id)['excerpt']);

        $this->model->updateMetaData($id, 'excerpt', 'Changed');
        self::assertSame('Changed', $this->model->getMetaData($id, 'excerpt'));

        $this->model->deleteMetaData($id, 'excerpt');
        self::assertNull($this->model->getMetaData($id, 'excerpt'));
    }

    public function testHardDeleteCurrentlyLeavesMetadataOrphaned(): void
    {
        $id = $this->createPost('orphan');
        $this->model->createMetaData($id, 'excerpt', 'Orphaned');

        self::assertTrue($this->model->delete($id));
        self::assertNull($this->model->getById($id));
        self::assertSame('Orphaned', $this->model->getMetaData($id, 'excerpt'));
    }

    public function testMetadataTargetCurrentlyUsesThePhpClassName(): void
    {
        $id = $this->createPost('target');
        $this->model->createMetaData($id, 'excerpt', 'Target');

        $statement = $this->pdo->prepare(
            'SELECT target FROM meta_data WHERE target_id = :target_id'
        );
        $statement->execute(['target_id' => $id]);

        self::assertSame(PostModel::class, $statement->fetchColumn());
    }

    public function testSortingByMetadataIsCurrentlySilentlyIgnored(): void
    {
        $firstId = $this->createPost('metadata-sort-first');
        $secondId = $this->createPost('metadata-sort-second');
        $this->model->createMetaData($firstId, 'excerpt', 'Zulu');
        $this->model->createMetaData($secondId, 'excerpt', 'Alpha');

        $method = new ReflectionMethod($this->model, 'applyFiltersToQuery');
        $query = $method->invoke($this->model, 'all', [
            'orderby' => 'excerpt',
            'order' => 'ASC',
        ]);
        $sql = strtolower($query->toSql());

        self::assertStringContainsString('order by "excerpt" asc', $sql);
        self::assertStringNotContainsString('meta_data', $sql);

        $rows = $this->model->getIndexData('all', [
            'orderby' => 'excerpt',
            'order' => 'ASC',
        ]);

        self::assertCount(2, $rows);
        self::assertNotContains('excerpt', array_keys($rows[0]));
    }

    public function testIndexContextsSelectTheCurrentPostStates(): void
    {
        $now = Carbon::now('UTC');
        $past = $now->copy()->subHour()->format('Y-m-d H:i:s');
        $future = $now->copy()->addHour()->format('Y-m-d H:i:s');

        $this->createPost('published-null');
        $this->createPost('published-past', 'published', $past);
        $this->createPost('reserved', 'published', $future);
        $this->createPost('expired', 'published', $past, $past);
        $this->createPost('draft', 'draft');
        $this->createPost('pending', 'pending');
        $this->createPost('trash', 'published', $past, null, $past);
        $this->createPost('other-type', 'published', $past, null, null, 'sample');

        self::assertSame([
            'draft',
            'expired',
            'pending',
            'published-null',
            'published-past',
            'reserved',
        ], $this->slugsFor('all'));
        self::assertSame([
            'published-null',
            'published-past',
        ], $this->slugsFor('published'));
        self::assertSame(['reserved'], $this->slugsFor('reserved'));
        self::assertSame(['expired'], $this->slugsFor('expired'));
        self::assertSame(['pending'], $this->slugsFor('pending'));
        self::assertSame(['draft'], $this->slugsFor('draft'));
        self::assertSame(['trash'], $this->slugsFor('trash'));
    }

    private function validationService(Database $database): ValidationService
    {
        return new ValidationService(
            $database,
            new Validator([], [], 'en')
        );
    }

    private function createPost(
        string $slug,
        string $status = 'published',
        ?string $publishedAt = null,
        ?string $expiredAt = null,
        ?string $deletedAt = null,
        string $postType = 'post'
    ): int {
        $statement = $this->pdo->prepare(
            'INSERT INTO posts '
            . '(post_type, title, slug, parent_id, status, sort_order, '
            . 'creator_id, published_at, expired_at, deleted_at) '
            . 'VALUES (:post_type, :title, :slug, :parent_id, :status, '
            . ':sort_order, :creator_id, :published_at, :expired_at, :deleted_at)'
        );
        $statement->execute([
            'post_type' => $postType,
            'title' => ucfirst($slug),
            'slug' => $slug,
            'parent_id' => '',
            'status' => $status,
            'sort_order' => 1,
            'creator_id' => 1,
            'published_at' => $publishedAt,
            'expired_at' => $expiredAt,
            'deleted_at' => $deletedAt,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /** @return list<string> */
    private function slugsFor(string $context): array
    {
        $rows = $this->model->getIndexData($context, ['perPage' => 100]);
        $slugs = array_column($rows, 'slug');
        sort($slugs);

        return $slugs;
    }
}
