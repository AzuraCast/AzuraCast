<template>
    <div id="song_history">
        <song-history
            :show-album-art="showAlbumArt"
            :history="history"
        />
    </div>
</template>

<script lang="ts">
import {NowPlayingProps} from "~/functions/useNowPlaying.ts";

export interface HistoryProps extends NowPlayingProps {
    showAlbumArt?: boolean
}
</script>

<script setup lang="ts">
import useNowPlaying from '~/functions/useNowPlaying';
import {computed} from "vue";
import SongHistory from "~/components/Public/FullPlayer/SongHistory.vue";

const props = withDefaults(
    defineProps<HistoryProps>(),
    {
        showAlbumArt: true
    }
);

const {np} = useNowPlaying(props);

const history = computed(() => {
    return np.value.song_history ?? [];
});
</script>
