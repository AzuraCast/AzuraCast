<template>
    <div>
        <div class="media align-items-center">
            <div class="pr-2" v-if="show_album_art && np.now_playing.song.art">
                <a v-bind:href="np.now_playing.song.art" data-fancybox target="_blank"><img v-bind:src="np.now_playing.song.art" id="album-art" :alt="$t('album_art_alt')"></a>
            </div>
            <div class="media-body" style="min-width: 0;">
                <div v-if="np.now_playing.song.title !== ''">
                    <h4 class="media-heading might-overflow m-0 nowplaying-title">
                        {{ np.now_playing.song.title }}
                    </h4>
                    <div class="nowplaying-artist might-overflow">
                        {{ np.now_playing.song.artist }}
                    </div>
                </div>
                <div v-else>
                    <h4 class="media-heading might-overflow nowplaying-title">
                        {{ np.now_playing.song.text }}
                    </h4>
                </div>

                <div class="d-flex flex-row align-items-center nowplaying-progress mt-1" v-if="time_display_played">
                    <div class="mr-2">
                        {{ time_display_played }}
                    </div>
                    <div class="flex-fill">
                        <div class="progress">
                            <div class="progress-bar bg-secondary" role="progressbar" v-bind:style="{ width: time_percent+'%' }"></div>
                        </div>
                    </div>
                    <div class="ml-2">
                        {{ time_display_total }}
                    </div>
                </div>
            </div>
        </div>

        <hr class="my-2">

        <div class="d-flex flex-row align-items-center">
            <div>
                <a href="javascript:;" id="main-play-btn" role="button" :title="$t('play_pause_btn')" @click.prevent="toggle()">
                    <i class="material-icons lg" style="line-height: 1" v-if="is_playing">pause_circle_filled</i>
                    <i class="material-icons lg" style="line-height: 1" v-else>play_circle_filled</i>
                </a>
            </div>
            <div class="flex-fill ml-1 nowplaying-progress">
                <div id="stream-selector" v-if="this.streams.length > 1" class="dropdown">
                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" id="btn-select-stream" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        {{ current_stream.name }}
                    </button>
                    <div class="dropdown-menu" aria-labelledby="btn-select-stream">
                        <a class="dropdown-item" v-for="stream in streams" href="javascript:;" @click="switchStream(stream)">
                            {{ stream.name }}
                        </a>
                    </div>
                </div>
            </div>

            <div class="flex-shrink-0">
                <a href="javascript:;" class="text-secondary" :title="$t('mute_btn')" @click.prevent="volume = 0">
                    <i class="material-icons" style="line-height: 1;" aria-hidden="true">volume_mute</i>
                </a>
            </div>
            <div class="flex-fill" style="max-width: 30%;">
                <input type="range" :title="$t('volume_slider')" class="custom-range" style="height: 10px;" id="jp-volume-range" min="0" max="100" step="1" v-model="volume">
            </div>
            <div class="flex-shrink-0">
                <a href="javascript:;" class="text-secondary" :title="$t('full_volume_btn')" @click.prevent="volume = 100">
                    <i class="material-icons" style="line-height: 1;" aria-hidden="true">volume_up</i>
                </a>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';
import store from 'store';

