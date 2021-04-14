<template>
    <div class="radio-player-widget">
        <now-playing v-bind="$props" @np_updated="setNowPlaying"></now-playing>
        <audio-player ref="player" v-bind:title="np.now_playing.song.text"></audio-player>

        <div class="now-playing-details">
            <div class="now-playing-art" v-if="showAlbumArt && np.now_playing.song.art">
                <a v-bind:href="np.now_playing.song.art" data-fancybox target="_blank">
                    <img v-bind:src="np.now_playing.song.art" :alt="lang_album_art_alt">
                </a>
            </div>
            <div class="now-playing-main">
                <h6 class="now-playing-live" v-if="np.live.is_live">
                    <translate key="lang_live" class="badge badge-primary">Live</translate>
                    {{ np.live.streamer_name }}
                </h6>

                <div v-if="np.now_playing.song.title !== ''">
                    <h4 class="now-playing-title">{{ np.now_playing.song.title }}</h4>
                    <h5 class="now-playing-artist">{{ np.now_playing.song.artist }}</h5>
                </div>
                <div v-else>
                    <h4 class="now-playing-title">{{ np.now_playing.song.text }}</h4>
                </div>

                <div class="time-display" v-if="time_display_played">
                    <div class="time-display-played text-secondary">
                        {{ time_display_played }}
                    </div>
                    <div class="time-display-progress">
                        <div class="progress">
                            <div class="progress-bar bg-secondary" role="progressbar"
                                 v-bind:style="{ width: time_percent+'%' }"></div>
                        </div>
                    </div>
                    <div class="time-display-total text-secondary">
                        {{ time_display_total }}
                    </div>
                </div>
            </div>
        </div>

        <hr>

        <div class="radio-controls">
            <div class="radio-control-play-button" v-if="is_playing">
                <a href="#" role="button" :title="lang_stop_btn" :aria-label="lang_stop_btn"
                   @click.prevent="toggle()">
                    <icon class="outlined lg" icon="stop_circle"></icon>
                </a>
            </div>
            <div class="radio-control-play-button" v-else>
                <a href="#" role="button" :title="lang_play_btn" :aria-label="lang_play_btn"
                   @click.prevent="toggle()">
                    <icon class="outlined lg" icon="play_circle"></icon>
                </a>
            </div>

            <div class="radio-control-select-stream">
                <div v-if="this.streams.length > 1" class="dropdown">
                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" id="btn-select-stream"
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        {{ current_stream.name }}
                    </button>
                    <div class="dropdown-menu" aria-labelledby="btn-select-stream">
                        <a class="dropdown-item" v-for="stream in streams" href="javascript:"
                           @click="switchStream(stream)">
                            {{ stream.name }}
                        </a>
                    </div>
                </div>
            </div>

            <div class="radio-control-mute-button">
                <a href="#" class="text-secondary" :title="lang_mute_btn" @click.prevent="volume = 0">
                    <icon icon="volume_mute"></icon>
                </a>
            </div>
            <div class="radio-control-volume-slider">
                <input type="range" :title="lang_volume_slider" class="custom-range" min="0" max="100" step="1"
                       v-model="volume">
            </div>
            <div class="radio-control-max-volume-button">
                <a href="#" class="text-secondary" :title="lang_full_volume_btn" @click.prevent="volume = 100">
                    <icon icon="volume_up"></icon>
                </a>
            </div>
        </div>
    </div>
</template>

<style lang="scss">
.radio-player-widget {
    .now-playing-details {
        display: flex;
        align-items: center;

        .now-playing-art {
            padding-right: .5rem;

            img {
                width: 75px;
                height: auto;
                border-radius: 5px;

                @media (max-width: 575px) {
                    width: 50px;
                }
            }
        }

        .now-playing-main {
            flex: 1;
            min-width: 0;
        }

        h4, h5, h6 {
            margin: 0;
            line-height: 1.3;
        }

        h4 {
            font-size: 15px;
        }

        h5 {
            font-size: 13px;
            font-weight: normal;
        }

        h6 {
            font-size: 11px;
            font-weight: normal;
        }

        .now-playing-title,
        .now-playing-artist {
            text-overflow: ellipsis;
            overflow: hidden;
            white-space: nowrap;

            &:hover {
                text-overflow: clip;
                white-space: normal;
                word-break: break-all;
            }
        }

        .time-display {
            font-size: 10px;
            margin-top: .25rem;
            flex-direction: row;
            align-items: center;
            display: flex;

            .time-display-played {
                margin-right: .5rem;
            }

            .time-display-progress {
                flex: 1 1 auto;

                .progress-bar {
                    -webkit-transition: width 1s; /* Safari */
                    transition: width 1s;
                    transition-timing-function: linear;
                }
            }

            .time-display-total {
                margin-left: .5rem;
            }
        }
    }

    hr {
        margin-top: .5rem;
        margin-bottom: .5rem;
    }

    i.material-icons {
        line-height: 1;
    }

    .radio-controls {
        display: flex;
        flex-direction: row;
        align-items: center;

        .radio-control-play-button {
            margin-right: .25rem;
        }

        .radio-control-select-stream {
            flex: 1 1 auto;
        }

        .radio-control-mute-button,
        .radio-control-max-volume-button {
            flex-shrink: 0;
        }

        .radio-control-volume-slider {
            flex: 1 1 auto;
            max-width: 30%;

            input {
                height: 10px;
            }
        }
    }
}
</style>

