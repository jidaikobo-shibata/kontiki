<?php

namespace Jidaikobo\Kontiki\Models\BaseModelTraits;

/**
 * Trait FieldDefinitionTrait
 *
 * Provides methods for defining and managing field definitions in models.
 */
trait FieldDefinitionTrait
{
    protected ?array $fieldDefinitions = null;
    protected ?array $metaDataFieldDefinitions = null;

    public function getFields(
        string $context = '',
        array $data = [],
        int $id = null
    ): array {
        if (!empty($context)) {
            $this->processFields($context, $data, $id);
        }

        return array_merge($this->fieldDefinitions, $this->metaDataFieldDefinitions);
    }

    public function getFieldDefinitions(): array
    {
        return $this->fieldDefinitions;
    }

    public function getMetaDataFieldDefinitions(): array
    {
        return $this->metaDataFieldDefinitions;
    }

    protected function defineFieldDefinitions(): void
    {
        $this->fieldDefinitions = [];
    }

    protected function defineMetaDataFieldDefinitions(): void
    {
        $this->metaDataFieldDefinitions = [];
    }

    protected function processFieldDefinitions(
        string $context = '',
        array $data = [],
        int $id = null
    ): void {
        return;
    }

    protected function disableFormFieldsForContext(): void
    {
        foreach (['metaDataFieldDefinitions', 'fieldDefinitions'] as $fieldType) {
            foreach ($this->$fieldType as &$field) {
                // Set input fields to readonly
                $field['attributes']['readonly'] = 'readonly';

                // Remove file upload class and add "form-control-plaintext"
                $existingClass = $field['attributes']['class'] ?? '';
                $existingClass = str_replace('kontiki-file-upload', '', $existingClass);
                $field['attributes']['class'] = trim(
                    $existingClass . ' form-control-plaintext p-2'
                );

                // Remove descriptions
                $field['description'] = '';
            }
        }
    }

    private function fillValueFieldDefinitions(
        string $context = '',
        array $data = [],
        int $id = null
    ): void {
        foreach (['metaDataFieldDefinitions', 'fieldDefinitions'] as $fieldType) {
            foreach (array_keys($this->$fieldType) as $fieldName) {
                if (isset($data[$fieldName])) {
                    $this->$fieldType[$fieldName]['default'] = $data[$fieldName];
                }
            }
        }
    }

    private function initializeFields(): void
    {
        if ($this->fieldDefinitions === null) {
            $this->defineFieldDefinitions();
        }
    }

    private function initializeMetaDataFields(): void
    {
        if ($this->metaDataFieldDefinitions === null) {
            $this->defineMetaDataFieldDefinitions();
        }
    }

    private function processFields(
        string $context = '',
        array $data = [],
        int $id = null
    ): void {
        // set values for save / form
        $this->fillValueFieldDefinitions($context, $data, $id);

        // process definitions for various purpose
        $this->processFieldDefinitions($context, $data, $id);
    }

    /**
     * Get the definition for Id field.
     *
     * wrapper of read-only field.
     */
    protected function getIdField(): array
    {
        return $this->getReadOnlyField(
            __('ID'),
            [
                'display_in_list' => true
            ]
        );
    }

    /**
     * Get the definition for a read-only field.
     *
     * This method defines the structure of a field that is displayed in lists
     * but not included in form inputs.
     *
     * @param string $label The label to be used for the field.
     * @param array $options Optional settings for the field.
     * @return array The read-only field definition.
     */
    protected function getReadOnlyField(string $label, array $options = []): array
    {
        return [
            'label' => $label,
            'display_in_list' => $options['display_in_list'] ?? false,
            'save_as_utc' => $options['save_as_utc'] ?? false,
        ];
    }

    /**
     * Generate a text field definition.
     *
     * @param string $label The field name.
     * @param array $options Optional settings for the field.
     * @return array Field definition.
     */
    protected function getField(string $label, array $options = []): array
    {
        return [
            'label' => $options['label'] ?? __($label),
            'type' => $options['type'] ?? 'text',
            'description' => $options['description'] ?? '',
            'attributes' => $options['attributes'] ?? ['class' => 'form-control'],
            'label_attributes' => $options['label_attributes'] ?? ['class' => 'form-label'],
            'options' => $options['options'] ?? [],
            'default' => $options['default'] ?? '',
            'searchable' => $options['searchable'] ?? true,
            'rules' => $options['rules'] ?? [],
            'filter' => $options['filter'] ?? (defined('FILTER_SANITIZE_FULL_SPECIAL_CHARS')
                ? FILTER_SANITIZE_FULL_SPECIAL_CHARS
                : FILTER_SANITIZE_SPECIAL_CHARS),
            'template' => $options['template'] ?? 'default',
            'group' => $options['group'] ?? 'main',
            'fieldset_template' => $options['fieldset_template'] ?? 'forms/fieldset/flat.php',
            'display_in_list' => $options['display_in_list'] ?? false,
            'save_as_utc' => $options['save_as_utc'] ?? false
        ];
    }
}
