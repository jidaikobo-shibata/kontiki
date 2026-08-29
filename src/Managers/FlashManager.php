<?php

namespace Jidaikobo\Kontiki\Managers;

use Aura\Session\Session;

class FlashManager
{
    private Session $session;
    private string $segmentName;

    public function __construct(
        Session $session,
        string $segmentName = 'jidaikobo\kontiki\flash'
    ) {
        $this->session = $session;
        $this->segmentName = $segmentName;
    }

    public function setData(string $type, $data): void
    {
        $segment = $this->session->getSegment($this->segmentName);
        $segment->set($type, $data);
    }

    public function getData(string $type, $default = null)
    {
        $segment = $this->session->getSegment($this->segmentName);
        $data = $segment->get($type, $default);
        $segment->set($type, null); // 取得後に削除
        return $data;
    }

    public function addMessage(string $type, string $message): void
    {
        $existingMessages = $this->getData($type, []);
        $existingMessages[] = $message;
        $this->setData($type, $existingMessages);
    }

    public function addErrors(array $errors): void
    {
        $existingErrors = $this->getData('errors', []);
        $mergedErrors = array_merge_recursive($existingErrors, $errors);
        $this->setData('errors', $mergedErrors);
    }
}
