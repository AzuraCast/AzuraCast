<template>
    <div id="song_history">
        <song-history
            :show-album-art="showAlbumArt"
            :history="history"
        />
    </div>
</template>

<script setup lang="ts">
import useNowPlaying from "~/functions/useNowPlaying";
import {computed, toRef} from "vue";
import SongHistory from "~/components/Public/FullPlayer/SongHistory.vue";
import {ApiNowPlayingVueProps} from "~/entities/ApiInterfaces.ts";

defineOptions({
    inheritAttrs: false
});

const props = withDefaults(
    defineProps<{
        nowPlayingProps: ApiNowPlayingVueProps,
        showAlbumArt?: boolean
    }>(),
    {
        showAlbumArt: true
    }
);

const {np} = useNowPlaying(toRef(props, 'nowPlayingProps'));

const history = computed(() => {
    return np.value.song_history ?? [];
});
</script>
