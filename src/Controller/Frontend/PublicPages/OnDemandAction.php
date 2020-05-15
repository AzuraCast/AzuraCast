<?php
namespace App\Controller\Frontend\PublicPages;

use App\Exception\StationNotFoundException;
use App\Exception\StationUnsupportedException;
use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface;

class OnDemandAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        EntityManager $em,
        bool $embed = false
    ): ResponseInterface {
        // Override system-wide iframe refusal
        $response = $response->withHeader('X-Frame-Options', '*');

        $station = $request->getStation();

        if (!$station->getEnablePublicPage()) {
            throw new StationNotFoundException;
        }

        if (!$station->getEnableOnDemand()) {
            throw new StationUnsupportedException;
        }

        // Get list of custom fields.
        $customFieldsRaw = $em->createQuery(/** @lang DQL */ 'SELECT cf.id, cf.short_name, cf.name FROM App\Entity\CustomField cf ORDER BY cf.name ASC')
            ->getArrayResult();

        $customFields = [];
        foreach ($customFieldsRaw as $row) {
            $customFields[] = [
                'display_key' => 'media_custom_' . $row['id'],
                'key' => $row['short_name'],
                'label' => $row['name'],
            ];
        }

        $templateName = ($embed)
            ? 'frontend/public/ondemand_embed'
            : 'frontend/public/ondemand';

        return $request->getView()->renderToResponse($response, $templateName, [
            'station' => $station,
            'custom_fields' => $customFields,
        ]);
    }
}