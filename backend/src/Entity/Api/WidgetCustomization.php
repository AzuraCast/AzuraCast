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
    #[OA\Property(nullable: true, description: 'Primary accent color (hex without #).', example: '2196F3')]
    public ?string $primaryColor = null;

    #[OA\Property(nullable: true, description: 'Background color (hex without #).', example: 'ffffff')]
    public ?string $backgroundColor = null;

    #[OA\Property(nullable: true, description: 'Text color (hex without #).', example: '000000')]
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
    public bool $showHistoryButton = false;

    #[OA\Property(description: 'Whether the request button is visible.', example: false)]
    public bool $showRequestButton = false;

    #[OA\Property(description: 'Initial player volume (0-100).', example: 75, minimum: 0, maximum: 100)]
    public int $initialVolume = 75;

    #[OA\Property(description: 'Layout variant for the widget.', example: 'horizontal')]
    public string $layout = 'horizontal';

    #[OA\Property(description: 'Whether to show an "open popup" button.', example: false)]
    public bool $enablePopupPlayer = false;

    #[OA\Property(description: 'Whether to persist playback state across pages.', example: false)]
    public bool $continuousPlay = false;

    #[OA\Property(nullable: true, description: 'Additional CSS applied to the widget.', example: '.radio-player-widget { border-radius: 12px; }')]
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

        $instance->showAlbumArt = empty($request->getQueryParam('hide_album_art'));
        $instance->roundedCorners = !empty($request->getQueryParam('rounded'));
        $instance->autoplay = !empty($request->getQueryParam('autoplay'));
        $instance->showVolumeControls = empty($request->getQueryParam('hide_volume'));
        $instance->showTrackProgress = empty($request->getQueryParam('hide_progress'));
        $instance->showStreamSelection = empty($request->getQueryParam('hide_streams'));
        $instance->showHistoryButton = !empty($request->getQueryParam('show_history'));
        $instance->showRequestButton = !empty($request->getQueryParam('show_requests'));

    $allowPopup = $request->getQueryParam('allow_popup');
    $instance->enablePopupPlayer = !empty($allowPopup);
        $instance->continuousPlay = !empty($request->getQueryParam('continuous'));

        if ($volume = $request->getQueryParam('volume')) {
            $instance->initialVolume = Types::int($volume);
        }

        if ($layout = $request->getQueryParam('layout')) {
            $instance->layout = Types::string($layout);
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

    public function getCssVariables(): array
    {
        $vars = [];

        if ($this->primaryColor) {
            $vars['--widget-primary-color'] = '#' . $this->primaryColor;
        }

        if ($this->backgroundColor) {
            $vars['--widget-bg-color'] = '#' . $this->backgroundColor;
        }

        if ($this->textColor) {
            $vars['--widget-text-color'] = '#' . $this->textColor;
        }

        if ($this->roundedCorners) {
            $vars['--widget-border-radius'] = '8px';
        }

        return $vars;
    }

    public function getCustomCssDecoded(): ?string
    {
        return $this->customCss;
    }
}
