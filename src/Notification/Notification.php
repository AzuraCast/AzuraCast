<?php
namespace App\Notification;

use Azura\Session\Flash;

class Notification
{
    // Alert type constants.
    const SUCCESS   = Flash::SUCCESS;
    const WARNING   = Flash::WARNING;
    const ERROR     = Flash::ERROR;
    const INFO      = Flash::INFO;

    /** @var string */
    protected $title;

    /** @var string */
    protected $body;

    /** @var string */
    protected $type;

    /**
     * Notification constructor.
     * @param string $title
     * @param string $body
     * @param string $type
     */
    public function __construct(string $title, string $body, string $type)
    {
        $this->title = $title;
        $this->body = $body;
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }
}
