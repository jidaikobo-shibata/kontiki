<?php

use Phinx\Migration\AbstractMigration;

class AddDisplayUpdatedAtToPosts extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('posts');

        if ($table->hasColumn('display_updated_at')) {
            return;
        }

        $table->addColumn('display_updated_at', 'timestamp', [
            'null' => true,
            'default' => null,
            'after' => 'updated_at',
        ]);

        $table->update();
    }

    public function down(): void
    {
        $table = $this->table('posts');

        if (!$table->hasColumn('display_updated_at')) {
            return;
        }

        $table->removeColumn('display_updated_at')->update();
    }
}
