<?php

declare(strict_types=1);

namespace App\Controller\Frontend\PublicPages;

use App\Exception\StationNotFoundException;
use App\Exception\StationUnsupportedException;
use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;

class OnDemandAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        EntityManagerInterface $em,
        bool $embed = false
    ): ResponseInterface {
        // Override system-wide iframe refusal
        $response = $response->withHeader('X-Frame-Options', '*');

        $station = $request->getStation();

        if (!$station->getEnablePublicPage()) {
            throw new StationNotFoundException();
        }

        if (!$station->getEnableOnDemand()) {
            throw new StationUnsupportedException();
        }

        // Get list of custom fields.
        $customFieldsRaw = $em->createQuery(
            <<<'DQL'
                SELECT cf.id, cf.short_name, cf.name
                FROM App\Entity\CustomField cf ORDER BY cf.name ASC
            DQL
        )->getArrayResult();

        $customFields = [];
        foreach ($customFieldsRaw as $row) {
            $customFields[] = [
                'display_key' => 'media_custom_fields_' . $row['short_name'],
                'key' => $row['short_name'],
                'label' => $row['name'],
            ];
        }

        return $request->getView()->renderToResponse(
            $response,
            'frontend/public/ondemand',
            [
                'embed' => $embed,
                'station' => $station,
                'custom_fields' => $customFields,
            ]
        );
    }
}
