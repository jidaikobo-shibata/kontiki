<?php

use Phinx\Migration\AbstractMigration;

final class AddDefaultUser extends AbstractMigration
{
    public function up(): void
    {
        $singleRow = [
            'username'  => 'testuser',
            'password'  => password_hash('TEST$2025#PSWD', PASSWORD_BCRYPT),
            'role'  => 'admin'
        ];

        $table = $this->table('users');
        $table->insert($singleRow);
        $table->saveData();
    }

    public function down(): void
    {
        $this->execute('DELETE FROM users WHERE id = 1');;
    }
}
