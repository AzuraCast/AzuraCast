/**
 * AzuraCast Embed Widget
 * JavaScript-based alternative to iframe embedding
 */

class AzuraCastEmbed {
    constructor() {
        this.config = {};
        this.containerId = null;
        this.api = null;
    }

    /**
     * Initialize the embed widget
     * @param {Object} config - Widget configuration
     */
    init(config) {
        this.config = {
            // Default configuration
            station: '',
            width: '100%',
            height: 150,
            autoplay: false,
            showAlbumArt: true,
            showVolumeControls: true,
            showTrackProgress: true,
            showStreamSelection: true,
            layout: 'horizontal',
            primaryColor: '#2196F3',
            backgroundColor: 'transparent',
            textColor: 'inherit',
            roundedCorners: false,
            initialVolume: 75,
            // Override with provided config
            ...config
        };

        // Find the container
        this.containerId = config.containerId || 'azuracast-player';
        const container = document.getElementById(this.containerId);
        
        if (!container) {
            console.error('AzuraCast Embed: Container element not found');
            return;
        }

        this.render(container);
        this.loadNowPlayingData();
        
        // Set up periodic updates
        this.startPolling();
    }

    /**
     * Render the player widget
     */
    render(container) {
        // Create the widget HTML
        const widgetHtml = this.generateWidgetHtml();
        container.innerHTML = widgetHtml;
        
        // Apply styles
        this.applyStyles(container);
        
        // Bind event handlers
        this.bindEvents(container);
    }

    /**
     * Generate the HTML for the widget
     */
    generateWidgetHtml() {
        const layoutClass = `layout-${this.config.layout}`;
        const roundedClass = this.config.roundedCorners ? 'rounded-corners' : '';
        
        return `
            <div class="azuracast-embed-widget ${layoutClass} ${roundedClass}" id="azuracast-widget-${this.containerId}">
                <div class="now-playing-details">
                    ${this.config.showAlbumArt ? '<div class="now-playing-art"><img id="album-art" src="" alt="" style="display:none;"></div>' : ''}
                    <div class="now-playing-main">
                        <div class="now-playing-live" id="live-indicator" style="display:none;">
                            <span class="badge">Live</span>
                            <span id="streamer-name"></span>
                        </div>
                        <div class="now-playing-offline" id="offline-indicator" style="display:none;">
                            <h4>Station Offline</h4>
                        </div>
                        <div class="now-playing-song" id="song-info">
                            <h4 class="now-playing-title" id="song-title">Loading...</h4>
                            <h5 class="now-playing-artist" id="song-artist"></h5>
                        </div>
                        ${this.config.showTrackProgress ? `
                            <div class="time-display" id="time-display" style="display:none;">
                                <div class="time-display-played" id="time-played">0:00</div>
                                <div class="time-display-progress">
                                    <div class="progress">
                                        <div class="progress-bar" id="progress-bar" style="width: 0%"></div>
                                    </div>
                                </div>
                                <div class="time-display-total" id="time-total">0:00</div>
                            </div>
                        ` : ''}
                    </div>
                </div>
                <hr class="separator">
                <div class="radio-controls">
                    <button class="play-button" id="play-button" type="button">
                        <span class="play-icon">▶</span>
                        <span class="stop-icon" style="display:none;">⏹</span>
                    </button>
                    ${this.config.showStreamSelection ? `
                        <div class="stream-selection">
                            <select id="stream-select" class="form-select">
                                <option value="">Select Stream...</option>
                            </select>
                        </div>
                    ` : ''}
                    ${this.config.showVolumeControls ? `
                        <div class="volume-controls">
                            <button id="mute-button" type="button" class="mute-btn">🔊</button>
                            <input type="range" id="volume-slider" class="volume-slider" min="0" max="100" value="${this.config.initialVolume}">
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    }

