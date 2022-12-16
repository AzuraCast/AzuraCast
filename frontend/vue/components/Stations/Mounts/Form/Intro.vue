<template>
    <b-tab :title="langTitle">
        <b-form-group>
            <b-form-row>
                <b-form-group class="col-md-6" label-for="intro_file">
                    <template #label>
                        {{ $gettext('Select Intro File') }}
                    </template>
                    <template #description>
                        {{ $gettext('This introduction file should exactly match the bitrate and format of the mount point itself.') }}
                    </template>

                    <flow-upload :target-url="targetUrl" :valid-mime-types="acceptMimeTypes"
                                 @success="onFileSuccess"></flow-upload>
                </b-form-group>

                <b-form-group class="col-md-6">
                    <template #label>
                        {{ $gettext('Current Intro File') }}
                    </template>

                    <div v-if="hasIntro">
                        <div class="buttons pt-3">
                            <b-button v-if="editIntroUrl" block variant="bg" :href="editIntroUrl" target="_blank">
                                {{ $gettext('Download') }}
                            </b-button>
                            <b-button block variant="danger" @click="deleteIntro">
                                {{ $gettext('Clear File') }}
                            </b-button>
                        </div>
                    </div>
                    <div v-else>
                        {{ $gettext('There is no existing intro file associated with this mount point.') }}
                    </div>
                </b-form-group>
            </b-form-row>
        </b-form-group>
    </b-tab>
</template>

<script>
import FlowUpload from '~/components/Common/FlowUpload';

export default {
    name: 'MountFormIntro',
    components: {FlowUpload},
    props: {
        value: Object,
        recordHasIntro: Boolean,
        editIntroUrl: String,
        newIntroUrl: String
    },
    data() {
        return {
            hasIntro: this.recordHasIntro,
            acceptMimeTypes: ['audio/*']
        };
    },
    watch: {
        recordHasIntro(newValue) {
            this.hasIntro = newValue;
        }
    },
    computed: {
        langTitle() {
            return this.$gettext('Intro');
        },
        targetUrl() {
            return (this.editIntroUrl)
                ? this.editIntroUrl
                : this.newIntroUrl;
        }
    },
    methods: {
        onFileSuccess(file, message) {
            this.hasIntro = true;
            if (!this.editIntroUrl) {
                this.$emit('input', message);
            }
        },
        deleteIntro() {
            if (this.editIntroUrl) {
                this.axios.delete(this.editIntroUrl).then(() => {
                    this.hasIntro = false;
                });
            } else {
                this.hasIntro = false;
                this.$emit('input', null);
            }
        }
    }
};
</script>
