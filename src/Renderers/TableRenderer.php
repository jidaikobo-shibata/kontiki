<?php

namespace Jidaikobo\Kontiki\Renderers;

use Carbon\Carbon;
use Slim\Views\PhpRenderer;
use Jidaikobo\Kontiki\Models\ModelInterface;

class TableRenderer
{
    protected $fields;
    protected $data;
    protected $view;
    protected $table;
    protected $adminDirName;
    protected $context;
    protected $routes;
    protected $postType;
    protected $deleteType; // Context: "hardDelete" or "softDelete"
    private ?ModelInterface $model = null;

    public function __construct(PhpRenderer $view)
    {
        $this->view = $view;
    }

    public function setModel(ModelInterface $model): void
    {
        $this->model = $model;
    }

    public function render(
        array $data,
        string $adminDirName,
        array $routes = [],
        string $context = 'all'
    ): string {
        $this->deleteType = $this->model->getDeleteType();
        $this->data = $data;
        $this->adminDirName = $adminDirName;
        $this->routes = $routes;
        $this->context = $context;

        $this->fields = array_filter(
            $this->model->getFields(),
            fn($field) => isset($field['display_in_list']) &&
                ($field['display_in_list'] === true || $field['display_in_list'] == $context)
        );

        $createButton = $this->renderCreateButton();
        $displayModes = $this->renderDisplayModes();
        $headers = $this->renderHeaders();
        $rows = array_map(function ($row) {
            return $this->renderRow($row);
        }, $this->data);

        return $this->view->fetch('tables/table.php', [
            'createButton' => $createButton,
            'displayModes' => $displayModes,
            'headers' => $headers,
            'rows' => implode("\n", $rows),
        ]);
    }

    protected function renderCreateButton(): array
    {
        $filtered = array_filter($this->routes, function ($routes) {
            return in_array('createButton', $routes['type'], true);
        });
        $createButton = !empty($filtered) ? reset($filtered) : [];
        return $createButton;
    }

    protected function renderDisplayModes(): array
    {
        $displayModes = [];

        foreach ($this->routes as $route) {
            if (strpos($route['path'], $this->adminDirName . '/index') === false) {
                continue;
            }

            $displayModes[] = [
                'name' => __(basename($route['path'])),
                'path' => $route['path'],
            ];
        }

        return $displayModes;
    }

    protected function renderHeaders(): string
    {
        $headerHtml = '';
        foreach ($this->fields as $name => $config) {
            $label = e($config['label']);
            $headerHtml .= sprintf('<th class="text-nowrap">%s</th>', $label);
        }
        $headerHtml .= '<th>' . __('actions') . '</th>';
        return $headerHtml;
    }

    protected function renderRow(array $row): string
    {
        $cellsHtml = $this->renderValues($row);
        $cellsHtml .= $this->renderActions($row);

        return sprintf('<tr>%s</tr>', $cellsHtml);
    }

    protected function renderValues(array $row): string
    {
        $currentTime = Carbon::now('UTC')->setTimezone(env('TIMEZONE', 'UTC'));
        $cellsHtml = '';

        foreach (array_keys($this->fields) as $name) {
            $values = $this->getRowValues($name, $row, $currentTime);
            $value = implode(', ', array_filter($values));
            $cellsHtml .= sprintf('<td>%s</td>', e($value));
        }

        return $cellsHtml;
    }

    protected function getRowValues(string $name, array $row, Carbon $currentTime): array
    {
        if ($name === 'status') {
            return $this->getStatusValues($row, $currentTime);
        }

        if ($this->isSelectableField($name)) {
            return [$this->resolveOptionLabel($name, $row[$name] ?? '')];
        }

        if ($this->isUtcField($name)) {
            return [$this->formatDateTimeField($row[$name] ?? '')];
        }

        return [$row[$name] ?? ''];
    }

    private function isSelectableField(string $name): bool
    {
        $type = $this->fields[$name]['type'] ?? 'text';
        return in_array($type, ['select', 'checkbox', 'radio'], true) && $name !== 'status';
    }

    private function resolveOptionLabel(string $name, string|int|null $value): string
    {
        $options = $this->fields[$name]['options'] ?? [];
        return $options[$value] ?? '';
    }

    private function isUtcField(string $name): bool
    {
        return !empty($this->fields[$name]['save_as_utc']);
    }

    private function formatDateTimeField(?string $value): string
    {
        if (empty($value)) {
            return '';
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i');
        } catch (\Exception $e) {
            return $value; // fallback to raw
        }
    }

    private function getStatusValues(array $row, Carbon $currentTime): array
    {
        $values = [__($row['status'] ?? '') ?: ''];

        $this->addStatusIfConditionMet(
            $values,
            $row,
            'published_at',
            $currentTime,
            fn($time) => $time->greaterThan($currentTime),
            'reserved'
        );

        $this->addStatusIfConditionMet(
            $values,
            $row,
            'expired_at',
            $currentTime,
            fn($time) => $currentTime->greaterThan($time),
            'expired'
        );

        return $values;
    }

    /**
     * Add a status to values if the condition is met.
     *
     * @param array  $values      Reference to the values array.
     * @param array  $row         The data row.
     * @param string $key         The key to check in the row.
     * @param Carbon $currentTime The current timestamp.
     * @param callable $condition Callback that takes a Carbon instance and returns a boolean.
     * @param string $status      The status text to add if the condition is met.
     */
    private function addStatusIfConditionMet(
        array &$values,
        array $row,
        string $key,
        Carbon $currentTime,
        callable $condition,
        string $status
    ): void {
        if (!empty($row[$key])) {
            $time = Carbon::parse($row[$key], env('TIMEZONE', 'UTC'));
            if ($condition($time)) {
                $values[0] = __($status);
            }
        }
    }

    protected function renderActions(array $row): string
    {
        $id = e($row['id']);

        $uri = env('BASEPATH', '') . "/{$this->adminDirName}/%s/%s";

        $tpl = '<a href="' . $uri . '" class="btn btn-%s btn-sm">%s</a> ';
        $tplTrash = '<a href="' . $uri . '" class="btn btn-%s btn-sm">%s <span class="fa-solid fa-trash"></span></a> ';
        $tplPreview = '<a href="' . $uri . '" class="btn btn-%s btn-sm" target="preview">%s <span class="fa-solid fa-arrow-up-right-from-square" aria-label="' . __('open_in_new_window') . '"></span></a> ';

        $actions = [
            'edit' => sprintf($tpl, 'edit', $id, 'primary', __('edit')),
            'delete' => sprintf($tplTrash, 'delete', $id, 'danger', __('delete')),
            'trash' => sprintf($tplTrash, 'trash', $id, 'warning', __('to_trash')),
            'restore' => sprintf($tpl, 'restore', $id, 'success', __('restore')),
            'preview' => sprintf($tplPreview, 'preview', $id, 'info', __('preview')),
        ];

        $previewBtn = $this->view->getAttributes()['is_previewable'] ? $actions['preview'] : '';

        $html = '';
        if ($this->deleteType == 'hardDelete') {
            $html .= $actions['edit'] . $actions['delete'];
        } elseif ($this->context == 'trash') {
            $html .= $actions['restore'] . $previewBtn . $actions['delete'];
        } elseif ($this->deleteType == 'softDelete') {
            $html .= $actions['edit'] . $previewBtn . $actions['trash'];
        } else {
            $html .= $actions['edit'];
        }

        return sprintf('<td class="text-nowrap">%s</td>', $html);
    }
}
