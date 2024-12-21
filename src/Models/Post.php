<?php

namespace jidaikobo\kontiki\Models;

use jidaikobo\kontiki\Utils\Lang;

class Post extends BaseModel
{
    protected string $table = 'posts';

    public function getDisplayFields(): array
    {
        return ['id', 'title', 'slug', 'created_at'];
    }

    public function getFieldDefinitions(): array
    {
        $userModel = new User($this->db, $this->validationService);

        return [
            'id' => [
                'label' => 'ID',
            ],
            'title' => [
                'label' => Lang::get('title', 'Title'),
                'type' => 'text',
                'attributes' => ['class' => 'form-control'],
                'label_attributes' => ['class' => 'form-label'],
                'default' => '',
                'searchable' => TRUE,
                'rules' => ['required'],
                'filter' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
                'template' => 'default',
                'group' => 'main',
                'fieldset_template' => 'forms/fieldset/flat.php',
            ],
            'content' => [
                'label' => Lang::get('content', 'Content'),
                'description' => '',
                'type' => 'textarea',
                'attributes' => [
                  'class' => 'form-control',
                  'rows' => '10'
                ],
                'label_attributes' => ['class' => 'form-label'],
                'default' => '',
                'searchable' => true,
                'rules' => [],
                'filter' => FILTER_UNSAFE_RAW,
                'template' => 'default',
                'group' => 'main',
                'fieldset_template' => 'forms/fieldset/flat.php',
            ],
            'slug' => [
                'label' => Lang::get('slug', 'Slug'),
                'type' => 'text',
                'attributes' => ['class' => 'form-control'],
                'label_attributes' => ['class' => 'form-label'],
                'default' => '',
                'searchable' => TRUE,
                'rules' => ['required'],
                'filter' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
                'template' => 'default',
                'group' => 'main',
                'fieldset_template' => 'forms/fieldset/flat.php',
            ],
            'published_at' => [
                'label' => Lang::get('published', 'published'),
                'type' => 'datetime-local',
                'attributes' => ['class' => 'form-control'],
                'label_attributes' => ['class' => 'form-label'],
                'default' => date('Y-m-dTH:i', time()),
                'searchable' => TRUE,
                'rules' => ['required'],
                'filter' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
                'template' => 'default',
                'group' => 'main',
                'fieldset_template' => 'forms/fieldset/flat.php',
            ],
            'is_draft' => [
                'label' => Lang::get('draft', 'Draft'),
                'type' => 'select',
                'options' => [0 => 'published', 1 => 'draft'],
                'attributes' => ['class' => 'form-control form-select'],
                'label_attributes' => ['class' => 'form-label'],
                'default' => '',
                'searchable' => TRUE,
                'rules' => ['required'],
                'filter' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
                'template' => 'default',
                'group' => 'main',
                'fieldset_template' => 'forms/fieldset/flat.php',
            ],
            'creator_id' => [
                'label' => Lang::get('creator', 'Creator'),
                'type' => 'select',
                'options' => $userModel->getOptions('username'),
                'attributes' => ['class' => 'form-control form-select'],
                'label_attributes' => ['class' => 'form-label'],
                'default' => '',
                'searchable' => TRUE,
                'rules' => ['required'],
                'filter' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
                'template' => 'default',
                'group' => 'main',
                'fieldset_template' => 'forms/fieldset/flat.php',
            ],
            'created_at' => [
                'label' => Lang::get('created_at', 'Created'),
            ],
        ];
    }
}
