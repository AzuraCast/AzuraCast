<template>
    <b-modal size="lg" id="request_modal" ref="modal" :title="langTitle" hide-footer>
        <song-request :request-list-uri="requestListUri" @submitted="doClose"></song-request>
    </b-modal>
</template>

<script>
import SongRequest from '../SongRequest';

export default {
    components: { SongRequest },
    props: {
        requestListUri: {
            type: String,
            required: true
        }
    },
    data () {
        return {
            loading: true
        };
    },
    computed: {
        langTitle () {
            return this.$gettext('Request a Song');
        }
    },
    methods: {
        doClose () {
            this.$refs.modal.hide();
        }
    }
};
</script>
