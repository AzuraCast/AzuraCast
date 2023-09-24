// Type definitions for leaflet-fullscreen 1.0.2
// Project: https://github.com/Leaflet/Leaflet.fullscreen
// Original Definitions by: Denis Carriere <https://github.com/DenisCarriere>
// Updated by : Paul Harwood <https://github.com/runette>

import { ControlOptions } from 'leaflet';

declare module 'leaflet' {
    interface MapOptions {
        fullscreenControl?: boolean | FullscreenOptions;
    }

    interface FullscreenOptions extends ControlOptions {
        pseudoFullscreen?: boolean
        title?: {
            'false': string,
            'true': string
        }
    }

    type fullscreenOptions = FullscreenOptions;

    interface Map {
        isFullscreen(): boolean;
        toggleFullscreen(): void;
    }

    namespace control {
        function fullscreen(options: FullscreenOptions): Control.Fullscreen;
    }

    namespace Control {
        export class Fullscreen extends Control {
            constructor(options: FullscreenOptions);
            options: FullscreenOptions;
        }
    }

}
