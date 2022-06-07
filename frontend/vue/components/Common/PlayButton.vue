<template>
    <a href="#" @click.prevent="toggle" :title="langTitle">
        <icon :class="iconClass" :icon="icon"></icon>
    </a>
</template>

<script>
import Icon from "./Icon";

import getUrlWithoutQuery from "~/functions/getUrlWithoutQuery";

import store from '~/store.js';

export default {
    name: 'PlayButton',
    components: {Icon},
    props: {
        url: String,
        isStream: {
            type: Boolean,
            default: false
        },
        isHls: {
            type: Boolean,
            default: false
        },
        iconClass: String
    },
    computed: {
        isPlaying() {
            return store.state.player.isPlaying;
        },
        current() {
            return store.state.player.current;
        },
        isThisPlaying() {
            if (!this.isPlaying) {
                return false;
            }

            let playingUrl = getUrlWithoutQuery(this.current.url);
            let thisUrl = getUrlWithoutQuery(this.url);
            return playingUrl === thisUrl;
        },
        langTitle() {
            return this.isThisPlaying
                ? this.$gettext('Stop')
                : this.$gettext('Play');
        },
        icon() {
            return this.isThisPlaying
                ? 'stop_circle'
                : 'play_circle';
        }
    },
    methods: {
        toggle() {
            store.commit('player/toggle', {
                url: this.url,
                isStream: this.isStream,
                isHls: this.isHls
            });
        }
    }
}
</script>
