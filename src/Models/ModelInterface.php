<?php

namespace Jidaikobo\Kontiki\Models;

interface ModelInterface
{
    public function getFields(string $context = '', array $data = [], int $id = null): array;
    public function getFieldDefinitions(): array;
    public function getMetaDataFieldDefinitions(): array;
    public function getDeleteType(): string;
}
