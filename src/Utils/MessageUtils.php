<?php

namespace Jidaikobo\Kontiki\Utils;

use Jidaikobo\Kontiki\Models\ModelInterface;
use Jidaikobo\MarkdownExtra;

class MessageUtils
{
    public static function errorHtml(array $errors, ModelInterface $model): string
    {
        // フィールド定義を取得
        $fieldDefinitions = $model->getFields();

        $html = '<div class="errormessages" role="status">';
        $html .= '<ul class="alert alert-danger p-3 ps-5 pt-0 mt-3 mb-3 fs-6">';

        foreach ($errors as $field => $errorDetails) {
            $messages = $errorDetails['messages'] ?? [];
            $htmlName = $errorDetails['htmlName'] ?? $field;

            // フォーム全体のエラー
            if ($field === 0) {
                $html .= '<li class="pt-3">' . __('found_the_problem', 'Found the problem');
                $html .= '<ul class="ps-3">';
                foreach ($messages as $message) {
                    $html .= sprintf('<li class="pt-2">%s</li>', e($message));
                }
                $html .= '</ul></li>';
                continue;
            }

            // フィールド固有のエラー
            $id = FormUtils::nameToId($htmlName);

            $html .= sprintf('<li id="errormessage_%s" class="pt-3">', e($id));

            // モデルからラベルを取得
            $labelText = $fieldDefinitions[$field]['label'] ?? ucfirst($field);

            $html .= sprintf(
                '<a href="#%s" class="alert-link">%s</a>',
                e($id),
                __('error_at_x', 'Error at :name', ['name' => $labelText]),
            );

            $html .= '<ul class="ps-3">';
            foreach ($messages as $message) {
                $html .= sprintf(
                    '<li class="pt-2">%s%s</li>',
                    $labelText,
                    e($message)
                );
            }
            $html .= '</ul></li>';
        }

        $html .= '</ul></div>';

        return $html;
    }

    /**
     * Generates a section for displaying status messages with appropriate styling.
     *
     * @param string $message The message to display within the section.
     * @param string $status The status of the message.
     * @param bool $escape Whether to escape the message.
     * @return string HTML output for the status section.
     */
    public static function alertHtml(
        string $message,
        string $status = "success",
        bool $escape = true
    ): string {
        // Define a status-class mapping table
        $statusClasses = [
            'success' => 'alert alert-success',
            'info'    => 'alert alert-info',
            'warning' => 'alert alert-warning',
            'danger'  => 'alert alert-danger',
        ];

        // Get the CSS class from the table, default to "alert-secondary"
        $statusClass = $statusClasses[$status] ?? 'alert alert-secondary';

        // Escape message if required
        $message = $escape ? e($message) : $message;

        // apply markdown instead of using HTML
        $message = MarkdownExtra::defaultTransform($message);
        $message = strip_tags($message, '<a>');

        // Generate and return the HTML
        return sprintf(
            '<section class="%s" role="status"><p class="mb-0">%s</p></section>',
            e($statusClass),
            $message
        );
    }
}
