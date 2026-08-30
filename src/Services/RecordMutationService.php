<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Services;

use Exception;
use InvalidArgumentException;
use Jidaikobo\Kontiki\Models\DeletableModelInterface;
use Jidaikobo\Kontiki\Models\SoftDeletableModelInterface;

final class RecordMutationService
{
    public const ACTION_RESTORE = 'restore';
    public const ACTION_TRASH = 'trash';

    /** @param array<string, mixed> $data */
    public function validateDelete(
        DeletableModelInterface $model,
        array $data,
        int $id
    ): RecordMutationResult {
        $validation = $model->validate(
            $data,
            ['id' => $id, 'context' => 'delete']
        );
        if ($validation['valid']) {
            return RecordMutationResult::success();
        }

        return RecordMutationResult::failure(
            RecordMutationResult::FAILURE_VALIDATION,
            $validation['errors']
        );
    }

    public function delete(
        DeletableModelInterface $model,
        int $id
    ): RecordMutationResult {
        return $this->execute(static fn(): bool => $model->delete($id));
    }

    public function changeState(
        SoftDeletableModelInterface $model,
        int $id,
        string $action
    ): RecordMutationResult {
        $operation = match ($action) {
            self::ACTION_TRASH => static fn(): bool => $model->trash($id),
            self::ACTION_RESTORE => static fn(): bool => $model->restore($id),
            default => throw new InvalidArgumentException(
                "Unsupported record state action: {$action}"
            ),
        };

        return $this->execute($operation);
    }

    /** @param callable(): bool $operation */
    private function execute(callable $operation): RecordMutationResult
    {
        try {
            $success = $operation();
        } catch (Exception) {
            return RecordMutationResult::failure(
                RecordMutationResult::FAILURE_EXCEPTION
            );
        }

        return $success
            ? RecordMutationResult::success()
            : RecordMutationResult::failure(
                RecordMutationResult::FAILURE_OPERATION
            );
    }
}
