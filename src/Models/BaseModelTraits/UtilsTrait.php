<?php

namespace Jidaikobo\Kontiki\Models\BaseModelTraits;

trait UtilsTrait
{
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
        if (empty($fieldName)) {
            throw new \InvalidArgumentException('Field name cannot be empty.');
        }

        // クエリを準備
        $query = $this->db->table($this->table)
            ->select(['id', $fieldName]);

        // 除外IDがある場合はフィルタリング
        if (!is_null($excludeId)) {
            $query->where('id', '!=', $excludeId);
        }

        $results = $query->get();

        $options = [];

        foreach ($results as $row) {
            if (isset($row->id, $row->$fieldName)) {
                $processedRow = $this->processDataBeforeGet((array)$row);
                $options[$row->id] = $processedRow[$fieldName];
            }
        }

        if ($includeEmpty) {
            $options = ['' => $emptyLabel] + $options;
        }

        return $options;
    }
}
