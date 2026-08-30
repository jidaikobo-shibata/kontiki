<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Services;

use Jidaikobo\Kontiki\Managers\FlashManager;

final class RecordMutationFeedbackService
{
    public function __construct(private FlashManager $flashManager)
    {
    }

    public function apply(
        RecordMutationResult $result,
        RecordMutationFeedbackConfig $config
    ): string {
        if ($result->success) {
            $this->flashManager->addMessage(
                'success',
                __(
                    $config->successMessageKey,
                    $config->successMessageTemplate,
                    ['name' => __($config->label)]
                )
            );

            return $config->successTarget;
        }

        if ($result->failure === RecordMutationResult::FAILURE_EXCEPTION) {
            $this->flashManager->addErrors([
                __(
                    $config->failureMessageKey,
                    $config->failureMessageTemplate,
                    ['name' => __($config->label)]
                ),
            ]);
        }

        return $config->failureTarget;
    }
}
