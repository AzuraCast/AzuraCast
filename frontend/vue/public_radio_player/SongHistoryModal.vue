<template>
    <b-modal size="md" id="song_history_modal" ref="modal" :title="langTitle" centered hide-footer>
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
    </b-modal>
</template>

<script>
export default {
    data () {
        return {
            history: []
        };
    },
    computed: {
        langTitle () {
            return this.$gettext('Song History');
        },
        langNoRecords () {
            return this.$gettext('No records to display.');
        }
    },
    methods: {
        updateHistory (newHistory) {
            this.history = newHistory.song_history;
        },
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
