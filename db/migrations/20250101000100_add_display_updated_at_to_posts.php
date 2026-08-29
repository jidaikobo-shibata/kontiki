<?php

use Phinx\Migration\AbstractMigration;

class AddDisplayUpdatedAtToPosts extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('posts');

        // Add display_updated_at column (nullable, default null)
        $table->addColumn('display_updated_at', 'timestamp', [
            'null' => true,
            'default' => null,
            'after' => 'updated_at', // optional: places it logically next to updated_at
        ]);

        $table->update();
    }
}
