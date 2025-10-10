<?php

declare(strict_types=1);

namespace App\Assets;

enum AssetTypes: string
{
    case AlbumArt = 'album_art';
    case Background = 'background';
    case BrowserIcon = 'browser_icon';
}
