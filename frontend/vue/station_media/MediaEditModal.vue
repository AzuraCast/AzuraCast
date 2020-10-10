<template>
    <b-modal size="lg" id="edit_modal" ref="modal" :title="langTitle" :busy="loading">
        <b-spinner v-if="loading">
        </b-spinner>
        <b-form class="form" v-else @submit.prevent="doEdit">
            <b-tabs content-class="mt-3">
                <media-form-basic-info :form="$v.form"></media-form-basic-info>
                <media-form-album-art :album-art-url="albumArtUrl"></media-form-album-art>
                <media-form-custom-fields v-if="customFields.length > 0" :form="$v.form" :custom-fields="customFields"></media-form-custom-fields>
                <media-form-waveform-editor :form="form" :audio-url="audioUrl" :waveform-url="waveformUrl"></media-form-waveform-editor>
                <media-form-advanced-settings :form="$v.form" :song-length="songLength"></media-form-advanced-settings>
            </b-tabs>
            <invisible-submit-button/>
        </b-form>
        <template v-slot:modal-footer>
            <b-button variant="default" @click="close">
                <translate key="lang_btn_close">Close</translate>
            </b-button>
            <b-button variant="primary" @click="doEdit" :disabled="$v.form.$invalid">
                <translate key="lang_btn_save">Save Changes</translate>
            </b-button>
        </template>
    </b-modal>
</template>
<script>
import { validationMixin } from 'vuelidate';
import axios from 'axios';
import required from 'vuelidate/src/validators/required';
import _ from 'lodash';
import MediaFormBasicInfo from './form/MediaFormBasicInfo';
import MediaFormAlbumArt from './form/MediaFormAlbumArt';
import MediaFormCustomFields from './form/MediaFormCustomFields';
import MediaFormAdvancedSettings from './form/MediaFormAdvancedSettings';
import InvisibleSubmitButton from '../components/InvisibleSubmitButton';
import MediaFormWaveformEditor from './form/MediaFormWaveformEditor';

export default {
    name: 'EditModal',
    components: {
        MediaFormWaveformEditor,
        MediaFormAdvancedSettings,
        MediaFormCustomFields,
        MediaFormAlbumArt,
        MediaFormBasicInfo,
        InvisibleSubmitButton
    },
    mixins: [validationMixin],
    props: {
        customFields: Array
    },
    data() {
        return {
            loading: true,
            recordUrl: null,
            albumArtUrl: null,
            waveformUrl: null,
            audioUrl: null,
            songLength: null,
            form: this.getBlankForm()
        };
    },
    validations() {
        let validations = {
            form: {
                path: {
                    required
                },
                title: {},
                artist: {},
                album: {},
                lyrics: {},
                isrc: {},
                art: {},
                amplify: {},
                fade_overlap: {},
                fade_in: {},
                fade_out: {},
                cue_in: {},
                cue_out: {},
                custom_fields: {}
            }
        };

        _.forEach(this.customFields.slice(), (field) => {
            validations.form.custom_fields[field.key] = {};
        });

        return validations;
    },
    computed: {
        langTitle() {
            return this.$gettext('Edit Media');
        }
    },
    methods: {
        getBlankForm() {
            let customFields = {};

            _.forEach(this.customFields.slice(), (field) => {
                customFields[field.key] = null;
            });

            return {
                path: null,
                title: null,
                artist: null,
                album: null,
                lyrics: null,
                isrc: null,
                amplify: null,
                fade_overlap: null,
                fade_in: null,
                fade_out: null,
                cue_in: null,
                cue_out: null,
                custom_fields: customFields
            };
        },
        open(recordUrl, albumArtUrl, audioUrl, waveformUrl) {
            this.loading = true;
            this.$refs.modal.show();

            this.albumArtUrl = albumArtUrl;
            this.waveformUrl = waveformUrl;
            this.recordUrl = recordUrl;
            this.audioUrl = audioUrl;

            axios.get(recordUrl).then((resp) => {
                let d = resp.data;

                this.songLength = d.length_text;
                this.form = {
                    path: d.path,
                    title: d.title,
                    artist: d.artist,
                    album: d.album,
                    lyrics: d.lyrics,
                    isrc: d.isrc,
                    amplify: d.amplify,
                    fade_overlap: d.fade_overlap,
                    fade_in: d.fade_in,
                    fade_out: d.fade_out,
                    cue_in: d.cue_in,
                    cue_out: d.cue_out,
                    custom_fields: {}
                };

                _.forEach(this.customFields.slice(), (field) => {
                    this.form.custom_fields[field.key] = _.defaultTo(d.custom_fields[field.key], null);
                });

                this.loading = false;
            }).catch((err) => {
                console.log(err);
                this.close();
            });
        },
        close() {
            this.loading = false;
            this.albumArtUrl = null;
            this.audioUrl = null;

            this.form = this.getBlankForm();

            this.$v.form.$reset();
            this.$refs.modal.hide();
        },
        doEdit() {
            this.$v.form.$touch();
            if (this.$v.form.$anyError) {
                return;
            }

            axios.put(this.recordUrl, this.form).then((resp) => {
                let notifyMessage = this.$gettext('Changes saved.');
                notify('<b>' + notifyMessage + '</b>', 'success', false);

                this.$emit('relist');
                this.close();
            }).catch((err) => {
                console.error(err);

                let notifyMessage = this.$gettext('An error occurred and your request could not be completed.');
                notify('<b>' + notifyMessage + '</b>', 'danger', false);

                this.$emit('relist');
                this.close();
            });
        }
    }
};
</script>