    /**
     * Apply CSS styles to the widget
     */
    applyStyles(container) {
        const styles = `
            .azuracast-embed-widget {
                font-family: system-ui, -apple-system, sans-serif;
                padding: 1rem;
                background-color: ${this.config.backgroundColor};
                color: ${this.config.textColor};
                border-radius: ${this.config.roundedCorners ? '12px' : '0'};
                width: ${this.config.width};
                height: ${typeof this.config.height === 'number' ? this.config.height + 'px' : this.config.height};
                box-sizing: border-box;
                position: relative;
            }
            
            .azuracast-embed-widget .now-playing-details {
                display: flex;
                align-items: center;
                margin-bottom: 1rem;
            }
            
            .azuracast-embed-widget.layout-vertical .now-playing-details {
                flex-direction: column;
                text-align: center;
            }
            
            .azuracast-embed-widget .now-playing-art {
                margin-right: 0.75rem;
            }
            
            .azuracast-embed-widget.layout-vertical .now-playing-art {
                margin-right: 0;
                margin-bottom: 1rem;
            }
            
            .azuracast-embed-widget .now-playing-art img {
                width: 75px;
                height: 75px;
                border-radius: 5px;
            }
            
            .azuracast-embed-widget.layout-compact .now-playing-art img {
                width: 50px;
                height: 50px;
            }
            
            .azuracast-embed-widget.layout-large .now-playing-art img {
                width: 120px;
                height: 120px;
            }
            
            .azuracast-embed-widget .now-playing-title {
                margin: 0;
                font-size: 1.1rem;
                font-weight: bold;
            }
            
            .azuracast-embed-widget .now-playing-artist {
                margin: 0;
                font-size: 0.9rem;
                opacity: 0.8;
            }
            
            .azuracast-embed-widget .badge {
                background-color: ${this.config.primaryColor};
                color: white;
                padding: 0.25rem 0.5rem;
                border-radius: 4px;
                font-size: 0.8rem;
                margin-right: 0.5rem;
            }
            
            .azuracast-embed-widget .separator {
                border: 0;
                height: 1px;
                background-color: currentColor;
                opacity: 0.2;
                margin: 0.75rem 0;
            }
            
            .azuracast-embed-widget .radio-controls {
                display: flex;
                align-items: center;
                gap: 1rem;
            }
            
            .azuracast-embed-widget.layout-vertical .radio-controls {
                justify-content: center;
            }
            
            .azuracast-embed-widget .play-button {
                background-color: ${this.config.primaryColor};
                color: white;
                border: none;
                border-radius: 50%;
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: background-color 0.2s;
            }
            
            .azuracast-embed-widget .play-button:hover {
                background-color: ${this.adjustColor(this.config.primaryColor, -20)};
            }
            
            .azuracast-embed-widget .volume-controls {
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }
            
            .azuracast-embed-widget .mute-btn {
                background: none;
                border: none;
                font-size: 1.2rem;
                cursor: pointer;
                color: inherit;
            }
            
            .azuracast-embed-widget .volume-slider {
                width: 100px;
            }
            
            .azuracast-embed-widget .form-select {
                background-color: transparent;
                border: 1px solid currentColor;
                border-radius: 4px;
                padding: 0.25rem 0.5rem;
                color: inherit;
            }
            
            .azuracast-embed-widget .time-display {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                font-size: 0.8rem;
                margin-top: 0.5rem;
            }
            
            .azuracast-embed-widget .progress {
                flex: 1;
                height: 4px;
                background-color: currentColor;
                opacity: 0.2;
                border-radius: 2px;
                overflow: hidden;
            }
            
            .azuracast-embed-widget .progress-bar {
                height: 100%;
                background-color: ${this.config.primaryColor};
                transition: width 1s linear;
            }
        `;
        
        // Add or update the style element
        let styleElement = document.getElementById(`azuracast-styles-${this.containerId}`);
        if (!styleElement) {
            styleElement = document.createElement('style');
            styleElement.id = `azuracast-styles-${this.containerId}`;
            document.head.appendChild(styleElement);
        }
        styleElement.textContent = styles;
    }

    /**
     * Bind event handlers
     */
    bindEvents(container) {
        const playButton = container.querySelector('#play-button');
        const volumeSlider = container.querySelector('#volume-slider');
        const muteButton = container.querySelector('#mute-button');
        const streamSelect = container.querySelector('#stream-select');

        if (playButton) {
            playButton.addEventListener('click', () => this.togglePlay());
        }

        if (volumeSlider) {
            volumeSlider.addEventListener('input', (e) => this.setVolume(e.target.value));
        }

        if (muteButton) {
            muteButton.addEventListener('click', () => this.toggleMute());
        }

        if (streamSelect) {
            streamSelect.addEventListener('change', (e) => this.changeStream(e.target.value));
        }
    }

    /**
     * Load now playing data from the API
     */
    async loadNowPlayingData() {
        try {
            const response = await fetch(`/api/nowplaying/${this.config.station}`);
            const data = await response.json();
            this.updateDisplay(data);
        } catch (error) {
            console.error('Failed to load now playing data:', error);
        }
    }

