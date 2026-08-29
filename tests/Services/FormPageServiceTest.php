<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Tests\Services;

use Jidaikobo\Kontiki\Models\ModelInterface;
use Jidaikobo\Kontiki\Services\FormPageService;
use Jidaikobo\Kontiki\Services\FormService;
use PHPUnit\Framework\TestCase;

final class FormPageServiceTest extends TestCase
{
    public function testBuildsFieldsAndAddsMessagesToRenderedForm(): void
    {
        $data = ['title' => 'Existing title'];
        $fields = ['title' => ['type' => 'text']];
        $formVars = ['buttonID' => 'mainSubmitBtn', 'data' => $data];
        $errors = [['messages' => ['Error']]];
        $success = ['Saved'];

        $model = $this->createMock(ModelInterface::class);
        $model->expects(self::once())
            ->method('getFields')
            ->with('edit', $data)
            ->willReturn($fields);

        $formService = $this->createMock(FormService::class);
        $formService->expects(self::once())->method('setModel')->with($model);
        $formService->expects(self::once())
            ->method('formHtml')
            ->with('/post/edit/10', $fields, 'csrf-token', $formVars)
            ->willReturn('<form>raw</form>');
        $formService->expects(self::once())
            ->method('addMessages')
            ->with('<form>raw</form>', $errors, $success)
            ->willReturn('<form>rendered</form>');

        $result = (new FormPageService($formService))->render(
            $model,
            'edit',
            '/post/edit/10',
            $data,
            'csrf-token',
            $formVars,
            $errors,
            $success
        );

        self::assertSame('<form>rendered</form>', $result);
    }
}
