<?php

namespace Jidaikobo\Kontiki\Handlers;

use DOMDocument;
use Jidaikobo\Kontiki\Models\ModelInterface;
use Jidaikobo\Kontiki\Utils\MessageUtils;
use Jidaikobo\Kontiki\Utils\FormUtils;

class FormHandler
{
    private DOMDocument $dom;
    private FormUtils $formUtils;
    private ?ModelInterface $model = null;

    public function __construct(FormUtils $formUtils)
    {
        $this->formUtils = $formUtils;
    }

    public function setModel(ModelInterface $model): void
    {
        $this->model = $model;
    }

    public function setHtml(string $html): void
    {
        $this->dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        if (!empty($html)) {
            $this->loadHTML($html);
        }
    }

    public function loadHTML(string $html): void
    {
        $map = [0x80, 0x10FFFF, 0, 0xFFFF];
        $html = mb_encode_numericentity($html, $map, 'UTF-8');
        $this->dom->loadHTML(
            $html,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
    }

    /**
     * Set an attribute for an element by its ID.
     *
     * @param  string $id        The ID of the element.
     * @param  string $attribute The attribute name.
     * @param  string $value     The attribute value.
     * @return void
     */
    public function setAttributeById(
        string $id,
        string $attribute,
        string $value
    ): void {
        $element = $this->dom->getElementById($id);
        if ($element) {
            $element->setAttribute($attribute, $value);
        }
    }

    /**
     * Add a CSS class to an element by its ID.
     *
     * @param  string $id    The ID of the element.
     * @param  string $class The CSS class to add.
     * @return void
     */
    public function addClassById(string $id, string $class): void
    {
        $element = $this->dom->getElementById($id);
        if ($element) {
            $currentClass = $element->getAttribute('class') ?: '';
            $classes = array_unique(array_filter(array_merge(explode(' ', $currentClass), [$class])));
            $element->setAttribute('class', implode(' ', $classes));
        }
    }

    public function addErrors(array $errors): void
    {
        if (empty($errors)) {
            return;
        }

        foreach ($errors as $field => $messages) {
            $id = $this->formUtils->nameToId($field);

            // Add ARIA attributes and classes to the element
            $this->setAttributeById($id, 'aria-invalid', 'true');
            $this->setAttributeById($id, 'aria-errormessage', 'errormessage_' . $id);
            $this->addClassById($id, 'is-invalid');
        }

        // Use MessageUtils to generate the error summary
        $errorHtml = MessageUtils::errorHtml($errors, $this->model);
        $this->injectMessageIntoForm($errorHtml);
    }

    public function addSuccessMessages(array $successMessages): void
    {
        if (empty($successMessages)) {
            return;
        }
        $successHtml = MessageUtils::alertHtml(join($successMessages));
        $this->injectMessageIntoForm($successHtml);
    }

    protected function injectMessageIntoForm(string $messageHtml): void
    {
        $form = $this->dom->getElementsByTagName('form')->item(0);
        if ($form) {
            $messageNode = $this->dom->createDocumentFragment();
            $messageNode->appendXML($messageHtml);
            $form->insertBefore($messageNode, $form->firstChild);
        }
    }

    /**
     * Get the modified HTML as a string.
     *
     * @return string The HTML content.
     */
    public function getHtml(): string
    {
        $html = $this->dom->saveHTML();
        return mb_decode_numericentity($html, [0x80, 0x10FFFF, 0, 0xFFFF], 'UTF-8');
    }
}
