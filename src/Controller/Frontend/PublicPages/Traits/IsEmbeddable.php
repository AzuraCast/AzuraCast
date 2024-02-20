<?php

declare(strict_types=1);

namespace App\Controller\Frontend\PublicPages\Traits;

use App\Http\ServerRequest;
use App\Utilities\Types;

trait IsEmbeddable
{
    private function isEmbedded(
        ServerRequest $request,
        array $params
    ): bool {
        $embedParam = Types::stringOrNull($params['embed'] ?? null, true);
        if (null !== $embedParam) {
            return true;
        }

        return Types::bool(
            $request->getQueryParam('embed'),
            false,
            true
        );
    }
}
