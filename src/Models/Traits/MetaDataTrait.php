<?php

namespace Jidaikobo\Kontiki\Models\Traits;

trait MetaDataTrait
{
    public function getAllMetaData(int $id): array
    {
        $modelClass = static::class;
        $result = $this->db->table('meta_data')
            ->where('target', $modelClass)
            ->where('target_id', $id)
            ->get()
            ->map(fn($item) => [
                'meta_key' => $item->meta_key,
                'meta_value' => json_decode($item->meta_value, true)
            ])
            ->toArray();

        $retvals = [];
        foreach ($result as $each) {
            $retvals[$each['meta_key']] = $each['meta_value'];
        }

        return $retvals;
    }

    public function getMetaData(int $id, string $key): mixed
    {
        $modelClass = static::class;
        $result = $this->db->table('meta_data')
            ->where('target', $modelClass)
            ->where('target_id', $id)
            ->where('meta_key', $key)
            ->first();

        if ($result) {
            $result = (array) $result;
            return json_decode($result['meta_value'], true);
        }

        return null;
    }

    public function createMetaData(int $id, string $key, mixed $value): void
    {
        $modelClass = static::class;
        $data = [
            'target' => $modelClass,
            'target_id' => $id,
            'meta_key' => $key,
            'meta_value' => json_encode($value),
        ];
        $this->db->table('meta_data')->insert($data);
    }

    public function updateMetaData(int $id, string $key, mixed $value): void
    {
        $data = [
            'meta_value' => json_encode($value),
        ];

        $modelClass = static::class;
        $this->db->table('meta_data')
            ->where('target', $modelClass)
            ->where('target_id', $id)
            ->where('meta_key', $key)
            ->update($data);
    }

    public function deleteMetaData(int $id, string $key): void
    {
        $modelClass = static::class;
        $this->db->table('meta_data')
            ->where('target', $modelClass)
            ->where('target_id', $id)
            ->where('meta_key', $key)
            ->delete();
    }
}
