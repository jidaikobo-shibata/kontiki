<?php

namespace Jidaikobo\Kontiki\Services;

use Illuminate\Database\Connection;
use Valitron\Validator;
use Jidaikobo\Kontiki\Utils\Env;

class ValidationService
{
    protected Connection $db;

    /**
     * ValidationService constructor.
     *
     * @param Connection $db Instance of Illuminate\Database\Connection
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;

        Validator::addRule(
            'unique',
            function ($field, $value, array $params, array $fields) {
                return $this->isUnique($params[0], $params[1], $value, $params[2] ?? null);
            },
            __('is_already_exists', 'is already exists')
        );
    }

    /**
     * Validate the given data based on field definitions.
     *
     * @param array $data The input data to validate.
     * @param array $fieldDefinitions The validation rules and configurations for each field.
     * @return array Validation result with 'valid' (bool) and 'errors' (array).
     */
    public function validate(array $data, array $fieldDefinitions): array
    {
        Validator::lang(Env::get('LANG'));
        $validator = new Validator($data);
        $validator->setPrependLabels(false);

        foreach ($fieldDefinitions as $field => $definition) {
            if (isset($definition['rules'])) {
                foreach ($definition['rules'] as $rule) {
                    if (is_array($rule)) {
                        $validator->rule($rule[0], $field, ...array_slice($rule, 1));
                    } else {
                        $validator->rule($rule, $field);
                    }
                }
            }
        }

        $isValid = $validator->validate();

        $errors = [];

        if (!$isValid) {
            foreach ($validator->errors() as $field => $messages) {
                $errors[$field] = [
                    'messages' => $messages,
                    'htmlName' => $field,
                ];
            }
        }

        return [
            'valid' => $isValid,
            'errors' => $errors,
        ];
    }

    /**
     * Check if a value is unique in the database for a specific field, optionally excluding a specific record by ID.
     *
     * @param string $table The table name to check in.
     * @param string $column The column name to check.
     * @param mixed $value The value to check for uniqueness.
     * @param int|null $excludeId The ID of the record to exclude from the check (used for updates).
     *
     * @return bool True if the value is unique, false otherwise.
     */
    public function isUnique(string $table, string $column, mixed $value, ?int $excludeId = null): bool
    {
        $query = $this->db->table($table)
            ->where($column, '=', $value);

        // exclude condition
        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        // fetch count
        $count = $query->count();

        return $count === 0;
    }
}
