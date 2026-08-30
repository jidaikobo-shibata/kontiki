<?php

namespace Jidaikobo\Kontiki\Core;

use Aura\Session\Session;
use Jidaikobo\Kontiki\Models\UserModel;

class Auth
{
    private UserModel $userModel;
    private Session $session;
    private string $segment = 'jidaikobo\kontiki\auth';

    public function __construct(Session $session, UserModel $userModel)
    {
        $this->session = $session;
        $this->userModel = $userModel;
    }

    /**
     * Handles user login.
     *
     * @param string $username Username or email address.
     * @param string $password Password.
     * @return bool Returns true if login is successful, false otherwise.
     */
    public function login(string $username, string $password): bool
    {
        $user = $this->userModel->getByField('username', $username);
        $stored_password = $user['password'] ?? null ;

        if ($stored_password !== null && password_verify($password, $stored_password)) {
            if (!$this->session->isStarted() && !$this->session->start()) {
                return false;
            }
            if (!$this->session->regenerateId()) {
                return false;
            }

            $segment = $this->session->getSegment($this->segment);
            $segment->set('user', $this->identityFromUser($user));
            return true;
        }

        return false;
    }

    /**
     * Handles user logout.
     *
     * @return void
     */
    public function logout(): void
    {
        $this->session->destroy();
    }

    /**
     * Retrieves the current user's information.
     *
     * @return array<string, mixed>|null Returns the logged-in user's information,
     *                                  or null if not logged in.
     */
    public function getCurrentUser(): ?array
    {
        $segment = $this->session->getSegment($this->segment);
        return $segment->get('user');
    }

    /**
     * Checks if a user is logged in.
     *
     * @return bool Returns true if a user is logged in, false otherwise.
     */
    public function isLoggedIn(): bool
    {
        return $this->getCurrentUser() !== null;
    }

    /**
     * Checks if a user is logged in.
     *
     * @return bool Returns true if a user is logged in, false otherwise.
     */
    public function isAdminLoggedIn(): bool
    {
        return $this->getCurrentUser() !== null &&
            $this->getCurrentUser()["role"] === 'admin';
    }

    /**
     * Refresh the session identity from the current database record.
     *
     * Deleted or malformed users are logged out. A role change also rotates
     * the session ID before the new privilege level is stored.
     */
    public function refreshCurrentUser(): bool
    {
        $segment = $this->session->getSegment($this->segment);
        $currentUser = $segment->get('user');
        if (!is_array($currentUser)) {
            return false;
        }

        $id = filter_var($currentUser['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) {
            $this->logout();
            return false;
        }

        $user = $this->userModel->getById($id);
        if ($user === null) {
            $this->logout();
            return false;
        }

        $identity = $this->identityFromUser($user);
        if (($currentUser['role'] ?? null) !== $identity['role']) {
            if (!$this->session->regenerateId()) {
                $this->logout();
                return false;
            }
        }

        $segment->set('user', $identity);
        return true;
    }

    /**
     * @param array<string, mixed> $user
     * @return array{id: mixed, username: mixed, role: mixed}
     */
    private function identityFromUser(array $user): array
    {
        return [
            'id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
        ];
    }
}
