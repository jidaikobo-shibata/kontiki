<?php

namespace Jidaikobo\Kontiki\Models\BaseModelTraits;

use Carbon\Carbon;

trait CRUDTrait
{
    public function processDataBeforeSave(array $data): array
    {
        foreach ($data as $field => $value) {
            if (in_array($field, $this->getUtcFields())) {
                if (empty($value)) {
                    $data[$field] = null;
                } else {
                    $date = Carbon::parse($value, env('TIMEZONE', 'UTC'))->setTimezone('UTC');
                    $data[$field] = $date->format('Y-m-d H:i:s');
                }
            }
        }
        return $data;
    }

    public function processDataBeforeGet(array $data): array
    {
        foreach ($data as $field => $value) {
            if (in_array($field, $this->getUtcFields()) && !empty($value)) {
                $date = Carbon::parse($value, 'UTC')->setTimezone(env('TIMEZONE', 'UTC'));
                $data[$field] = $date->format('Y-m-d H:i:s');
            }
        }
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

    public function getById(int $id): ?array
    {
        $result = $this->db->table($this->table)
            ->where('id', $id)
            ->first();

        if (is_array($result)) {
            $result = $this->processDataBeforeGet($result);
        }

        return $result ? (array)$result : null;
    }

    public function getByField(string $field, mixed $value): ?array
    {
        $result = $this->db->table($this->table)
            ->where($field, $value)
            ->first();

        if (is_array($result)) {
            $result = $this->processDataBeforeGet($result);
        }

        return $result ? (array)$result : null;
    }

    /**
     * Create a new record in the table.
     *
     * @param array $data Key-value pairs of column names and values.
     *
     * @return int|null The ID of the newly created record, or null if the operation failed.
     * @throws InvalidArgumentException If validation fails.
     */
    public function create(array $data, bool $skipFieldFilter = false): ?int
    {
        if (!$skipFieldFilter) {
            $data = $this->filterAllowedFields($data);
        }

        $data = $this->processDataBeforeSave($data);

        $success = $this->db->table($this->table)->insert($data);
        return $success ? $this->db->getPdo()->lastInsertId() : null;
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

        $data = $this->processDataBeforeSave($data);

        return $this->db->table($this->table)
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
