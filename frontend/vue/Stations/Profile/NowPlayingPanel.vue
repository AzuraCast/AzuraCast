<template>
    <section class="card mb-4 nowplaying" role="region" id="profile-nowplaying">
        <div class="card-header bg-primary-dark">
            <div class="d-flex align-items-center">
                <h3 class="flex-shrink card-title my-0" key="lang_profile_nowplaying_onair" v-translate>On the Air</h3>
                <h6 class="card-subtitle text-right flex-fill my-0" style="line-height: 1;">
                    <icon class="sm align-middle" icon="headset"></icon>
                    {{ langListeners }}
                    <br>
                    <small>
                        <span>{{ np.listeners.unique }}</span>
                        <translate key="lang_profile_nowplaying_unique">Unique</translate>
                    </small>
                </h6>
            </div>
        </div>
        <b-overlay variant="card" :show="np.loading">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="clearfix">
                            <h6 style="margin-left: 32px;">
                                <icon icon="music_note"></icon>
                                <translate key="lang_profile_nowplaying_title">Now Playing</translate>
                            </h6>
                            <div class="media">
                                <a class="mr-2" v-if="np.now_playing.song.art" :href="np.now_playing.song.art" data-fancybox target="_blank">
                                    <img class="rounded" :src="np.now_playing.song.art" alt="Album Art" style="width: 50px;">
                                </a>
                                <div class="media-body">
                                    <div v-if="np.now_playing.song.title !== ''">
                                        <h5 class="media-heading m-0" style="line-height: 1;">
                                            {{ np.now_playing.song.title }}<br>
                                            <small>{{ np.now_playing.song.artist }}</small>
                                        </h5>
                                    </div>
                                    <div v-else>
                                        <h5 class="media-heading m-0" style="line-height: 1;">{{ np.now_playing.song.text }}</h5>
                                    </div>
                                    <div v-if="np.now_playing.playlist">
                                        <small class="text-muted"><translate key="lang_profile_nowplaying_playlist">Playlist</translate>: {{ np.now_playing.playlist }}</small>
                                    </div>
                                    <div class="nowplaying-progress" v-if="timeDisplay">
                                        <small>{{ timeDisplay }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="clearfix" v-if="!np.live.is_live && np.playing_next">
                            <h6 style="margin-left: 22px;">
                                <icon icon="skip_next"></icon>
                                <translate key="profile_nowplaying_playing_next">Playing Next</translate>
                            </h6>

                            <div class="media">
                                <a class="mr-2" v-if="np.playing_next.song.art" :href="np.playing_next.song.art" data-fancybox target="_blank">
                                    <img :src="np.playing_next.song.art" class="rounded" alt="Album Art" style="width: 40px;">
                                </a>
                                <div class="media-body">
                                    <div v-if="np.playing_next.song.title !== ''">
                                        <h5 class="media-heading m-0" style="line-height: 1;">
                                            {{ np.playing_next.song.title }}<br>
                                            <small>{{ np.playing_next.song.artist }}</small>
                                        </h5>
                                    </div>
                                    <div v-else>
                                        <h5 class="media-heading m-0" style="line-height: 1;">{{ np.playing_next.song.text }}</h5>
                                    </div>

                                    <div v-if="np.playing_next.playlist">
                                        <small class="text-muted"><translate key="lang_profile_nowplaying_playlist">Playlist</translate>: {{ np.playing_next.playlist }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="clearfix" v-else-if="np.live.is_live">
                            <h6 style="margin-left: 22px;">
                                <icon icon="mic"></icon>
                                <translate key="lang_profile_nowplaying_live">Live</translate>
                            </h6>

                            <h4 class="media-heading" style="margin-left: 22px;">
                                {{ np.live.streamer_name }}
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
        </b-overlay>

        <div class="card-actions flex-shrink" v-if="isLiquidsoap && userCanManageBroadcasting">
            <a id="btn_skip_song" class="btn btn-outline-primary api-call no-reload" role="button" v-if="!np.live.is_live" :href="backendSkipSongUri">
                <icon icon="skip_next"></icon>
                <translate key="lang_backend_skip">Skip Song</translate>
            </a>
            <a id="btn_disconnect_streamer" class="btn btn-outline-primary api-call no-reload" role="button" v-if="np.live.is_live" :href="backendDisconnectStreamerUri">
                <icon icon="volume_off"></icon>
                <translate key="lang_backend_disconnect">Disconnect Streamer</translate>
            </a>
        </div>
    </section>
</template>

<script>
import { BACKEND_LIQUIDSOAP } from '../../Entity/RadioAdapters.js';
import Icon from '../../Common/Icon';

export const profileNowPlayingProps = {
    props: {
        backendType: String,
        userCanManageBroadcasting: Boolean,
        backendSkipSongUri: String,
        backendDisconnectStreamerUri: String
    }
};

export default {
    components: { Icon },
    mixins: [profileNowPlayingProps],
    props: {
        np: Object
    },
    data () {
        return {
            npElapsed: 0,
            clockInterval: null
        };
    },
    mounted () {
        this.clockInterval = setInterval(this.iterateTimer, 1000);
    },
    computed: {
        langListeners () {
            let translated = this.$ngettext('%{listeners} Listener', '%{listeners} Listeners', this.np.listeners.total);
            return this.$gettextInterpolate(translated, { listeners: this.np.listeners.total });
        },
        isLiquidsoap () {
            return this.backendType === BACKEND_LIQUIDSOAP;
        },
        timeDisplay () {
            let time_played = this.npElapsed;
            let time_total = this.np.now_playing.duration;

            if (!time_total) {
                return null;
            }

            if (time_played > time_total) {
                time_played = time_total;
            }

            return this.formatTime(time_played) + ' / ' + this.formatTime(time_total);
        }
    },
    methods: {
        iterateTimer () {
            let current_time = Math.floor(Date.now() / 1000);
            let np_elapsed = current_time - this.np.now_playing.played_at;
            if (np_elapsed < 0) {
                np_elapsed = 0;
            } else if (np_elapsed >= this.np.now_playing.duration) {
                np_elapsed = this.np.now_playing.duration;
            }

            this.npElapsed = np_elapsed;
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
