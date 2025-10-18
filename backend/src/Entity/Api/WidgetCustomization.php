<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Http\ServerRequest;
use App\Utilities\Types;

final class WidgetCustomization
{
    public ?string $primaryColor = null;
    public ?string $backgroundColor = null;
    public ?string $textColor = null;
    public bool $showAlbumArt = true;
    public bool $roundedCorners = false;
    public bool $autoplay = false;
    public bool $showVolumeControls = true;
    public bool $showTrackProgress = true;
    public bool $showStreamSelection = true;
    public bool $showHistoryButton = false;
    public bool $showRequestButton = false;
    public int $initialVolume = 75;
    public string $layout = 'horizontal';
    public bool $enablePopupPlayer = false;
    public bool $continuousPlay = false;
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
        $instance->enablePopupPlayer = !empty($request->getQueryParam('popup'));
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