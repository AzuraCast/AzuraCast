<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Http\ServerRequest;
use App\Utilities\Types;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_WidgetCustomization',
    type: 'object'
)]
final class WidgetCustomization
{
    #[OA\Property(description: 'Primary accent color (hex without #).', example: '2196F3', nullable: true)]
    public ?string $primaryColor = null;

    #[OA\Property(description: 'Background color (hex without #).', example: 'ffffff', nullable: true)]
    public ?string $backgroundColor = null;

    #[OA\Property(description: 'Text color (hex without #).', example: '000000', nullable: true)]
    public ?string $textColor = null;

    #[OA\Property(description: 'Whether album art should be shown.', example: true)]
    public bool $showAlbumArt = true;

    #[OA\Property(description: 'Whether the widget should use rounded corners.', example: false)]
    public bool $roundedCorners = false;

    #[OA\Property(description: 'Whether autoplay should be requested.', example: false)]
    public bool $autoplay = false;

    #[OA\Property(description: 'Whether the volume controls are visible.', example: true)]
    public bool $showVolumeControls = true;

    #[OA\Property(description: 'Whether track progress is visible.', example: true)]
    public bool $showTrackProgress = true;

    #[OA\Property(description: 'Whether stream selection controls are visible.', example: true)]
    public bool $showStreamSelection = true;

    #[OA\Property(description: 'Whether the history button is visible.', example: false)]
    public bool $showHistoryButton = true;

    #[OA\Property(description: 'Whether the request button is visible.', example: false)]
    public bool $showRequestButton = true;

    #[OA\Property(description: 'Whether the playlist download button is visible.', example: false)]
    public bool $showPlaylistButton = true;

    #[OA\Property(description: 'Initial player volume (0-100).', maximum: 100, minimum: 0, example: 75)]
    public int $initialVolume = 75;

    #[OA\Property(description: 'Layout variant for the widget.', example: 'horizontal')]
    public string $layout = 'horizontal';

    #[OA\Property(description: 'Whether to show an "open popup" button.', example: false)]
    public bool $enablePopupPlayer = false;

    #[OA\Property(description: 'Whether to persist playback state across pages.', example: false)]
    public bool $continuousPlay = false;

    #[OA\Property(
        description: 'Additional CSS applied to the widget.',
        example: '.radio-player-widget { border-radius: 12px; }',
        nullable: true
    )]
    public ?string $customCss = null;

    public static function fromRequest(ServerRequest $request): self
    {
        // Create instance with parameters from query string
        $instance = new self();

        // Set properties based on query parameters
        if ($primaryColor = self::sanitizeColor($request->getQueryParam('primary_color'))) {
            $instance->primaryColor = $primaryColor;
        }
        if ($backgroundColor = self::sanitizeColor($request->getQueryParam('bg_color'))) {
            $instance->backgroundColor = $backgroundColor;
        }
        if ($textColor = self::sanitizeColor($request->getQueryParam('text_color'))) {
            $instance->textColor = $textColor;
        }

        $instance->showAlbumArt = !Types::bool(
            $request->getQueryParam('hide_album_art'),
            false,
            true
        );
        $instance->roundedCorners = Types::bool(
            $request->getQueryParam('rounded'),
            false,
            true
        );
        $instance->autoplay = Types::bool(
            $request->getQueryParam('autoplay'),
            false,
            true
        );
        $instance->showVolumeControls = !Types::bool(
            $request->getQueryParam('hide_volume'),
            false,
            true
        );
        $instance->showTrackProgress = !Types::bool(
            $request->getQueryParam('hide_progress'),
            false,
            true
        );
        $instance->showStreamSelection = !Types::bool(
            $request->getQueryParam('hide_streams'),
            false,
            true
        );
        $instance->showHistoryButton = !Types::bool(
            $request->getQueryParam('hide_history'),
            false,
            true
        );
        $instance->showRequestButton = !Types::bool(
            $request->getQueryParam('hide_requests'),
            false,
            true
        );
        $instance->showPlaylistButton = !Types::bool(
            $request->getQueryParam('hide_playlist'),
            false,
            true
        );
        $instance->enablePopupPlayer = Types::bool(
            $request->getQueryParam('allow_popup'),
            false,
            true
        );
        $instance->continuousPlay = Types::bool(
            $request->getQueryParam('continuous'),
            false,
            true
        );

        if ($volume = Types::intOrNull($request->getQueryParam('volume'))) {
            $instance->initialVolume = $volume;
        }
        if ($layout = Types::stringOrNull($request->getQueryParam('layout'))) {
            $instance->layout = $layout;
        }

        if ($customCss = $request->getQueryParam('custom_css')) {
            $decoded = base64_decode($customCss, true);
            if ($decoded !== false) {
                $instance->customCss = $decoded;
            }
        }

        return $instance;
    }

    private static function sanitizeColor(?string $color): ?string
    {
        if (empty($color)) {
            return null;
        }

        // Remove # if present and validate hex color
        $color = ltrim($color, '#');
        if (preg_match('/^[a-fA-F0-9]{6}$/', $color)) {
            return $color;
        }

        return null;
    }
}
