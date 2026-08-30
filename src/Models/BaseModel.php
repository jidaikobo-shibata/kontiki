<?php

namespace Jidaikobo\Kontiki\Models;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Jidaikobo\Kontiki\Core\Database;
use Jidaikobo\Kontiki\Models\BaseModelTraits;
use Jidaikobo\Kontiki\Services\ValidationService;
use Jidaikobo\Kontiki\Services\ApplicationClock;

/**
 * BaseModel provides common CRUD operations for database interactions.
 * Extend this class to create specific models for different database tables.
 */
abstract class BaseModel implements ModelInterface
{
    use BaseModelTraits\FieldDefinitionTrait;
    use BaseModelTraits\SearchTrait;
    use BaseModelTraits\UtilsTrait;

    protected string $table;
    protected string $postType = '';
    protected string $deleteType = 'hardDelete';
    protected Connection $db;
    public ValidationService $validator;
    protected ApplicationClock $applicationClock;

    public function __construct(
        Database $db,
        ValidationService $validator,
        ?ApplicationClock $applicationClock = null,
    ) {
        $this->db = $db->getConnection();
        $this->validator = $validator;
        $this->applicationClock = $applicationClock
            ?? new ApplicationClock(env('TIMEZONE', 'UTC'));
        $this->validator->setModel($this);
        $this->initializeFields();
        $this->initializeMetaDataFields();
    }

    public function getQuery(): Builder
    {
        return $this->db->table($this->table);
    }

    public function getDeleteType(): string
    {
        return $this->deleteType;
    }

    public function getTableName(): string
    {
        return $this->table;
    }

    public function getPostType(): string
    {
        return $this->postType;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function validate(
        array $data,
        array $context
    ): array {
        return $this->validator->validate($data, $context);
    }
}