    /**
     * Update the display with now playing data
     */
    updateDisplay(data) {
        const container = document.getElementById(this.containerId);
        if (!container) return;

        // Update song info
        const titleEl = container.querySelector('#song-title');
        const artistEl = container.querySelector('#song-artist');
        const albumArtEl = container.querySelector('#album-art');
        
        if (data.now_playing?.song) {
            if (titleEl) titleEl.textContent = data.now_playing.song.title || 'Unknown Title';
            if (artistEl) artistEl.textContent = data.now_playing.song.artist || 'Unknown Artist';
            
            if (albumArtEl && data.now_playing.song.art) {
                albumArtEl.src = data.now_playing.song.art;
                albumArtEl.style.display = 'block';
            }
        }

        // Update live/offline status
        const liveIndicator = container.querySelector('#live-indicator');
        const offlineIndicator = container.querySelector('#offline-indicator');
        const songInfo = container.querySelector('#song-info');

        if (data.live?.is_live) {
            if (liveIndicator) {
                liveIndicator.style.display = 'block';
                const streamerName = container.querySelector('#streamer-name');
                if (streamerName) streamerName.textContent = data.live.streamer_name || '';
            }
            if (songInfo) songInfo.style.display = 'block';
            if (offlineIndicator) offlineIndicator.style.display = 'none';
        } else if (!data.is_online) {
            if (offlineIndicator) offlineIndicator.style.display = 'block';
            if (liveIndicator) liveIndicator.style.display = 'none';
            if (songInfo) songInfo.style.display = 'none';
        } else {
            if (liveIndicator) liveIndicator.style.display = 'none';
            if (offlineIndicator) offlineIndicator.style.display = 'none';
            if (songInfo) songInfo.style.display = 'block';
        }

        // Update stream options
        this.updateStreamOptions(data.station);
    }

    /**
     * Update available stream options
     */
    updateStreamOptions(station) {
        const streamSelect = document.querySelector('#stream-select');
        if (!streamSelect || !this.config.showStreamSelection) return;

        // Clear existing options
        streamSelect.innerHTML = '<option value="">Select Stream...</option>';

        // Add HLS if available
        if (station.hls_enabled) {
            const option = document.createElement('option');
            option.value = station.hls_url;
            option.textContent = 'HLS';
            streamSelect.appendChild(option);
        }

        // Add mounts
        if (station.mounts) {
            station.mounts.forEach(mount => {
                const option = document.createElement('option');
                option.value = mount.url;
                option.textContent = mount.name || mount.url;
                streamSelect.appendChild(option);
            });
        }
    }

    /**
     * Toggle play/stop
     */
    togglePlay() {
        // Implementation would depend on your audio player setup
        console.log('Toggle play/stop');
        
        const playIcon = document.querySelector('.play-icon');
        const stopIcon = document.querySelector('.stop-icon');
        
        if (playIcon && stopIcon) {
            if (playIcon.style.display !== 'none') {
                playIcon.style.display = 'none';
                stopIcon.style.display = 'inline';
            } else {
                playIcon.style.display = 'inline';
                stopIcon.style.display = 'none';
            }
        }
    }

    /**
     * Set volume
     */
    setVolume(volume) {
        console.log('Set volume to:', volume);
        // Implementation would depend on your audio player setup
    }

    /**
     * Toggle mute
     */
    toggleMute() {
        console.log('Toggle mute');
        const muteButton = document.querySelector('#mute-button');
        if (muteButton) {
            muteButton.textContent = muteButton.textContent === '🔊' ? '🔇' : '🔊';
        }
    }

    /**
     * Change stream
     */
    changeStream(url) {
        console.log('Change stream to:', url);
        // Implementation would depend on your audio player setup
    }

    /**
     * Start polling for updates
     */
    startPolling() {
        // Update every 15 seconds
        setInterval(() => {
            this.loadNowPlayingData();
        }, 15000);
    }

    /**
     * Adjust color brightness
     */
    adjustColor(color, amount) {
        const usePound = color[0] === '#';
        const col = usePound ? color.slice(1) : color;
        const num = parseInt(col, 16);
        let r = (num >> 16) + amount;
        let g = (num >> 8 & 0x00FF) + amount;
        let b = (num & 0x0000FF) + amount;
        
        r = r > 255 ? 255 : r < 0 ? 0 : r;
        g = g > 255 ? 255 : g < 0 ? 0 : g;
        b = b > 255 ? 255 : b < 0 ? 0 : b;
        
        return (usePound ? '#' : '') + (r << 16 | g << 8 | b).toString(16).padStart(6, '0');
    }
}

// Global instance
window.AzuraCastEmbed = new AzuraCastEmbed();

// Auto-initialize if configuration is found
document.addEventListener('DOMContentLoaded', () => {
    // Look for auto-init configuration
    const autoConfig = document.querySelector('script[data-azuracast-config]');
    if (autoConfig) {
        try {
            const config = JSON.parse(autoConfig.dataset.azuracastConfig);
            window.AzuraCastEmbed.init(config);
        } catch (error) {
            console.error('Invalid AzuraCast configuration:', error);
        }
    }
});