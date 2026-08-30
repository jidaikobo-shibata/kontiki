<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Tests\Services;

use Jidaikobo\Kontiki\Models\ModelInterface;
use Jidaikobo\Kontiki\Services\ConfirmationFormConfig;
use Jidaikobo\Kontiki\Services\ConfirmationFormService;
use Jidaikobo\Kontiki\Services\FormService;
use PHPUnit\Framework\TestCase;

final class ConfirmationFormServiceTest extends TestCase
{
    public function testBuildsAndDecoratesConfirmationForm(): void
    {
        $data = ['id' => 42, 'title' => 'Article'];
        $fields = ['confirm' => ['type' => 'hidden']];
        $errors = [['messages' => ['Invalid token.']]];
        $config = new ConfirmationFormConfig(
            'trash',
            '/post/trash/42',
            'Are you sure?',
            'btn-danger',
            'mainTrashBtn',
            'Move to trash'
        );
        $model = $this->createMock(ModelInterface::class);
        $model->expects(self::once())->method('getFields')
            ->with('trash', $data)->willReturn($fields);
        $formService = $this->createMock(FormService::class);
        $formService->expects(self::once())->method('formHtml')
            ->with(
                '/post/trash/42',
                $fields,
                'csrf-token',
                [
                    'description' => 'Are you sure?',
                    'buttonClass' => 'btn-danger',
                    'buttonID' => 'mainTrashBtn',
                    'buttonText' => 'Move to trash',
                    'data' => $data,
                ]
            )->willReturn('<form>raw</form>');
        $formService->expects(self::once())->method('addMessages')
            ->with('<form>raw</form>', $errors, [], $model)
            ->willReturn('<form>decorated</form>');

        $result = (new ConfirmationFormService($formService))->render(
            $model,
            $config,
            $data,
            'csrf-token',
            $errors
        );

        self::assertSame('<form>decorated</form>', $result);
    }
}
