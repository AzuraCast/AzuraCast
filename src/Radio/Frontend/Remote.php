<?php
namespace App\Radio\Frontend;

class Remote extends FrontendAbstract
{
    public function supportsMounts(): bool
    {
        return false;
    }

    public function supportsListenerDetail(): bool
    {
        return false;
    }

    public function read(): bool
    {
        return true;
    }

    public function write(): bool
    {
        return true;
    }

    public function isRunning(): bool
    {
        return true;
    }

    public function getStreamUrl(): string
    {
        return '';
    }

    public function getStreamUrls(): array
    {
        return [];
    }

    public function getAdminUrl(): string
    {
        return '';
    }
}
