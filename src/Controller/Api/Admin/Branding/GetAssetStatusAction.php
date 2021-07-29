<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Branding;

use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class GetAssetStatusAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $type
    ): ResponseInterface {
        $customization = $request->getCustomization();
        
        $url = $customization->getCustomAssetUrl($type);
        $isUploaded = (null !== $url);
        
        if (!$isUploaded) {
            
        }
        
        return $response->withJson(
            [
                'is_uploaded' => $isUploaded,
                'url' => $url,
            ]
        );
    }
}
