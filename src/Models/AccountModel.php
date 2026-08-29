<?php

namespace Jidaikobo\Kontiki\Models;

class AccountModel extends UserModel
{
    protected function processFieldDefinitions(
        string $context = '',
        array $data = [],
        int $id = null
    ): void {
        parent::processFieldDefinitions($context, $data, $id);
        unset($this->fieldDefinitions['role']);
    }
}
