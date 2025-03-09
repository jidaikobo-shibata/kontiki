<?php

namespace Jidaikobo\Kontiki\Models\BaseModelTraits;

use Carbon\Carbon;

trait UtilsTrait
{
    public function processDataBeforeSave(array $data): array
    {
        foreach ($data as $field => $value) {
            if (!empty($this->fieldDefinitions[$field]['save_as_utc'])) {
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
            if (!empty($this->fieldDefinitions[$field]['save_as_utc']) && !empty($value)) {
                $date = Carbon::parse($value, 'UTC')->setTimezone(env('TIMEZONE', 'UTC'));
                $data[$field] = $date->format('Y-m-d H:i:s');
            }
        }
        return $data;
    }

    /**
     * Get options in the form of id => field value, excluding a specific ID.
     *
     * @param string $fieldName The field name to use as the value.
     * @param bool $includeEmpty Whether to include an empty option at the start.
     * @param string $emptyLabel The label for the empty option (default: '').
     * @param int|null $excludeId The ID to exclude from the results (default: null).
     * @return array Associative array of id => field value.
     */
    public function getOptions(string $fieldName, bool $includeEmpty = false, string $emptyLabel = '', ?int $excludeId = null): array
    {
        $query = $this->db->table($this->table)->select(['id', $fieldName]);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        $results = $query->get();

        $options = array_column(
            array_map(fn($row) => $this->processRow($row, $fieldName), $results->toArray()),
            1,
            0
        );

        if ($includeEmpty) {
            $options = ['' => $emptyLabel] + $options;
        }

        return $options;
    }

    /**
     * Process a row of data before retrieving.
     *
     * @param object $row The database row object.
     * @param string $fieldName The field name to extract.
     * @return mixed The processed field value.
     */
    private function processRow(object $row, string $fieldName)
    {
        if (!isset($row->id, $row->$fieldName)) {
            return null;
        }

        $processedRow = $this->processDataBeforeGet((array)$row);

        return [$row->id, $processedRow[$fieldName]];
    }
}
