<?php

namespace Jidaikobo\Kontiki\Models;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Jidaikobo\Kontiki\Core\Auth;
use Jidaikobo\Kontiki\Core\Database;
use Jidaikobo\Kontiki\Services\ValidationService;

class PostModel extends BaseModel
{
    use Traits\CRUDTrait;
    use Traits\MetaDataTrait;
    use Traits\IndexTrait;
    use Traits\SoftDeleteTrait;
    use Traits\PublishedTrait;
    use Traits\DraftTrait;
    use Traits\PendingTrait;
    use Traits\ExpiredTrait;
    use Traits\TaxonomyTrait;

    protected string $table = 'posts';
    protected string $postType = 'post';
    protected string $deleteType = 'softDelete';

    private Auth $auth;
    private UserModel $userModel;

    public function __construct(
        Database $db,
        ValidationService $validator,
        Auth $auth,
        UserModel $userModel
    ) {
        parent::__construct($db, $validator);
        $this->auth = $auth;
        $this->userModel = $userModel;
    }

    protected function defineFieldDefinitions(): void
    {
        $this->fieldDefinitions = [
            'id' => $this->getIdField(),
            'title' => $this->getTitleField(),
            'content' => $this->getContentField(__('content')),
            'slug' => $this->getSlugField(),
            'parent_id' => $this->getParentIdField(),
            'status' => $this->getStatusField(),
            'expired_at' => $this->getExpiredAtField(),
            'published_at' => $this->getPublishedAtField(),
            'creator_id' => $this->getCreatorIdField(),
            'updated_at' => $this->getUpdatedAtField(),
            'deleted_at' => $this->getDeletedAtField(),
            'created_at' => $this->getCreatedAtField(),
            'display_updated_at' => $this->getDisplayUpdatedAtField(),
        ];
    }

    protected function defineMetaDataFieldDefinitions(): void
    {
        $hide_excerpt = env('POST_HIDE_METADATA_EXCERPT', false);
        $hide_eyecatch = env('POST_HIDE_METADATA_EYECATCH', false);
        $this->metaDataFieldDefinitions = [];

        if (!$hide_excerpt) {
            $this->metaDataFieldDefinitions['excerpt'] = $this->getField(
                __('excerpt'),
                [
                    'type' => 'textarea',
                    'attributes' => [
                        'class' => 'form-control',
                        'row' => 3,
                    ]
                ]
            );
        }

        if (!$hide_eyecatch) {
            $this->metaDataFieldDefinitions['eyecatch'] = $this->getField(
                __('eyecatch'),
                [
                    'attributes' => [
                        'class' => 'form-control font-monospace kontiki-file-upload',
                    ],
                    'fieldset_template' => 'forms/fieldset/input-group.php',
                ]
            );
        }
    }

    protected function getTaxonomyDefinitions(array $params = []): array
    {
        $taxonomies = [
            'category' => [
                'label' => __('category'),
                'Model' => 'CategoryModel',
            ],
        ];
        return $taxonomies;
    }

    protected function getAdditionalConditions(Builder $query, string $context = 'all'): Builder
    {
        $contextConditions = [
            'all'       => ['applyNotSoftDeletedConditions'],
            'published' => [
                'applyNotSoftDeletedConditions',
                'applyNotExpiredConditions',
                'applyPublisedConditions',
                'applyNotDraftConditions'
            ],
            'trash'     => ['applySoftDeletedConditions'],
            'reserved'  => [
                'applyNotPublisedConditions',
                'applyNotSoftDeletedConditions'
            ],
            'expired'   => [
                'applyExpiredConditions',
                'applyNotSoftDeletedConditions'
            ],
            'pending'     => [
                'applyPendingConditions',
                'applyNotSoftDeletedConditions'
            ],
            'draft'     => [
                'applyDraftConditions',
                'applyNotSoftDeletedConditions'
            ],
        ];

        if (isset($contextConditions[$context])) {
            foreach ($contextConditions[$context] as $method) {
                $query = $this->$method($query);
            }
        }

        // jlog($context);
        // jlog($query->toSql());
        // jlog($query->getBindings());

        return $query;
    }

    protected function processFieldDefinitions(
        string $context = '',
        array $data = [],
        int $id = null
    ): void {
        // add rule
        $this->fieldDefinitions['slug']['rules'][] = [
            'unique',
            $this->table,
            'slug',
            $id
        ];

        // add options
        $parents = $this->getOptions('title', true, '', $id);
        $this->fieldDefinitions['parent_id']['options'] = $parents;

        // add options and default value
        $userOptions = $this->userModel->getOptions('username');
        $user = $this->auth->getCurrentUser();
        $this->fieldDefinitions['creator_id']['options'] = $userOptions;
        $this->fieldDefinitions['creator_id']['default'] = $user['id'] ?? 0; // no logged in user: 0

        // add default value
        if (in_array($context, ['create'])) {
            $this->addDefaultSlug($data);
        }

        // disable form elements
        if (in_array($context, ['trash', 'restore', 'delete'])) {
            $this->disableFormFieldsForContext();
        }
    }

