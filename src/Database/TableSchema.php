<?php

namespace Jidaikobo\Kontiki\Database;

use Phinx\Db\Table;

class TableSchema
{
    public static function applyPostSchema(Table $table): void
    {
        $table->addColumn('title', 'string', ['limit' => 255])
              ->addColumn('content', 'text', ['null' => true])
              ->addColumn('slug', 'string', ['limit' => 255])
              ->addColumn('is_draft', 'boolean', ['default' => true])
              ->addColumn('creator_id', 'integer', ['default' => 1])
              ->addColumn('published_at', 'timestamp', ['null' => true, 'default' => null])
              ->addColumn('expired_at', 'timestamp', ['null' => true, 'default' => null])
              ->addColumn('deleted_at', 'timestamp', ['null' => true, 'default' => null])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
              ->addIndex(['slug'], ['unique' => true]);
    }
}
