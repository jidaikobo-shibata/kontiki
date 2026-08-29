<?php

namespace Jidaikobo\Kontiki\Renderers;

use Jidaikobo\Kontiki\Utils\FormUtils;
use Slim\Views\PhpRenderer;

class FormRenderer
{
    private array $fields;
    private FormUtils $formUtils;
    private PhpRenderer $view;

    public function __construct(
        PhpRenderer $view,
        FormUtils $formUtils
    ) {
        $this->view = $view;
        $this->formUtils = $formUtils;
    }

    public function setFields(array $fields): void
    {
        $this->fields = $fields;
    }

    public function render(): string
    {
        $groupedFields = $this->groupFields();

        $html = '';
        foreach ($groupedFields as $group => $fields) {
            $html .= $this->renderGroup($group, $fields);
        }

        return $html;
    }

    protected function groupFields(): array
    {
        $grouped = [];
        foreach ($this->fields as $name => $config) {
            if (!isset($config['type'])) {
                continue;
            }
            $group = $config['group'] ?? 'default';
            $grouped[$group][$name] = $config;
        }
        return $grouped;
    }

    protected function renderGroup(string $group, array $fields): string
    {
        $groupTemplate = $this->getGroupTemplate($group);

        $fieldHtml = array_map(function ($name, $config) {
            return $this->renderFieldset($name, $config);
        }, array_keys($fields), $fields);

        return $this->view->fetch(
            $groupTemplate,
            [
                'fields_html' => implode("\n", $fieldHtml),
                'buttonPosition' => $this->view->getAttributes()['buttonPosition'] ?? 'main'
            ]
        );
    }

    protected function renderFieldset(string $name, array $config): string
    {
        $labelText = $this->generateLabelHtml($name, $config);
        $fieldHtml = $this->renderField($name, $config);
        $fieldsetTemplate = $config['fieldset_template'] ?? 'forms/fieldset/flat.php';

        return $this->view->fetch($fieldsetTemplate, [
            'label' => $labelText,
            'field' => $fieldHtml,
        ]);
    }

    private function generateLabelHtml(string $name, array $config): string
    {
        if ($config['type'] === 'hidden') {
            return ''; // Hidden fields do not need labels
        }

        $id = $this->formUtils->nameToId($name);
        $labelAttributes = $this->renderAttributes(array_merge(
            $config['label_attributes'] ?? [],
            ['for' => $id]
        ));

        $labelText = e($config['label']);
        if (!empty($config['rules']) && in_array('required', $config['rules'], true)) {
            $labelText .= ' (' . __('required') . ')';
        }

        return sprintf(
            '<label %s>%s</label>',
            $labelAttributes,
            e($labelText)
        );
    }

    protected function renderField(string $name, array $config): string
    {
        $id = $this->formUtils->nameToId($name);

        $attributes = $config['attributes'] ?? [];

        if (isset($config['rules']) && in_array('required', $config['rules'], true)) {
            $attributes['required'] = 'required';
        }

        $renderedAttributes = $this->renderAttributes($attributes);

        $fieldTemplate = $this->getFieldTemplate($config['type']);

        $ariaDescribedby = '';
        $ariaDescribedbyAttribute = '';
        $description = $config['description'] ?? '';
        if (!empty($description)) {
            $ariaDescribedby = 'ariaDesc_' . $id;
            $ariaDescribedbyAttribute = ' aria-describedby="' . $ariaDescribedby . '"';
            $description = '<div class="form-text" id="' . $ariaDescribedby . '">' . $description . '</div>';
        }

        return $this->view->fetch($fieldTemplate, [
            'id' => $id,
            'name' => $name,
            'type' => $config['type'],
            'value' => e($config['default'] ?? ''),
            'options' => $config['options'] ?? [],
            'attributes' => $renderedAttributes,
            'ariaDescribedby' => $ariaDescribedby,
            'ariaDescribedbyAttribute' => $ariaDescribedbyAttribute,
            'description' => $description,
        ]);
    }

    /**
     * Render HTML attributes from an associative array.
     *
     * @param array $attributes Associative array of attributes.
     * @return string Rendered attributes as a string.
     */
    protected function renderAttributes(array $attributes): string
    {
        $result = [];
        foreach ($attributes as $key => $value) {
            $result[] = sprintf('%s="%s"', e($key), e($value));
        }
        return implode(' ', $result);
    }

    protected function getFieldTemplate(string $type): string
    {
        $templates = [
            'text' => 'forms/fields/text.php',
            'textarea' => 'forms/fields/textarea.php',
            'checkbox' => 'forms/fields/checkbox.php',
            'radio' => 'forms/fields/radio.php',
            'select' => 'forms/fields/select.php',
        ];
        return $templates[$type] ?? 'forms/fields/text.php';
    }

    protected function getGroupTemplate(string $group): string
    {
        $templates = [
            'header' => 'forms/groups/header.php',
            'main' => 'forms/groups/main.php',
            'meta' => 'forms/groups/meta.php',
        ];
        return $templates[$group] ?? 'forms/groups/default.php';
    }
}
