<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Services;

use InvalidArgumentException;
use Jidaikobo\Kontiki\Core\Database;
use Jidaikobo\Kontiki\Models\LegacyMetadataModelInterface;
use Jidaikobo\Kontiki\Models\PersistableModelInterface;
use RuntimeException;

final class RecordPersistenceService
{
    public function __construct(private Database $database)
    {
    }

    /** @param array<string, mixed> $data */
    public function save(
        PersistableModelInterface $model,
        string $context,
        ?int $id,
        array $data
    ): int {
        [$recordData, $metadata] = $this->divideMetadata($model, $data);

        return $this->database->getConnection()->transaction(
            function () use ($model, $context, $id, $recordData, $metadata): int {
                $savedId = $this->saveRecord(
                    $model,
                    $context,
                    $id,
                    $recordData
                );
                $this->saveLegacyMetadata($model, $savedId, $metadata);

                return $savedId;
            }
        );
    }

    /**
     * @param array<string, mixed> $data
     * @return array{array<string, mixed>, array<string, mixed>}
     */
    private function divideMetadata(
        PersistableModelInterface $model,
        array $data
    ): array {
        $metadata = [];

        foreach ($model->getMetaDataFieldDefinitions() as $key => $_definition) {
            if (isset($data[$key])) {
                $metadata[$key] = $data[$key];
                unset($data[$key]);
            }
        }

        return [$data, $metadata];
    }

    /** @param array<string, mixed> $data */
    private function saveRecord(
        PersistableModelInterface $model,
        string $context,
        ?int $id,
        array $data
    ): int {
        if ($context === 'create') {
            $newId = $model->create($data);
            if ($newId === null) {
                throw new RuntimeException(
                    'Failed to create record. No ID returned.'
                );
            }

            return $newId;
        }

        if ($context === 'edit' && $id !== null) {
            $model->update($id, $data);
            return $id;
        }

        throw new InvalidArgumentException(
            'Invalid action type or missing ID.'
        );
    }

    /** @param array<string, mixed> $metadata */
    private function saveLegacyMetadata(
        PersistableModelInterface $model,
        int $id,
        array $metadata
    ): void {
        if ($metadata === []) {
            return;
        }

        if (!$model instanceof LegacyMetadataModelInterface) {
            throw new RuntimeException(
                'Model defines legacy metadata fields but cannot persist them.'
            );
        }

        foreach ($metadata as $key => $value) {
            $existing = $model->getMetaData($id, $key);

            if ($value === '' || $value === null) {
                $model->deleteMetaData($id, $key);
            } elseif ($existing !== null) {
                $model->updateMetaData($id, $key, $value);
            } else {
                $model->createMetaData($id, $key, $value);
            }
        }
    }
}
