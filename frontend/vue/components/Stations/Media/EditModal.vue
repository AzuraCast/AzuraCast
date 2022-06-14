<template>
    <modal-form ref="modal" :loading="loading" :title="langTitle" :error="error" :disable-save-button="$v.form.$invalid"
                @submit="doEdit" @hidden="clearContents">

        <b-tabs content-class="mt-3">
            <b-tab active>
                <template #title>
                    <translate key="tab_basic_info">Basic Information</translate>
                </template>

                <media-form-basic-info :form="$v.form"></media-form-basic-info>
            </b-tab>
            <b-tab>
                <template #title>
                    <translate key="tab_playlists">Playlists</translate>
                </template>

                <media-form-playlists :form="$v.form" :playlists="playlists"></media-form-playlists>
            </b-tab>
            <b-tab lazy>
                <template #title>
                    <translate key="tab_album_art">Album Art</translate>
                </template>

                <media-form-album-art :album-art-url="albumArtUrl"></media-form-album-art>
            </b-tab>

            <b-tab v-if="customFields.length > 0">
                <template #title>
                    <translate key="tab_custom_fields">Custom Fields</translate>
                </template>

                <media-form-custom-fields :form="$v.form" :custom-fields="customFields"></media-form-custom-fields>
            </b-tab>

            <b-tab lazy>
                <template #title>
                    <translate key="tab_waveform_editor">Visual Cue Editor</translate>
                </template>

                <media-form-waveform-editor :form="form" :audio-url="audioUrl"
                                            :waveform-url="waveformUrl"></media-form-waveform-editor>
            </b-tab>

            <b-tab>
                <template #title>
                    <translate key="tab_advanced">Advanced</translate>
                </template>

                <media-form-advanced-settings :form="$v.form" :song-length="songLength"></media-form-advanced-settings>
            </b-tab>
        </b-tabs>
    </modal-form>
</template>
<script>
import {validationMixin} from 'vuelidate';
import {required} from 'vuelidate/dist/validators.min.js';
import _ from 'lodash';
import MediaFormBasicInfo from './Form/BasicInfo';
import MediaFormAlbumArt from './Form/AlbumArt';
import MediaFormCustomFields from './Form/CustomFields';
import MediaFormAdvancedSettings from './Form/AdvancedSettings';
import MediaFormPlaylists from './Form/Playlists';
import MediaFormWaveformEditor from './Form/WaveformEditor';
import ModalForm from "~/components/Common/ModalForm";

export default {
    name: 'EditModal',
    components: {
        ModalForm,
        MediaFormPlaylists,
        MediaFormWaveformEditor,
        MediaFormAdvancedSettings,
        MediaFormCustomFields,
        MediaFormAlbumArt,
        MediaFormBasicInfo
    },
    mixins: [validationMixin],
    props: {
        customFields: Array,
        playlists: Array
    },
    data() {
        return {
            loading: true,
            recordUrl: null,
            error: null,
            albumArtUrl: null,
            waveformUrl: null,
            audioUrl: null,
            songLength: null,
            form: {}
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
                genre: {},
                lyrics: {},
                isrc: {},
                art: {},
                amplify: {},
                fade_overlap: {},
                fade_in: {},
                fade_out: {},
                cue_in: {},
                cue_out: {},
                playlists: {},
                custom_fields: {}
            }
        };

        _.forEach(this.customFields.slice(), (field) => {
            validations.form.custom_fields[field.short_name] = {};
        });

        return validations;
    },
    computed: {
        langTitle() {
            return this.$gettext('Edit Media');
        }
    },
    methods: {
        resetForm() {
            this.loading = false;
            this.error = null;

            this.albumArtUrl = null;
            this.waveformUrl = null;
            this.recordUrl = null;
            this.audioUrl = null;

            let customFields = {};
            _.forEach(this.customFields.slice(), (field) => {
                customFields[field.short_name] = null;
            });

            this.form = {
                path: null,
                title: null,
                artist: null,
                album: null,
                genre: null,
                lyrics: null,
                isrc: null,
                amplify: null,
                fade_overlap: null,
                fade_in: null,
                fade_out: null,
                cue_in: null,
                cue_out: null,
                playlists: [],
                custom_fields: customFields
            };
        },
        open(recordUrl, albumArtUrl, audioUrl, waveformUrl) {
            this.resetForm();

            this.loading = true;
            this.error = null;

            this.albumArtUrl = albumArtUrl;
            this.waveformUrl = waveformUrl;
            this.recordUrl = recordUrl;
            this.audioUrl = audioUrl;

            this.$refs.modal.show();

            this.axios.get(recordUrl).then((resp) => {
                let d = resp.data;

                this.songLength = d.length_text;
                this.form = {
                    path: d.path,
                    title: d.title,
                    artist: d.artist,
                    album: d.album,
                    genre: d.genre,
                    lyrics: d.lyrics,
                    isrc: d.isrc,
                    amplify: d.amplify,
                    fade_overlap: d.fade_overlap,
                    fade_in: d.fade_in,
                    fade_out: d.fade_out,
                    cue_in: d.cue_in,
                    cue_out: d.cue_out,
                    playlists: _.map(d.playlists, 'id'),
                    custom_fields: {}
                };

                _.forEach(this.customFields.slice(), (field) => {
                    this.form.custom_fields[field.short_name] = _.defaultTo(d.custom_fields[field.short_name], null);
                });

                this.loading = false;
            }).catch((error) => {
                this.close();
            });
        },
        close() {
            this.$refs.modal.hide();
        },
        clearContents() {
            this.resetForm();
            this.$v.form.$reset();
        },
        doEdit() {
            this.$v.form.$touch();
            if (this.$v.form.$anyError) {
                return;
            }

            this.error = null;

            this.axios.put(this.recordUrl, this.form).then((resp) => {
                this.$notifySuccess();
                this.$emit('relist');
                this.close();
            }).catch((error) => {
                this.error = error.response.data.message;
            });
        }
    }
};
</script>
