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
        <tabs destroy-on-hide>
            <tab :label="$gettext('Basic Information')">
                <media-form-basic-info :form="v$" />
            </tab>
            <tab :label="$gettext('Playlists')">
                <media-form-playlists
                    :form="v$"
                    :playlists="playlists"
                />
            </tab>
            <tab :label="$gettext('Album Art')">
                <media-form-album-art :album-art-url="albumArtUrl" />
            </tab>
            <tab
                v-if="customFields.length > 0"
                :label="$gettext('Custom Fields')"
            >
                <media-form-custom-fields
                    :form="v$"
                    :custom-fields="customFields"
                />
            </tab>
            <tab :label="$gettext('Visual Cue Editor')">
                <media-form-waveform-editor
                    :form="form"
                    :audio-url="audioUrl"
                    :waveform-url="waveformUrl"
                />
            </tab>
            <tab :label="$gettext('Advanced')">
                <media-form-advanced-settings
                    :form="v$"
                    :song-length="songLength"
                />
            </tab>
        </tabs>
    </modal-form>
</template>

<script setup lang="ts">
import {required} from '@vuelidate/validators';
import {defaultTo, forEach, map} from 'lodash';
import MediaFormBasicInfo from './Form/BasicInfo.vue';
import MediaFormAlbumArt from './Form/AlbumArt.vue';
import MediaFormCustomFields from './Form/CustomFields.vue';
import MediaFormAdvancedSettings from './Form/AdvancedSettings.vue';
import MediaFormPlaylists from './Form/Playlists.vue';
import MediaFormWaveformEditor from './Form/WaveformEditor.vue';
import ModalForm from "~/components/Common/ModalForm.vue";
import {ref} from "vue";
import {useVuelidateOnForm} from "~/functions/useVuelidateOnForm";
import {useAxios} from "~/vendor/axios";
import {useNotify} from "~/functions/useNotify";
import Tabs from "~/components/Common/Tabs.vue";
import Tab from "~/components/Common/Tab.vue";
import {ModalFormTemplateRef} from "~/functions/useBaseEditModal.ts";
import {useHasModal} from "~/functions/useHasModal.ts";

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
    const blankForm = {
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

    const validations = {
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

const $modal = ref<ModalFormTemplateRef>(null);
const {hide, show} = useHasModal($modal);

const {axios} = useAxios();

const open = (newRecordUrl, newAlbumArtUrl, newAudioUrl, newWaveformUrl) => {
    resetForm();

    loading.value = true;
    recordUrl.value = newRecordUrl;
    albumArtUrl.value = newAlbumArtUrl;
    audioUrl.value = newAudioUrl;
    waveformUrl.value = newWaveformUrl;

    show();

    axios.get(newRecordUrl).then((resp) => {
        const d = resp.data;

        songLength.value = d.length_text;

        const newForm = {
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
        hide();
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
            hide();
        }).catch((error) => {
            error.value = error.response.data.message;
        });
    });
};

defineExpose({
    open
});
</script>
