<template>
    <b-tab :title="langTitle">
        <b-form-group>
            <b-form-row>
                <b-form-group class="col-md-6" label-for="media_file">
                    <template #label>
                        {{ $gettext('Select Media File') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('Podcast media should be in the MP3 or M4A (AAC) format for the greatest compatibility.')
                        }}
                    </template>

                    <flow-upload :target-url="targetUrl" :valid-mime-types="acceptMimeTypes"
                                 @success="onFileSuccess"></flow-upload>
                </b-form-group>

                <b-form-group class="col-md-6">
                    <template #label>
                        {{ $gettext('Current Podcast Media') }}
                    </template>

                    <div v-if="hasMedia">
                        <div class="buttons pt-3">
                            <b-button v-if="downloadUrl" block variant="bg" :href="downloadUrl" target="_blank">
                                {{ $gettext('Download') }}
                            </b-button>
                            <b-button block variant="danger" @click="deleteMedia">
                                {{ $gettext('Clear Media') }}
                            </b-button>
                        </div>
                    </div>
                    <div v-else>
                        {{ $gettext('There is no existing media associated with this episode.') }}
                    </div>
                </b-form-group>
            </b-form-row>
        </b-form-group>
    </b-tab>
</template>

<script>
import FlowUpload from '~/components/Common/FlowUpload';

export default {
    name: 'EpisodeFormMedia',
    components: {FlowUpload},
    props: {
        value: Object,
        recordHasMedia: Boolean,
        downloadUrl: String,
        editMediaUrl: String,
        newMediaUrl: String
    },
    data() {
        return {
            hasMedia: this.recordHasMedia,
            acceptMimeTypes: ['audio/x-m4a', 'audio/mpeg']
        };
    },


    watch: {
        recordHasMedia(newValue) {
            this.hasMedia = newValue;
        }
    },
    computed: {
        langTitle() {
            return this.$gettext('Media');
        },
        targetUrl() {
            return (this.editMediaUrl)
                ? this.editMediaUrl
                : this.newMediaUrl;
        }
    },
    methods: {
        onFileSuccess (file, message) {
            this.hasMedia = true;
            if (!this.editMediaUrl) {
                this.$emit('input', message);
            }
        },
        deleteMedia () {
            if (this.editMediaUrl) {
                this.axios.delete(this.editMediaUrl).then(() => {
                    this.hasMedia = false;
                });
            } else {
                this.hasMedia = false;
                this.$emit('input', null);
            }
        }
    }
};
</script>
