<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Tests\Services;

use Aura\Session\SessionFactory;
use Jidaikobo\Kontiki\Core\Auth;
use Jidaikobo\Kontiki\Core\Database;
use Jidaikobo\Kontiki\Models\PostModel;
use Jidaikobo\Kontiki\Models\UserModel;
use Jidaikobo\Kontiki\Services\RecordPersistenceService;
use Jidaikobo\Kontiki\Services\ValidationService;
use Jidaikobo\Kontiki\Tests\Database\DatabaseTestCase;
use RuntimeException;
use Valitron\Validator;

final class RecordPersistenceServiceTest extends DatabaseTestCase
{
    private Database $database;
    private RecordPersistenceService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->database = new Database([
            'driver' => 'sqlite',
            'database' => $this->databasePath(),
            'prefix' => '',
        ]);
        $this->service = new RecordPersistenceService($this->database);
    }

    public function testCreateSavesRecordAndLegacyMetadataTogether(): void
    {
        $model = $this->postModel();

        $id = $this->service->save(
            $model,
            'create',
            null,
            $this->postData('transaction-create', 'Excerpt')
        );

        self::assertSame('Transaction create', $model->getById($id)['title']);
        self::assertSame('Excerpt', $model->getMetaData($id, 'excerpt'));
    }

    public function testCreateRollsBackRecordWhenMetadataSaveFails(): void
    {
        $model = $this->failingPostModel('create');

        try {
            $this->service->save(
                $model,
                'create',
                null,
                $this->postData('transaction-create-failure', 'Excerpt')
            );
            self::fail('The simulated metadata failure was not thrown.');
        } catch (RuntimeException $exception) {
            self::assertSame('Simulated metadata create failure.', $exception->getMessage());
        }

        self::assertSame(
            0,
            (int) $this->pdo->query(
                "SELECT count(*) FROM posts WHERE slug = 'transaction-create-failure'"
            )->fetchColumn()
        );
        self::assertSame(
            0,
            (int) $this->pdo->query('SELECT count(*) FROM meta_data')->fetchColumn()
        );
    }

    public function testUpdateRollsBackRecordWhenMetadataSaveFails(): void
    {
        $model = $this->postModel();
        $id = $this->service->save(
            $model,
            'create',
            null,
            $this->postData('transaction-update-failure', 'Original excerpt')
        );
        $failingModel = $this->failingPostModel('create');

        try {
            $this->service->save(
                $failingModel,
                'edit',
                $id,
                [
                    'title' => 'Changed title',
                    'excerpt' => 'Changed excerpt',
                ]
            );
            self::fail('The simulated metadata failure was not thrown.');
        } catch (RuntimeException $exception) {
            self::assertSame('Simulated metadata create failure.', $exception->getMessage());
        }

        self::assertSame('Transaction update failure', $model->getById($id)['title']);
        self::assertSame('Original excerpt', $model->getMetaData($id, 'excerpt'));
    }

    public function testEmptyMetadataValueDeletesExistingValue(): void
    {
        $model = $this->postModel();
        $id = $this->service->save(
            $model,
            'create',
            null,
            $this->postData('transaction-delete-metadata', 'Excerpt')
        );

        $this->service->save($model, 'edit', $id, ['excerpt' => '']);

        self::assertNull($model->getMetaData($id, 'excerpt'));
    }

    /** @return array<string, mixed> */
    private function postData(string $slug, string $excerpt): array
    {
        return [
            'title' => ucfirst(str_replace('-', ' ', $slug)),
            'content' => 'Body',
            'slug' => $slug,
            'parent_id' => '',
            'status' => 'published',
            'creator_id' => 1,
            'published_at' => null,
            'expired_at' => null,
            'excerpt' => $excerpt,
        ];
    }

    private function postModel(): PostModel
    {
        $userModel = $this->userModel();

        return new PostModel(
            $this->database,
            $this->validationService(),
            new Auth((new SessionFactory())->newInstance([]), $userModel),
            $userModel
        );
    }

    private function failingPostModel(string $operation): PostModel
    {
        $userModel = $this->userModel();

        return new class (
            $this->database,
            $this->validationService(),
            new Auth((new SessionFactory())->newInstance([]), $userModel),
            $userModel,
            $operation
        ) extends PostModel {
            public function __construct(
                Database $database,
                ValidationService $validator,
                Auth $auth,
                UserModel $userModel,
                private string $failingOperation
            ) {
                parent::__construct($database, $validator, $auth, $userModel);
            }

            public function createMetaData(
                int $id,
                string $key,
                mixed $value
            ): void {
                if ($this->failingOperation === 'create') {
                    throw new RuntimeException('Simulated metadata create failure.');
                }

                parent::createMetaData($id, $key, $value);
            }

            public function updateMetaData(
                int $id,
                string $key,
                mixed $value
            ): void {
                if ($this->failingOperation === 'update') {
                    throw new RuntimeException('Simulated metadata update failure.');
                }

                parent::updateMetaData($id, $key, $value);
            }
        };
    }

    private function userModel(): UserModel
    {
        return new UserModel($this->database, $this->validationService());
    }

    private function validationService(): ValidationService
    {
        return new ValidationService(
            $this->database,
            new Validator([], [], 'en')
        );
    }
}
