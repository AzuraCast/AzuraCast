<template>
    <modal-form
        ref="$modal"
        :loading="loading"
        :title="$gettext('Edit Media')"
        :error="error"
        :disable-save-button="r$.$invalid"
        @submit="doSubmit"
        @hidden="onClose"
    >
        <tabs destroy-on-hide>
            <media-form-basic-info/>
            <media-form-playlists
                :playlists="playlists"
            />
            <tab :label="$gettext('Album Art')">
                <media-form-album-art :album-art-url="record.links.art" />
            </tab>
            <media-form-custom-fields
                v-if="customFields.length > 0"
                :custom-fields="customFields"
            />
            <tab :label="$gettext('Visual Cue Editor')">
                <media-form-waveform-editor
                    v-model:form="form"
                    :duration="record.length"
                    :audio-url="record.links.play"
                    :waveform-url="record.links.waveform"
                    :waveform-cache-url="record.links.waveform_cache"
                />
            </tab>
            <media-form-advanced-settings
                :song-length="record.length_text"
            />
        </tabs>
    </modal-form>
</template>

<script setup lang="ts">
import {forEach, map} from "es-toolkit/compat";
import MediaFormBasicInfo from "~/components/Stations/Media/Form/BasicInfo.vue";
import MediaFormAlbumArt from "~/components/Stations/Media/Form/AlbumArt.vue";
import MediaFormCustomFields from "~/components/Stations/Media/Form/CustomFields.vue";
import MediaFormAdvancedSettings from "~/components/Stations/Media/Form/AdvancedSettings.vue";
import MediaFormPlaylists from "~/components/Stations/Media/Form/Playlists.vue";
import MediaFormWaveformEditor from "~/components/Stations/Media/Form/WaveformEditor.vue";
import ModalForm from "~/components/Common/ModalForm.vue";
import {toRef, useTemplateRef} from "vue";
import Tabs from "~/components/Common/Tabs.vue";
import Tab from "~/components/Common/Tab.vue";
import {BaseEditModalEmits, BaseEditModalProps, useBaseEditModal} from "~/functions/useBaseEditModal.ts";
import mergeExisting from "~/functions/mergeExisting.ts";
import {useResettableRef} from "~/functions/useResettableRef.ts";
import {ApiStationMedia, CustomField} from "~/entities/ApiInterfaces.ts";
import {MediaInitialPlaylist} from "~/components/Stations/Media.vue";
import {storeToRefs} from "pinia";
import {customFieldsKey, useStationsMediaForm} from "~/components/Stations/Media/Form/form.ts";
import {provideLocal} from "@vueuse/core";

interface MediaEditModalProps extends BaseEditModalProps {
    customFields: Required<CustomField>[],
    playlists: MediaInitialPlaylist[]
}

const props = defineProps<MediaEditModalProps>();
const emit = defineEmits<BaseEditModalEmits>();

type MediaMeta = Required<Pick<
    ApiStationMedia,
    | 'length'
    | 'length_text'
    | 'links'
>>

const {record, reset} = useResettableRef<MediaMeta>({
    length: 0,
    length_text: '',
    links: {
        art: '',
        waveform: '',
        waveform_cache: '',
        play: ''
    }
});

const $modal = useTemplateRef('$modal');

provideLocal(customFieldsKey, toRef(props, 'customFields'));

const formStore = useStationsMediaForm();
const {form, r$} = storeToRefs(formStore);
const {$reset: resetForm} = formStore;

const {
    loading,
    error,
    clearContents,
    edit,
    doSubmit
} = useBaseEditModal(
    form,
    props,
    emit,
    $modal,
    () => {
        resetForm();
        reset();
    },
    async () => (await r$.value.$validate()).valid,
    {
        populateForm: (data, form) => {
            record.value = mergeExisting(record.value, data as typeof record.value);

            const newForm = mergeExisting(form.value, data);
            newForm.playlists = map(data.playlists, 'id');
            newForm.custom_fields = {};

            forEach(props.customFields.slice(), (field) => {
                newForm.custom_fields[field.short_name] =
                    (data.custom_fields && data.custom_fields[field.short_name])
                    ? data.custom_fields[field.short_name]
                    : null;
            });

            form.value = newForm;
        },
    }
);

const open = (editRecordUrl: string) => {
    void edit(editRecordUrl);
};

const onClose = () => {
    clearContents();
}

defineExpose({
    open
});
</script>
