<?php

namespace Jidaikobo\Kontiki\Models;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;

class CategoryModel extends BaseModel
{
    use Traits\CRUDTrait;
    use Traits\MetaDataTrait;
    use Traits\IndexTrait;

    protected string $table = 'terms';

    public function getFieldDefinitions(array $params = []): array
    {
        $id = 1;
        $fields = [
            'id' => $this->getIdField(),
            'name' => $this->getNameField(),
            'slug' => $this->getSlugField($id),
            'parent_id' => $this->getParentIdField(),
            'term_order' => $this->getTermOrederField(),
        ];

        return array_merge($fields, $this->getMetaDataFieldDefinitions($params));
    }

    private function getNameField(): array
    {
        return $this->getField(
            __('name'),
            [
                'rules' => ['required'],
                'display_in_list' => true
            ]
        );
    }

    private function getSlugField(?int $id): array
    {
        $slug_exp = __('slug_exp', 'The "slug" is used as the URL. It can contain alphanumeric characters and hyphens.');

        return $this->getField(
            __('slug'),
            [
                'description' => $slug_exp,
                'rules' => [
                    'required',
                    'slug',
                    ['lengthMin', 3],
                    ['unique', $this->table, 'slug', $id]
                ],
            ]
        );
    }

    private function getParentIdField(): array
    {
        $type = false ? 'hidden' : 'select';
        return $this->getField(
            __('parent'),
            [
                'type' => $type,
                'options' => [],
                'default' => '',
                'rules' => [
                    'required',
                    'slug',
                    ['lengthMin', 3],
                    ['unique', $this->table, 'slug', $id]
                ],
            ]
        );
    }

    private function getTermOrederField(): array
    {
        return $this->getField(
            __('order'),
            [
                'display_in_list' => true
            ]
        );
    }
}
