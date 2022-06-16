<?php

namespace Mosparo\Helper;

class MailHelper
{
    protected string $projectDirectory;

    public function __construct(string $projectDirectory)
    {
        $this->projectDirectory = $projectDirectory;
    }

    public function getEmailCssFilePath(): string
    {
        return $this->projectDirectory . '/assets/css/non_http/email.css';
    }

    public function getEmailCssCode(): string
    {
        $path = $this->getEmailCssFilePath();
        if (!file_exists($path)) {
            return '';
        }

        return file_get_contents($path);
    }
}