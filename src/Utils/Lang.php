<?php

namespace Jidaikobo\Kontiki\Utils;

class Lang
{
    private static string $language = 'en'; // Default language
    private static array $messages = [];
    private static string $langPath = __DIR__ . '/../locale'; // Path to language files
    private static string $appLangPath = __DIR__ . '/../../app/locale'; // Path to language files

    /**
     * Set the language to use.
     *
     * @param  string $language
     * @return void
     */
    public static function setLanguage(string $language): void
    {
        self::$language = $language;
        self::loadLanguage();
    }

    /**
     * Load the language file into memory.
     *
     * @return void
     */
    private static function loadLanguage(): void
    {
        self::$messages = [];
        foreach ([self::$langPath, self::$appLangPath] as $langPath) {
            $langDir = $langPath . '/' . self::$language;
            $files = glob($langDir . '/*.php');
            foreach ($files as $filePath) {
                $messages = include $filePath;
                self::$messages = array_merge(self::$messages, $messages);
            }
        }
    }

    /**
     * Get a specific message by key.
     *
     * @param  string $key     The message key.
     * @param  string $default The default message if the key does not exist.
     * @param  array  $replace Variables to replace in the message.
     * @return string
     */
    public static function get(string $key, string $default = '', array $replace = []): string
    {
        if ($default === '') {
            $default = ucfirst(str_replace('_', ' ', $key));
        }

        $key = strtolower($key);
        $message = self::$messages[$key] ?? $default;

        foreach ($replace as $search => $value) {
            $message = str_replace(':' . $search, $value, $message);
        }

        return $message;
    }
}
