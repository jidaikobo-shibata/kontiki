<?php

namespace Jidaikobo\Kontiki\Models\Traits;

use Carbon\Carbon;
use  Jidaikobo\Kontiki\Managers\FlashManager;

trait CRUDTrait
{
    public function getById(int $id): ?array
    {
        $result = $this->db->table($this->table)
            ->where('id', $id)
            ->first();

        if ($result === null) {
            return null;
        }

        $result = (array)$result;

        if (method_exists($this, 'getAllMetaData')) {
            $metaData = $this->getAllMetaData($id);
            $result = array_merge($result, $metaData);
        }

        $result = $this->processDataBeforeGet($result);

        return $result ? (array)$result : null;
    }

    public function getByField(string $field, mixed $value): ?array
    {
        $result = $this->db->table($this->table)
            ->where($field, $value)
            ->first();

        if ($result === null) {
            return null;
        }

        $result = (array)$result;
        $result = $this->processDataBeforeGet($result);

        return $result ? (array)$result : null;
    }

    public function getByFieldWithCondtioned(
        string $field,
        mixed $value,
        string $context = 'published'
    ): ?array {
        $query = $this->getQuery();
        $query = $this->getAdditionalConditions($query, $context);
        $result = $query
            ->where($field, $value)
            ->where('post_type', $this->postType)
            ->first();

        if (is_object($result)) {
            $result = (array)$result;
            $result = $this->processDataBeforeGet($result);
        }

        return $result ? (array)$result : null;
    }

    public function getDataForForm(
        string $actionType,
        FlashManager $flashManager,
        ?int $id = null
    ): array {
        $data = $flashManager->getData('data') ?: ($id ? $this->getById($id) : []);
        return $this->processDataForForm($actionType, $data);
    }

    protected function processDataForForm(string $actionType, array $data): array
    {
        return $data;
    }

    protected function setPosttypeBeforeSave(array $data): array
    {
        $post_type = $data['post_type'] ?? '';
        if (!empty($post_type)) {
            return $data;
        }
        if (!empty($this->postType)) {
            $data['post_type'] = $this->postType;
        }
        return $data;
    }

    protected function processDataBeforeSave(string $context, array $data): array
    {
        foreach ($data as $field => $value) {
            $saveAsUtc = $this->getFieldDefinitions()[$field]['save_as_utc'] ?? false;
            if ($saveAsUtc) {
                if (empty($value)) {
                    $data[$field] = null;
                } else {
                    $date = Carbon::parse($value, env('TIMEZONE', 'UTC'))->setTimezone('UTC');
                    $data[$field] = $date->format('Y-m-d H:i:s');
                }
            }
        }
        return $this->afterProcessDataBeforeSave($context, $data);
    }

    protected function processDataBeforeGet(array $data): array
    {
        foreach ($data as $field => $value) {
            $saveAsUtc = $this->getFieldDefinitions()[$field]['save_as_utc'] ?? false;
            if ($saveAsUtc && !empty($value)) {
                $date = Carbon::parse($value, 'UTC')->setTimezone(env('TIMEZONE', 'UTC'));
                $data[$field] = $date->format('Y-m-d H:i:s');
            }
        }

        return $this->afterProcessDataBeforeGet($data);
        ;
    }

    protected function afterProcessDataBeforeSave(string $context, array $data): array
    {
        return $data;
    }

    protected function afterProcessDataBeforeGet(array $data): array
    {
        return $data;
    }

    /**
     * Filter the given data array to include only allowed fields.
     *
     * @param array $data The data to filter.
     *
     * @return array The filtered data.
     */
    public function filterAllowedFields(array $data): array
    {
        $allowedFields = array_keys($this->getFieldDefinitions());
        return array_intersect_key($data, array_flip($allowedFields));
    }

    /**
     * Create a new record in the table.
     *
     * @param array $data Key-value pairs of column names and values.
     *
     * @return int|null The ID of the newly created record, or null if the operation failed.
     */
    public function create(array $data, bool $skipFieldFilter = false): ?int
    {
        if (!$skipFieldFilter) {
            $data = $this->filterAllowedFields($data);
        }

        $data = $this->processDataBeforeSave('create', $data);
        $data = $this->setPosttypeBeforeSave($data);

        $success = $this->db->table($this->table)->insert($data);
        return $success ? (int) $this->db->getPdo()->lastInsertId() : null;
    }

    /**
     * Update a record in the table by its ID.
     *
     * @param  int   $id   The ID of the record to update.
     * @param  array $data Key-value pairs of column names and values to update.
     *
     * @return bool True if the record was updated, false otherwise.
     */
    public function update(int $id, array $data, bool $skipFieldFilter = false): bool
    {
        if (!$skipFieldFilter) {
            $data = $this->filterAllowedFields($data);
        }

        $data = $this->processDataBeforeSave('update', $data);
        $data = $this->setPosttypeBeforeSave($data);

        return (bool) $this->db->table($this->table)
            ->where('id', $id)
            ->update($data);
    }

    /**
     * Delte a record in the table by its ID.
     *
     * @param  int   $id   The ID of the record to update.
     *
     * @return bool True if the record was updated, false otherwise.
     */
    public function delete(int $id): bool
    {
        if (!$this->getById($id)) {
            return false;
        }
        return (bool)$this->db->table($this->table)
            ->where('id', $id)
            ->delete();
    }
}
