<?php

namespace Jidaikobo\Kontiki\Models;

class UserModel extends BaseModel
{
    use Traits\CRUDTrait;
    use Traits\MetaDataTrait;
    use Traits\IndexTrait;

    protected string $table = 'users';

    protected function defineFieldDefinitions(): void
    {
        // add dynamic rules at $this->processFieldDefinitions()
        $this->fieldDefinitions = [
            'id' => $this->getIdField(),

            'username' => $this->getField(
                __('username'),
                [
                    'rules' => [
                        'required',
                        ['lengthMin', 3]
                    ],
                    'display_in_list' => true
                ]
            ),

            'password' => $this->getField(
                __('password'),
                [
                    'type' => 'password',
                    'rules' => [
                        'required',
                        ['lengthMin', 8]
                    ],
                    'filter' => FILTER_UNSAFE_RAW,
                ]
            ),

            'role' => $this->getField(
                __('role'),
                [
                    'type' => 'select',
                    'options' => [
                        'editor' => __('editor'),
                        'admin' => __('admin'),
                    ],
                    'rules' => [
                        'required',
                    ],
                    'attributes' => [
                        'class' => 'form-control form-select'
                    ],
                    'display_in_list' => true
                ]
            ),

            'created_at' => $this->getReadOnlyField(
                __('created_at', 'Created'),
                [
                    'display_in_list' => true
                ]
            ),
        ];
    }

    protected function processFieldDefinitions(
        string $context = '',
        array $data = [],
        int $id = null
    ): void {
        // add rule
        $this->fieldDefinitions['username']['rules'][] = [
            'unique',
            $this->table,
            'username',
            $id
        ];

        if ($context == 'create') {
            return;
        }

        // Exclude `required` from password's rules
        // No password specified means no change
        $this->fieldDefinitions['password']['rules'] = array_filter(
            $this->fieldDefinitions['password']['rules'],
            fn($rule) => $rule !== 'required'
        );

        $this->fieldDefinitions['password']['description'] = __('users_edit_message');

        // disable form elements
        if (in_array($context, ['trash', 'restore', 'delete'])) {
            $this->disableFormFieldsForContext();
        }
    }

    public function validate(array $data, array $context): array
    {
        $result = parent::validate($data, $context);
        $adminCheck = $this->atLeastOneAdmin($data, $context);
        $deleteCheck = $this->cannotDeleteAdmin($context);

        return [
            'valid' => $result['valid'] && $adminCheck['valid'] && $deleteCheck['valid'],
            'errors' => array_merge_recursive(
                $result['errors'],
                $adminCheck['errors'],
                $deleteCheck['errors']
            )
        ];
    }

    private function atLeastOneAdmin(array $data, array $context): array
    {
        $result = [
            'valid' => true,
            'errors' => []
        ];

        $id = $context['id'] ?? 0;
        if (!$id || !isset($data['role'])) {
            return $result;
        }

        $targetUser = $this->getById($id);

        if ($this->isDemotingLastAdmin($targetUser, $data)) {
            $result['valid'] = false;
            $result['errors']['role']['messages'] = [__('at_least_one_admin')];
        }

        return $result;
    }

    /**
     * Check if the given user is the last admin and being demoted.
     */
    private function isDemotingLastAdmin(?array $user, array $newData): bool
    {
        if (!$user || $user['role'] !== 'admin' || $newData['role'] === 'admin') {
            return false;
        }

        $otherAdmins = $this->db->table($this->table)
            ->where('role', 'admin')
            ->where('id', '!=', $user['id'])
            ->count();

        return $otherAdmins === 0;
    }

    private function cannotDeleteAdmin(array $context): array
    {
        $result = [
            'valid' => true,
            'errors' => []
        ];

        if (($context['context'] ?? '') !== 'delete') {
            return $result;
        }

        $id = $context['id'] ?? 0;
        $targetUser = $this->getById($id);

        if ($targetUser['role'] !== 'admin') {
            return $result;
        }
        $result['valid'] = false;
        $result['errors']['role']['messages'] = [__('cannot_delete_admin')];

        return $result;
    }

    private function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    protected function processDataForForm(string $actionType, array $data): array
    {
        if ($actionType == 'edit') {
            $data['password'] = '';
        }
        return $data;
    }

    protected function afterProcessDataBeforeSave(string $context, array $data): array
    {
        if ($context == 'create') {
            $data['password'] = $this->hashPassword($data['password']);
        }

        if ($context == 'update') {
            // Branching password processing
            if (isset($data['password'])) {
                if (trim($data['password']) === '') {
                    unset($data['password']);
                } else {
                    $data['password'] = $this->hashPassword($data['password']);
                }
            }
        }
        return $data;
    }
}
