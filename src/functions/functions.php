<?php

if (!function_exists('jlog')) {
    /**
     * Log function for development
     *
     * @param mixed $messages message
     *
     * @return void
     */
    function jlog($messages): void
    {
        $message = match (true) {
            $messages === null => 'null',
            $messages === true => 'true',
            $messages === false => 'false',
            is_scalar($messages) => (string) $messages,
            default => var_export($messages, true),
        };

        error_log($message);
    }
}

if (!function_exists('__')) {
    /**
     * Translate the given text and replace placeholders.
     *
     * @param  string $key     The message key.
     * @param  string $default The default message if the key does not exist.
     * @param  array  $replace Variables to replace in the message.
     *
     * @return string
     */
    function __(string $key, string $default = '', array $replace = []): string
    {
        return \Jidaikobo\Kontiki\Utils\Lang::get($key, $default, $replace);
    }
}

if (!function_exists('e')) {
    /**
     * Escape the given string for safe HTML output.
     *
     * @param  string|null $value The string to escape. Null values are converted to an empty string.
     *
     * @return string
     */
    function e(?string $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('env')) {
    /**
     * Get an environment variable from $_SERVER or $_ENV, or return a default value.
     *
     * @param string $key The environment variable key.
     * @param mixed $default The default value if the key does not exist.
     * @return mixed
     */
    function env(string $key, $default = null)
    {
        static $cache = [];

        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        $value = $default;

        // Check in $_SERVER
        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            $value = $_SERVER[$key];
        }

        // Check in $_ENV
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            $value = $_ENV[$key];
        }

        $cache[$key] = $value;
        return $value;
    }
}

if (!function_exists('setenv')) {
    /**
     * Set an environment variable.
     *
     * @param string $key The environment variable key.
     * @param mixed $value The value to set. If null, the variable will be removed.
     * @return void
     */
    function setenv(string $key, $value): void
    {
        if ($value === null) {
            unset($_SERVER[$key], $_ENV[$key]);
            return;
        }

        if (!is_scalar($value)) {
            throw new \InvalidArgumentException("setenv() only accepts scalar values (string, int, float, bool).");
        }

        $_SERVER[$key] = (string) $value;
        $_ENV[$key] = (string) $value;
    }
}

if (!function_exists('performance')) {
    function performance($timer = false): void
    {
        \Jidaikobo\Kontiki\Bootstrap::performance($timer);
    }
}

if (!function_exists('homeUrl')) {
    function homeUrl(): string
    {
        return env('BASEURL');
    }
}
