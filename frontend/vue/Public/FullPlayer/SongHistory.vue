<template>
    <div id="station-history">
        <p v-if="history.length <= 0">{{ langNoRecords }}</p>
        <div class="song" v-for="(row, index) in history">
            <strong class="order">{{ history.length - index }}</strong>
            <img class="art" :src="row.song.art">
            <div class="name">
                <strong v-html="row.song.title"></strong>
                <span v-html="albumAndArtist(row.song)"></span>
            </div>
            <div class="break"></div>
            <small class="date-played text-muted">
                <span v-html="unixTimestampToDate(row.played_at)">{{ row.played_at }}</span>
            </small>
        </div>
    </div>
</template>

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

        .art {
            width: 40px;
            height: 40px;
            border-radius: 4px;
            margin-right: 5px;
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
            margin: 4px 0 0 40px;
        }

        .break {
            flex-basis: 100%;
            height: 0;
        }

        @media (min-width: 576px) {
            .date-played {
                margin-left: auto;
            }
            .break {
                display: none;
            }
        }
    }
}
</style>

<script>
export default {
    props: {
        history: Array
    },
    computed: {
        langNoRecords () {
            return this.$gettext('No records to display.');
        }
    },
    methods: {
        unixTimestampToDate (timestamp) {
            if (!timestamp) {
                return '';
            }
            const date = moment.unix(timestamp);
            if (moment().diff(date, 'days', true) > 0.5) {
                return date.calendar();
            }
            return date.fromNow();
        },
        albumAndArtist (song) {
            return [song.album, song.artist].filter(str => !!str).join(', ');
        }
    }
};
</script>
