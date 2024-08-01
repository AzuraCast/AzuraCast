<template>
    <modal-form
        ref="$modal"
        :loading="loading"
        :title="$gettext('Edit Media')"
        :error="error"
        :disable-save-button="v$.$invalid"
        @submit="doSubmit"
        @hidden="onClose"
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
                <media-form-album-art :album-art-url="record.links.art" />
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
                    :audio-url="record.links.play"
                    :waveform-url="record.links.waveform"
                    :waveform-cache-url="record.links.waveform_cache"
                />
            </tab>
            <tab :label="$gettext('Advanced')">
                <media-form-advanced-settings
                    :form="v$"
                    :song-length="record.length_text"
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
import Tabs from "~/components/Common/Tabs.vue";
import Tab from "~/components/Common/Tab.vue";
import {ModalFormTemplateRef, useBaseEditModal} from "~/functions/useBaseEditModal.ts";
import mergeExisting from "~/functions/mergeExisting.ts";
import {useResettableRef} from "~/functions/useResettableRef.ts";

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

const {record, reset} = useResettableRef({
    length_text: null,
    links: {
        art: null,
        waveform: null,
        waveform_cache: null,
        play: null
    }
});

const $modal = ref<ModalFormTemplateRef>(null);

const {
    loading,
    error,
    form,
    v$,
    clearContents,
    edit,
    doSubmit
} = useBaseEditModal(
    props,
    emit,
    $modal,
    () => {
        const validations = {
            path: {required},
            title: {},
            artist: {},
            album: {},
            genre: {},
            lyrics: {},
            isrc: {},
            art: {},
            custom_fields: {},
            extra_metadata: {
                liq_amplify: {},
                liq_cross_start_next: {},
                liq_fade_in: {},
                liq_fade_out: {},
                liq_cue_in: {},
                liq_cue_out: {}
            },
            playlists: {},
        };

        forEach(props.customFields.slice(), (field) => {
            validations.custom_fields[field.short_name] = {};
        });

        return validations;
    },
    () => {
        const blankForm = {
            path: null,
            title: null,
            artist: null,
            album: null,
            genre: null,
            lyrics: null,
            isrc: null,
            custom_fields: {},
            extra_metadata: {
                liq_amplify: null,
                liq_cross_start_next: null,
                liq_fade_in: null,
                liq_fade_out: null,
                liq_cue_in: null,
                liq_cue_out: null
            },
            playlists: [],
        };

        forEach(props.customFields.slice(), (field) => {
            blankForm.custom_fields[field.short_name] = null;
        });

        return blankForm;
    },
    {
        resetForm: (originalResetForm) => {
            originalResetForm();
            reset();
        },
        populateForm: (data, form) => {
            record.value = mergeExisting(record.value, data);

            const newForm = mergeExisting(form.value, data);
            newForm.playlists = map(data.playlists, 'id');
            newForm.custom_fields = {};

            forEach(props.customFields.slice(), (field) => {
                newForm.custom_fields[field.short_name] = defaultTo(
                    data.custom_fields[field.short_name],
                    null
                );
            });

            form.value = newForm;
        },
    }
);

const open = (editRecordUrl) => {
    edit(editRecordUrl);
};

const onClose = () => {
    clearContents();
}

defineExpose({
    open
});
</script>