    private function addDefaultSlug(array $data): void
    {
        // Give priority to POST values
        if (!empty($data['slug'])) {
            $this->fieldDefinitions['slug']['default'] = $data['slug'];
            return;
        }

        // recommend non exists slug
        $now = Carbon::now(env('TIMEZONE', 'UTC'))->format('Ymd');
        $slug = $this->postType . '-' . $now;
        $n = 1;
        while ($this->getByField('slug', $slug)) {
            $n++;
            $slug = $slug . '-' . $n;
        }
        $this->fieldDefinitions['slug']['default'] = $slug;
    }

    private function getTitleField(): array
    {
        return $this->getField(
            __('title'),
            [
                'rules' => ['required'],
                'display_in_list' => true
            ]
        );
    }

    private function getContentField(string $label): array
    {
        return $this->getField(
            $label,
            [
                'type' => 'textarea',
                'description' => __('content_exp'),
                'attributes' => [
                    'class' => 'form-control font-monospace kontiki-file-upload',
                    'data-button-class' => 'mt-2',
                    'rows' => '10'
                ]
            ]
        );
    }

    private function getSlugField(): array
    {
        // add dynamic rules at $this->processFieldDefinitions()
        return $this->getField(
            __('slug'),
            [
                'description' => __('slug_exp'),
                'rules' => [
                    'required',
                    'slug',
                    ['lengthMin', 3],
                ],
            ]
        );
    }

    private function getParentIdField(): array
    {
        // add options at $this->processFieldDefinitions()
        return $this->getField(
            'parent',
            [
                'type' => env('POST_HIDE_PARENT', false) ? 'hidden' : 'select',
                'attributes' => [
                    'class' => 'form-control form-select'
                ],
                'group' => 'meta',
            ]
        );
    }

    private function getPublishedAtField(): array
    {
        $now = Carbon::now(env('TIMEZONE', 'UTC'))->format('Y-m-d H:i');
        return $this->getField(
            'reserved_at',
            [
                'type' => 'datetime-local',
                'description' => __('published_at_exp'),
                'default' => $now,
                'group' => 'meta',
                'save_as_utc' => true,
                'fieldset_template' => 'forms/fieldset/details.php',
                'display_in_list' => 'reserved'
            ]
        );
    }

    private function getExpiredAtField(): array
    {
        return $this->getField(
            'expired_at',
            [
                'type' => 'datetime-local',
                'description' => __('expired_at_exp'),
                'group' => 'meta',
                'save_as_utc' => true,
                'fieldset_template' => 'forms/fieldset/details.php',
                'display_in_list' => 'expired'
            ]
        );
    }

    private function getStatusField(): array
    {
        return $this->getField(
            'status',
            [
                'type' => 'select',
                'options' => [
                    'draft' => __('draft'),
                    'published' => __('publishing'),
                    'pending' => __('pending'),
                ],
                'default' => 'published',
                'attributes' => [
                    'class' => 'form-control form-select'
                ],
                'display_in_list' => true,
                'group' => 'meta',
            ]
        );
    }

    private function getCreatorIdField(): array
    {
        // add options and default at $this->processFieldDefinitions()
        return $this->getField(
            'creator',
            [
                    'type' => env('POST_HIDE_AUTHOR', false) ? 'hidden' : 'select',
                    'attributes' => [
                        'class' => 'form-control form-select'
                    ],
                    'group' => 'meta',
                ]
        );
    }

    private function getCreatedAtField(): array
    {
        return $this->getReadOnlyField(
            __('created_at', 'Created'),
            [
                'save_as_utc' => true,
                'display_in_list' => true
            ]
        );
    }

    private function getUpdatedAtField(): array
    {
        return $this->getReadOnlyField(
            __('updated_at', 'Updated'),
            [
                'save_as_utc' => true
            ]
        );
    }

    private function getDeletedAtField(): array
    {
        return $this->getReadOnlyField(
            __('deleted_at', 'deleted'),
            [
                'save_as_utc' => true,
                'display_in_list' => 'trash'
            ]
        );
    }

    private function getDisplayUpdatedAtField(): array
    {
        return $this->getField(
            __('display_updated_at', 'Display Updated'),
            [
                'type' => 'datetime-local',
                'description' => __('display_updated_at_exp'),
                'group' => 'meta',
                'save_as_utc' => true,
                'fieldset_template' => 'forms/fieldset/details.php',
            ]
        );
    }
}
