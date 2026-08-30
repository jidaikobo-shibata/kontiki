<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Tests\Core;

use Aura\Session\SegmentInterface;
use Aura\Session\Session;
use Jidaikobo\Kontiki\Core\Auth;
use Jidaikobo\Kontiki\Models\UserModel;
use PHPUnit\Framework\TestCase;

final class AuthTest extends TestCase
{
    public function testRegeneratesSessionAndStoresOnlyRequiredIdentityData(): void
    {
        $user = [
            'id' => 42,
            'username' => 'editor',
            'password' => password_hash('correct-password', PASSWORD_DEFAULT),
            'role' => 'editor',
            'created_at' => '2026-08-30 00:00:00',
        ];
        $userModel = $this->createMock(UserModel::class);
        $userModel->method('getByField')->with('username', 'editor')->willReturn($user);

        $segment = $this->createMock(SegmentInterface::class);
        $segment->expects(self::once())->method('set')->with('user', [
            'id' => 42,
            'username' => 'editor',
            'role' => 'editor',
        ]);

        $session = $this->createMock(Session::class);
        $session->method('isStarted')->willReturn(false);
        $session->expects(self::once())->method('start')->willReturn(true);
        $session->expects(self::once())->method('regenerateId')->willReturn(true);
        $session->method('getSegment')->willReturn($segment);

        self::assertTrue((new Auth($session, $userModel))->login('editor', 'correct-password'));
    }

    public function testDoesNotAuthenticateWhenSessionIdCannotBeRegenerated(): void
    {
        $userModel = $this->createMock(UserModel::class);
        $userModel->method('getByField')->willReturn([
            'id' => 1,
            'username' => 'admin',
            'password' => password_hash('correct-password', PASSWORD_DEFAULT),
            'role' => 'admin',
        ]);

        $session = $this->createMock(Session::class);
        $session->method('isStarted')->willReturn(true);
        $session->expects(self::never())->method('start');
        $session->expects(self::once())->method('regenerateId')->willReturn(false);
        $session->expects(self::never())->method('getSegment');

        self::assertFalse((new Auth($session, $userModel))->login('admin', 'correct-password'));
    }
}
