<?php

use Phinx\Migration\AbstractMigration;

class CreateUsersTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('users');
        $table->addColumn('username', 'string', ['limit' => 255])
              ->addColumn('password', 'string', ['limit' => 255])
              ->addColumn(
                  'role',
                  'string',
                  ['limit' => 50, 'default' => 'editor', 'null' => false]
              )
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn(
                  'updated_at',
                  'timestamp',
                  ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP']
              )
              ->addIndex('username', ['unique' => true])
              ->create();
    }
}
