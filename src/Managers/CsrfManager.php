<?php

namespace Jidaikobo\Kontiki\Managers;

use Aura\Session\Session;

class CsrfManager
{
    private const SEGMENT_NAME = 'jidaikobo\kontiki\csrf';
    private const MAX_HISTORY = 10;
    private const EXPIRATION_TIME = 300; // sec.
    private Session $session;
    private $segment;

    public function __construct(Session $session)
    {
        $this->session = $session;
        $this->segment = $this->session->getSegment(self::SEGMENT_NAME);
    }

    /**
     * Obtaining a CSRF token
     *
     * @return string
     */
    public function getToken(): string
    {
        return $this->session->getCsrfToken()->getValue();
    }

    /**
     * Validating CSRF tokens
     *
     * @param string|null $token
     * @return bool
     */
    public function isValid(?string $token): bool
    {
        $currentToken = $this->getToken();
        $history = $this->getTokenHistory();

        // Allows current token or tokens in history
        return (!empty($currentToken) && hash_equals($currentToken, $token)) ||
               in_array($token, array_column($history, 'token'), true);
    }

    /**
     * Regenerating the CSRF token
     *
     * @return void
     */
    public function regenerate(): void
    {
        $csrfToken = $this->session->getCsrfToken();

        // Add the current token to the history
        $history = $this->getTokenHistory();
        $history[] = [
            'token' => $csrfToken->getValue(),
            'timestamp' => time(),
        ];

        // Trim history (remove old stuff)
        $history = array_filter($history, function ($entry) {
            return time() - $entry['timestamp'] <= self::EXPIRATION_TIME;
        });
        if (count($history) > self::MAX_HISTORY) {
            $history = array_slice($history, -self::MAX_HISTORY);
        }

        // Save history in session
        $this->segment->set('csrf_token_history', $history);

        // Regenerating the CSRF token
        $csrfToken->regenerateValue();
    }

    /**
     * Get Token History
     *
     * @return array
     */
    private function getTokenHistory(): array
    {
        return $this->segment->get('csrf_token_history') ?? [];
    }
}
