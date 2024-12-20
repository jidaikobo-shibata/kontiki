<?php

namespace jidaikobo\kontiki\Models;

interface ModelInterface
{
    public function getTableName(): string;
    public function getDisplayFields(): array;
    public function getFieldDefinitions(): array;
    public function countByKeyword(string $keyword): int;
}
