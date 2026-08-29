<?php

namespace Jidaikobo\Kontiki\Core;

use Aura\Session\Session;
use Jidaikobo\Kontiki\Core\Database;
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
            // Login Success
            $segment = $this->session->getSegment($this->segment);
            $segment->set('user', $user);
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
     * @return array|null Returns the logged-in user's information, or null if not logged in.
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
}
