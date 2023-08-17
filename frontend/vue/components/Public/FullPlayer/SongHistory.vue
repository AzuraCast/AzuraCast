<template>
    <div id="station-history">
        <p v-if="history.length <= 0">
            {{ $gettext('No records to display.') }}
        </p>
        <div
            v-for="(row, index) in history"
            :key="row.sh_id"
            class="song"
        >
            <strong class="order">{{ history.length - index }}</strong>

            <album-art
                v-if="showAlbumArt"
                class="me-3"
                :src="row.song.art"
            />

            <div class="name">
                <strong>{{ row.song.title }}</strong>
                <span>
                    {{ albumAndArtist(row.song) }}
                </span>
            </div>
            <small class="date-played text-muted ms-3">
                {{ unixTimestampToDate(row.played_at) }}
            </small>
        </div>
    </div>
</template>

<script setup>
import AlbumArt from "~/components/Common/AlbumArt.vue";
import {useLuxon} from "~/vendor/luxon";

const props = defineProps({
    history: {
        type: Object,
        default: () => {
            return {};
        }
    },
    showAlbumArt: {
        type: Boolean,
        default: true
    }
});

const {timestampToRelative} = useLuxon();

const unixTimestampToDate = (timestamp) => (!timestamp)
    ? ''
    : timestampToRelative(timestamp);

const albumAndArtist = (song) => {
    return [song.artist, song.album].filter(str => !!str).join(' - ');
};
</script>

<style lang="scss">
#station-history {
    .song {
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
        width: 100%;
        line-height: normal;
        margin-bottom: 15px;

        &:last-child {
            margin-bottom: 0;
        }

        .order {
            display: flex;
            flex-direction: column;
            width: 35px;
            justify-content: center;
            margin-right: 5px;
            text-align: center;
        }

        a.album-art {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .name {
            display: flex;
            flex: 1;
            flex-direction: column;
            justify-content: center;
        }

        .date-played {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
    }
}
</style>
