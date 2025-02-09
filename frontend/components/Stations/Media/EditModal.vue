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
                    v-model:form="form"
                    :duration="record.length"
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
import {required} from "@vuelidate/validators";
import {defaultTo, forEach, map} from "lodash";
import MediaFormBasicInfo from "~/components/Stations/Media/Form/BasicInfo.vue";
import MediaFormAlbumArt from "~/components/Stations/Media/Form/AlbumArt.vue";
import MediaFormCustomFields from "~/components/Stations/Media/Form/CustomFields.vue";
import MediaFormAdvancedSettings from "~/components/Stations/Media/Form/AdvancedSettings.vue";
import MediaFormPlaylists from "~/components/Stations/Media/Form/Playlists.vue";
import MediaFormWaveformEditor from "~/components/Stations/Media/Form/WaveformEditor.vue";
import ModalForm from "~/components/Common/ModalForm.vue";
import {useTemplateRef} from "vue";
import Tabs from "~/components/Common/Tabs.vue";
import Tab from "~/components/Common/Tab.vue";
import {BaseEditModalEmits, BaseEditModalProps, useBaseEditModal} from "~/functions/useBaseEditModal.ts";
import mergeExisting from "~/functions/mergeExisting.ts";
import {useResettableRef} from "~/functions/useResettableRef.ts";
import {ApiStationMedia, CustomField} from "~/entities/ApiInterfaces.ts";
import {MediaInitialPlaylist} from "~/components/Stations/Media.vue";

interface MediaEditModalProps extends BaseEditModalProps {
    customFields: CustomField[],
    playlists: MediaInitialPlaylist[]
}

const props = defineProps<MediaEditModalProps>();
const emit = defineEmits<BaseEditModalEmits>();

const {record, reset} = useResettableRef({
    length: null,
    length_text: null,
    links: {
        art: null,
        waveform: null,
        waveform_cache: null,
        play: null
    }
});

const $modal = useTemplateRef('$modal');

type StationMedia = Omit<ApiStationMedia, 'custom_fields' | 'extra_metadata'> & {
    custom_fields: Record<string, any>,
    extra_metadata?: {
        amplify?: number,
        cross_start_next?: number,
        fade_in?: number,
        fade_out?: number,
        cue_in?: number,
        cue_out?: number
    }
}

const {
    loading,
    error,
    form,
    v$,
    clearContents,
    edit,
    doSubmit
} = useBaseEditModal<StationMedia>(
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
                amplify: {},
                cross_start_next: {},
                fade_in: {},
                fade_out: {},
                cue_in: {},
                cue_out: {}
            },
            playlists: {},
        };

        forEach(props.customFields.slice(), (field: CustomField) => {
            if (field.short_name) {
                validations.custom_fields[field.short_name] = {};
            }
        });

        return validations;
    },
    () => {
        const blankForm: StationMedia = {
            path: null,
            title: null,
            artist: null,
            album: null,
            genre: null,
            lyrics: null,
            isrc: null,
            custom_fields: {},
            extra_metadata: {
                amplify: null,
                cross_start_next: null,
                fade_in: null,
                fade_out: null,
                cue_in: null,
                cue_out: null
            },
            playlists: [],
        };

        forEach(props.customFields.slice(), (field: CustomField) => {
            if (field.short_name) {
                blankForm.custom_fields[field.short_name] = null;
            }
        });

        return blankForm;
    },
    {
        resetForm: (originalResetForm) => {
            originalResetForm();
            reset();
        },
        populateForm: (data, form) => {
            record.value = mergeExisting(record.value, data as typeof record.value);

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

const open = (editRecordUrl: string) => {
    edit(editRecordUrl);
};

const onClose = () => {
    clearContents();
}

defineExpose({
    open
});
</script>
