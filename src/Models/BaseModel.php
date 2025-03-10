<?php

namespace Jidaikobo\Kontiki\Models;

use Illuminate\Database\Connection;
use Jidaikobo\Kontiki\Core\Database;
use Jidaikobo\Kontiki\Models\BaseModelTraits;

/**
 * BaseModel provides common CRUD operations for database interactions.
 * Extend this class to create specific models for different database tables.
 */
abstract class BaseModel implements ModelInterface
{
    use BaseModelTraits\FieldDefinitionTrait;
    use BaseModelTraits\SearchTrait;
    use BaseModelTraits\UtilsTrait;
    use BaseModelTraits\ValidateTrait;

    protected string $table;
    protected string $postType = '';
    protected string $deleteType = 'hardDelete';
    protected Connection $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
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
}