export default {
    props: {
        now_playing_uri: String,
        show_album_art: Boolean
    },
    data: function() {
        return {
            "np": {
                "station": {
                    "listen_url": '',
                    "mounts": [],
                    "remotes": []
                },
                "now_playing": {
                    "song": {
                        "title": "Song Title",
                        "artist": "Song Artist",
                        "art": "",
                    },
                    "is_request": false,
                    "elapsed": 0,
                    "duration": 0
                },
                "song_history": {},
            },
            "is_playing": false,
            "volume": 55,
            "current_stream": {
                "name": "",
                "url": ""
            },
            "audio": null,
            "np_timeout": null,
            "clock_interval": null,
        };
    },
    created: function() {
        this.audio = document.createElement('audio');
        this.clock_interval = setInterval(this.iterateTimer, 1000);

        // Handle audio errors.
        this.audio.onerror = (e) => {
            if (e.target.error.code === e.target.error.MEDIA_ERR_NETWORK && this.audio.src !== '') {
                console.log('Network interrupted stream. Automatically reconnecting shortly...');
                setTimeout(this.play, 5000);
            }
        };

        this.audio.onended = () => {
            if (this.is_playing) {
                this.stop();

                console.log('Network interrupted stream. Automatically reconnecting shortly...');
                setTimeout(this.play, 5000);
            } else {
                this.stop();
            }
        };

        // Check webstorage for existing volume preference.
        if (store.enabled && store.get('player_volume') !== undefined) {
            this.volume = store.get('player_volume', this.volume);
        }

        // Check the query string if browser supports easy query string access.
        if (typeof URLSearchParams !== 'undefined') {
            var urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('volume')) {
                this.volume = parseInt(urlParams.get('volume'));
            }
        }

        this.checkNowPlaying();
    },
    computed: {
        "streams": function() {
            let all_streams = [];
            this.np.station.mounts.forEach(function (mount) {
                all_streams.push({
                    "name": mount.name,
                    "url": mount.url
                });
            });
            this.np.station.remotes.forEach(function(remote) {
                all_streams.push({
                    "name": remote.name,
                    "url": remote.url
                })
            });
            return all_streams;
        },
        "time_percent": function() {
            let time_played = this.np.now_playing.elapsed;
            let time_total = this.np.now_playing.duration;

            if (!time_total) {
                return 0;
            }
            if (time_played > time_total) {
                return 100;
            }

            return (time_played / time_total) * 100;
        },
        "time_display_played": function() {
            let time_played = this.np.now_playing.elapsed;
            let time_total = this.np.now_playing.duration;

            if (!time_total) {
                return null;
            }

            if (time_played > time_total) {
                time_played = time_total;
            }

            return this.formatTime(time_played);
        },
        "time_display_total": function() {
            let time_total = this.np.now_playing.duration;
            return (time_total) ? this.formatTime(time_total) : null;
        }
    },
    watch: {
        "volume": function(volume) {
            this.audio.volume = Math.min((Math.exp(volume/100)-1)/(Math.E-1), 1);

            if (store.enabled) {
                store.set('player_volume', volume);
            }
        },
    },
    methods: {
        "play": function() {
            this.audio.src = this.current_stream.url;
            this.audio.play();

            this.is_playing = true;
        },
        "stop": function() {
            this.is_playing = false;

            this.audio.pause();
            this.audio.src = '';
        },
        "toggle": function() {
            if (this.is_playing) {
                this.stop();
            } else {
                this.play();
            }
        },
        "switchStream": function(new_stream) {
            this.current_stream = new_stream;
            this.play();
        },
        "checkNowPlaying": function() {
            axios.get(this.now_playing_uri).then((response) => {
                let np_new = response.data;

                this.np = np_new;

                // Set a "default" current stream if none exists.
                if (this.current_stream.url === '' && np_new.station.listen_url !== '' && this.streams.length > 0) {
                    let current_stream = null;

                    this.streams.forEach(function(stream) {
                        if (stream.url === np_new.station.listen_url) {
                            current_stream = stream;
                        }
                    });

                    this.current_stream = current_stream;
                }

                // Update the browser metadata for browsers that support it (i.e. Mobile Chrome)
                if ('mediaSession' in navigator) {
                    navigator.mediaSession.metadata = new MediaMetadata({
                        title: np_new.now_playing.song.title,
                        artist: np_new.now_playing.song.artist,
                        artwork: [
                            { src: np_new.now_playing.song.art }
                        ]
                    });
                }

                Vue.prototype.$eventHub.$emit('np_updated', np_new);
            }).catch((error) => {
                console.error(error);
            }).then(() => {
                clearTimeout(this.np_timeout);
                this.np_timeout = setTimeout(this.checkNowPlaying, 15000);
            });
        },
        "iterateTimer": function() {
            let np_elapsed = this.np.now_playing.elapsed;
            let np_total = this.np.now_playing.duration;

            if (np_elapsed < np_total) {
                this.np.now_playing.elapsed = np_elapsed + 1;
            }
        },
        "formatTime": function(time) {
            let sec_num = parseInt(time, 10);

            let hours = Math.floor(sec_num / 3600);
            let minutes = Math.floor((sec_num - (hours * 3600)) / 60);
            let seconds = sec_num - (hours * 3600) - (minutes * 60);

            if (hours < 10) {
                hours = "0" + hours;
            }
            if (minutes < 10) {
                minutes = "0" + minutes;
            }
            if (seconds < 10) {
                seconds = "0" + seconds;
            }
            return (hours !== "00" ? hours + ':' : "") + minutes + ':' + seconds;
        }
    }
}
</script>
