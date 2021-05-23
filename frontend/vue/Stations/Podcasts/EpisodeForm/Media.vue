<template>
    <b-tab :title="langTitle">
        <b-form-group>
            <b-row>
                <b-form-group class="col-md-6" label-for="media_file">
                    <template #label>
                        <translate key="media_file">Select Media File</translate>
                    </template>
                    <template #description>
                        <translate key="media_file_desc">Podcast media should be in the MP3 or M4A (AAC) format for the greatest compatibility.</translate>
                    </template>
                    <b-form-file id="media_file" accept="audio/x-m4a, audio/mpeg" v-model="form.media_file.$model"></b-form-file>
                </b-form-group>

                <b-form-group class="col-md-6">
                    <template #label>
                        <translate key="existing_media">Current Podcast Media</translate>
                    </template>

                    <div v-if="media">
                        <p>
                            {{ media.original_name }}<br>
                            <small>{{ media.length_text }}</small>
                        </p>

                        <div class="buttons">
                            <b-button :href="downloadUrl" target="_blank" variant="bg">
                                <translate key="btn_download">Download</translate>
                            </b-button>
                        </div>
                    </div>
                    <div v-else>
                        <translate key="no_existing_media">There is no existing media associated with this episode.</translate>
                    </div>
                </b-form-group>
            </b-row>
        </b-form-group>
    </b-tab>
</template>

<script>
export default {
    name: 'EpisodeFormMedia',
    props: {
        form: Object,
        media: Object,
        downloadUrl: String
    },
    computed: {
        langTitle () {
            return this.$gettext('Media');
        }
    }
};
</script>
