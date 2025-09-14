<?php

declare(strict_types=1);

namespace App\OpenApi\Response;

use OpenApi\Attributes\Attachable;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\MediaType;
use OpenApi\Attributes\Response;
use OpenApi\Attributes\XmlContent;

abstract class AbstractResponse extends Response
{
    public function __construct(
        object|string|null $ref = null,
        int|string|null $response = null,
        ?string $description = null,
        ?array $headers = null,
        MediaType|JsonContent|array|Attachable|XmlContent|null $content = null,
        ?array $links = null,
        ?array $x = null,
        ?array $attachables = null
    ) {
        $response ??= $this->getDefaultResponse();
        $description ??= $this->getDefaultDescription();

        if (null === $content && null === $ref) {
            $ref = $this->getDefaultRef();
        }

        parent::__construct($ref, $response, $description, $headers, $content, $links, $x, $attachables);
    }

    abstract protected function getDefaultRef(): string;

    abstract protected function getDefaultResponse(): int;

    abstract protected function getDefaultDescription(): string;
}