<script>

import AudioPlayer from '../Common/AudioPlayer';
import NowPlaying, { nowPlayingProps } from '../Common/NowPlaying';
import Icon from '../Common/Icon';

export const radioPlayerProps = {
    ...nowPlayingProps,
    props: {
        nowPlayingUri: {
            type: String,
            required: true
        },
        initialNowPlaying: {
            type: Object,
            default () {
                return NowPlaying;
            }
        },
        useNchan: {
            type: Boolean,
            default: true
        },
        showAlbumArt: {
            type: Boolean,
            default: true
        },
        autoplay: {
            type: Boolean,
            default: false
        }
    }
};

export default {
    components: { Icon, NowPlaying, AudioPlayer },
    mixins: [radioPlayerProps],
    data () {
        return {
            'is_mounted': false,
            'np': this.initialNowPlaying,
            'np_elapsed': 0,
            'current_stream': {
                'name': '',
                'url': ''
            },
            'nchan_subscriber': null,
            'clock_interval': null
        };
    },
    mounted () {
        this.is_mounted = true;
        this.clock_interval = setInterval(this.iterateTimer, 1000);

        if (this.autoplay) {
            this.play();
        }
    },
    computed: {
        lang_play_btn () {
            return this.$gettext('Play');
        },
        lang_stop_btn () {
            return this.$gettext('Stop');
        },
        lang_mute_btn () {
            return this.$gettext('Mute');
        },
        lang_volume_slider () {
            return this.$gettext('Volume');
        },
        lang_full_volume_btn () {
            return this.$gettext('Full Volume');
        },
        lang_album_art_alt () {
            return this.$gettext('Album Art');
        },
        streams () {
            let all_streams = [];
            this.np.station.mounts.forEach(function (mount) {
                all_streams.push({
                    'name': mount.name,
                    'url': mount.url
                });
            });
            this.np.station.remotes.forEach(function (remote) {
                all_streams.push({
                    'name': remote.name,
                    'url': remote.url
                });
            });
            return all_streams;
        },
        time_percent () {
            let time_played = this.np_elapsed;
            let time_total = this.np.now_playing.duration;

            if (!time_total) {
                return 0;
            }
            if (time_played > time_total) {
                return 100;
            }

            return (time_played / time_total) * 100;
        },
        time_display_played () {
            let time_played = this.np_elapsed;
            let time_total = this.np.now_playing.duration;

            if (!time_total) {
                return null;
            }

            if (time_played > time_total) {
                time_played = time_total;
            }

            return this.formatTime(time_played);
        },
        time_display_total () {
            let time_total = this.np.now_playing.duration;
            return (time_total) ? this.formatTime(time_total) : null;
        },
        is_playing () {
            if (!this.is_mounted) {
                return;
            }

            return this.$refs.player.isPlaying();
        },
        volume: {
            get () {
                if (!this.is_mounted) {
                    return;
                }

                return this.$refs.player.getVolume();
            },
            set (vol) {
                this.$refs.player.setVolume(vol);
            }
        }
    },
    methods: {
        play () {
            this.$refs.player.play(this.current_stream.url);
        },
        stop () {
            this.$refs.player.stop();
        },
        toggle () {
            this.$refs.player.toggle(this.current_stream.url);
        },
        switchStream (new_stream) {
            this.current_stream = new_stream;
            this.play();
        },
        setNowPlaying (np_new) {
            this.np = np_new;
            this.$emit('np_updated', np_new);

            // Set a "default" current stream if none exists.
            if (this.current_stream.url === '' && this.streams.length > 0) {
                let current_stream = null;

                if (np_new.station.listen_url !== '') {
                    this.streams.forEach(function (stream) {
                        if (stream.url === np_new.station.listen_url) {
                            current_stream = stream;
                        }
                    });
                }

                if (current_stream === null) {
                    current_stream = this.streams[0];
                }

                this.current_stream = current_stream;
            }
        },
        iterateTimer () {
            let current_time = Math.floor(Date.now() / 1000);
            let np_elapsed = current_time - this.np.now_playing.played_at;
            if (np_elapsed < 0) {
                np_elapsed = 0;
            } else if (np_elapsed >= this.np.now_playing.duration) {
                np_elapsed = this.np.now_playing.duration;
            }
            this.np_elapsed = np_elapsed;
        },
        formatTime (time) {
            let sec_num = parseInt(time, 10);

            let hours = Math.floor(sec_num / 3600);
            let minutes = Math.floor((sec_num - (hours * 3600)) / 60);
            let seconds = sec_num - (hours * 3600) - (minutes * 60);

            if (hours < 10) {
                hours = '0' + hours;
            }
            if (minutes < 10) {
                minutes = '0' + minutes;
            }
            if (seconds < 10) {
                seconds = '0' + seconds;
            }
            return (hours !== '00' ? hours + ':' : '') + minutes + ':' + seconds;
        }
    }
};
</script>
