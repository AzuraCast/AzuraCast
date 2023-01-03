<template>
    <modal-form
        ref="$modal"
        :loading="loading"
        :title="$gettext('Edit Media')"
        :error="error"
        :disable-save-button="v$.$invalid"
        @submit="doEdit"
        @hidden="resetForm"
    >
        <b-tabs
            content-class="mt-3"
            pills
        >
            <b-tab active>
                <template #title>
                    {{ $gettext('Basic Information') }}
                </template>

                <media-form-basic-info :form="v$" />
            </b-tab>
            <b-tab>
                <template #title>
                    {{ $gettext('Playlists') }}
                </template>

                <media-form-playlists
                    :form="v$"
                    :playlists="playlists"
                />
            </b-tab>
            <b-tab lazy>
                <template #title>
                    {{ $gettext('Album Art') }}
                </template>

                <media-form-album-art :album-art-url="albumArtUrl" />
            </b-tab>

            <b-tab v-if="customFields.length > 0">
                <template #title>
                    {{ $gettext('Custom Fields') }}
                </template>

                <media-form-custom-fields
                    :form="v$"
                    :custom-fields="customFields"
                />
            </b-tab>

            <b-tab lazy>
                <template #title>
                    {{ $gettext('Visual Cue Editor') }}
                </template>

                <media-form-waveform-editor
                    :form="form"
                    :audio-url="audioUrl"
                    :waveform-url="waveformUrl"
                />
            </b-tab>

            <b-tab>
                <template #title>
                    {{ $gettext('Advanced') }}
                </template>

                <media-form-advanced-settings
                    :form="v$"
                    :song-length="songLength"
                />
            </b-tab>
        </b-tabs>
    </modal-form>
</template>

<script setup>
import {required} from '@vuelidate/validators';
import {defaultTo, forEach, map} from 'lodash';
import MediaFormBasicInfo from './Form/BasicInfo';
import MediaFormAlbumArt from './Form/AlbumArt';
import MediaFormCustomFields from './Form/CustomFields';
import MediaFormAdvancedSettings from './Form/AdvancedSettings';
import MediaFormPlaylists from './Form/Playlists';
import MediaFormWaveformEditor from './Form/WaveformEditor';
import ModalForm from "~/components/Common/ModalForm";
import {ref} from "vue";
import {useVuelidateOnForm} from "~/functions/useVuelidateOnForm";
import {useAxios} from "~/vendor/axios";
import {useNotify} from "~/vendor/bootstrapVue";

const props = defineProps({
    customFields: {
        type: Array,
        required: true
    },
    playlists: {
        type: Array,
        required: true
    }
});

const emit = defineEmits(['relist']);

const loading = ref(true);
const error = ref(null);
const recordUrl = ref('');
const albumArtUrl = ref('');
const waveformUrl = ref('');
const audioUrl = ref('');
const songLength = ref(0);

const buildForm = () => {
    let blankForm = {
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
        custom_fields: {}
    };

    let validations = {
        path: {required},
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
    };

    forEach(props.customFields.slice(), (field) => {
        validations.custom_fields[field.short_name] = {};
        blankForm.custom_fields[field.short_name] = null;
    });

    return {blankForm, validations};
};

const {blankForm, validations} = buildForm();
const {form, resetForm: resetBaseForm, v$, ifValid} = useVuelidateOnForm(validations, blankForm);

const resetForm = () => {
    resetBaseForm();

    loading.value = false;
    error.value = null;

    albumArtUrl.value = '';
    waveformUrl.value = '';
    recordUrl.value = '';
    audioUrl.value = '';
};

const $modal = ref(); // BModal

const close = () => {
    $modal.value?.hide();
};

const {axios} = useAxios();

const open = (newRecordUrl, newAlbumArtUrl, newAudioUrl, newWaveformUrl) => {
    resetForm();

    loading.value = true;
    recordUrl.value = newRecordUrl;
    albumArtUrl.value = newAlbumArtUrl;
    audioUrl.value = newAudioUrl;
    waveformUrl.value = newWaveformUrl;

    $modal.value?.show();

    axios.get(newRecordUrl).then((resp) => {
        let d = resp.data;

        songLength.value = d.length_text;

        let newForm = {
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
            playlists: map(d.playlists, 'id'),
            custom_fields: {}
        };

        forEach(props.customFields.slice(), (field) => {
            newForm.custom_fields[field.short_name] = defaultTo(d.custom_fields[field.short_name], null);
        });

        form.value = newForm;
    }).catch(() => {
        close();
    }).finally(() => {
        loading.value = false;
    });
};

const {notifySuccess} = useNotify();

const doEdit = () => {
    ifValid(() => {
        error.value = null;

        axios.put(recordUrl.value, form.value).then(() => {
            notifySuccess();
            emit('relist');
            close();
        }).catch((error) => {
            error.value = error.response.data.message;
        });
    });
};

defineExpose({
    open
});
</script>
