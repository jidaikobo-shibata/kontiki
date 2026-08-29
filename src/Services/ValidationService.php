<?php

namespace Jidaikobo\Kontiki\Services;

use Illuminate\Database\Connection;
use Valitron\Validator;
use Jidaikobo\Kontiki\Core\Database;
use Jidaikobo\Kontiki\Models\ModelInterface;

class ValidationService
{
    private Connection $db;
    private Validator $validator;
    private ?ModelInterface $model = null;

    /**
     * ValidationService constructor.
     *
     * @param Validator $validator Instance of Valitron\Validator
     */
    public function __construct(
        Database $db,
        Validator $validator
    ) {
        $this->db = $db->getConnection();
        $this->validator = $validator;
        $this->validator->setPrependLabels(false);
        $this->registerCustomRules();
    }

    public function setModel(ModelInterface $model): void
    {
        $this->model = $model;
    }

    /**
     * Validate the given data based on field definitions.
     *
     * @param array $data The input data to validate.
     * @param array $context Additional validation context (e.g., ['id' => 123, 'role' => 'admin']).
     * @return array Validation result with 'valid' (bool) and 'errors' (array).
     */
    public function validate(
        array $data,
        array $context
    ): array {
        // set data/field to `valitron\validator`
        $validator = $this->validator->withData($data);

        // get fieldDefinitions from model
        $fields = $this->model->getFields(
            $context['context'] ?? 'create',
            $data,
            $context['id'] ?? null,
        );

        // Validation
        foreach ($fields as $field => $definition) {
            $rules = $definition['rules'] ?? [];
            foreach ($rules as $rule) {
                if (is_array($rule)) {
                    $validator->rule($rule[0], $field, ...array_slice($rule, 1));
                } else {
                    $validator->rule($rule, $field);
                }
            }
        }

        $isValid = $validator->validate();

        return [
            'valid' => $isValid,
            'errors' => $isValid ? [] : $this->extractValidationErrors($validator)
        ];
    }

    /**
     * Additional validation logic (to be overridden by subclasses if needed).
     *
     * @param Validator $validator The validator instance.
     * @param array $data The input data to validate.
     * @param array $context Context for validation.
     * @return Validator Modified validator instance.
     */
    public function additionalvalidate(
        Validator $validator,
        array $data,
        array $context
    ): Validator {
        return $validator;
    }

    /**
     * Registers custom validation rules for Valitron\Validator.
     *
     * Currently registers the 'unique' rule for database uniqueness checks.
     *
     * @return void
     */
    protected function registerCustomRules(): void
    {
        Validator::addRule(
            'unique',
            function ($field, $value, array $params, array $fields) {
                return $this->isUnique($params[0], $params[1], $value, $params[2] ?? null);
            },
            __('is_already_exists', 'is already exists')
        );
    }

    /**
     * Extract validation errors from the validator instance.
     *
     * @return array The extracted errors formatted for response.
     */
    private function extractValidationErrors($validator): array
    {
        $errors = [];
        foreach ($validator->errors() as $field => $messages) {
            $errors[$field] = [
                'messages' => $messages,
                'htmlName' => $field,
            ];
        }
        return $errors;
    }

    /**
     * Check if a value is unique.
     *
     * @param string $table The table name to check in.
     * @param string $column The column name to check.
     * @param mixed $value The value to check for uniqueness.
     * @param int|null $excludeId The ID of the record to exclude from the check.
     *
     * @return bool True if the value is unique, false otherwise.
     */
    private function isUnique(
        string $table,
        string $column,
        mixed $value,
        ?int $excludeId = null
    ): bool {
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
