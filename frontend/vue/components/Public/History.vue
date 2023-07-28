<template>
    <div id="song_history">
        <song-history
            :show-album-art="showAlbumArt"
            :history="history"
        />
    </div>
</template>

<script setup>
import useNowPlaying, {nowPlayingProps} from '~/functions/useNowPlaying';
import {computed} from "vue";
import SongHistory from "~/components/Public/FullPlayer/SongHistory.vue";

const props = defineProps({
    ...nowPlayingProps,
    showAlbumArt: {
        type: Boolean,
        default: true
    },
});

const {np} = useNowPlaying(props);

const history = computed(() => {
    return np.value.song_history ?? {};
});
</script>
