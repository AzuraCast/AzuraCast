<template>
    <modal-form
        ref="$modal"
        :loading="loading"
        :title="langTitle"
        :error="error"
        :disable-save-button="v$.$invalid"
        @submit="doSubmit"
        @hidden="clearContents"
    >
        <tabs>
            <episode-form-basic-info
                v-model:form="form"
            />

            <episode-form-media
                v-if="podcastIsManual"
                v-model="form.media_file"
                :record-has-media="record.has_media"
                :new-media-url="newMediaUrl"
                :edit-media-url="record.links.media"
            />

            <podcast-common-artwork
                v-model="form.artwork_file"
                :artwork-src="record.links.art"
                :new-art-url="newArtUrl"
            />
        </tabs>
    </modal-form>
</template>

<script setup lang="ts">
import EpisodeFormBasicInfo from './EpisodeForm/BasicInfo.vue';
import PodcastCommonArtwork from './Common/Artwork.vue';
import EpisodeFormMedia from './EpisodeForm/Media.vue';
import {
    BaseEditModalEmits,
    BaseEditModalProps,
    ModalFormTemplateRef,
    useBaseEditModal
} from "~/functions/useBaseEditModal";
import {computed, ref} from "vue";
import {useResettableRef} from "~/functions/useResettableRef";
import mergeExisting from "~/functions/mergeExisting";
import {useTranslate} from "~/vendor/gettext";
import ModalForm from "~/components/Common/ModalForm.vue";
import Tabs from "~/components/Common/Tabs.vue";
import {ApiPodcast} from "~/entities/ApiInterfaces.ts";

interface EpisodeEditModalProps extends BaseEditModalProps {
    podcast: ApiPodcast
}

const props = defineProps<EpisodeEditModalProps>();

const emit = defineEmits<BaseEditModalEmits>();

const newArtUrl = computed(() => props.podcast.links.episode_new_art);
const newMediaUrl = computed(() => props.podcast.links.episode_new_media);
const podcastIsManual = computed(() => {
    return props.podcast.source == 'manual';
});

const $modal = ref<ModalFormTemplateRef>(null);

const {record, reset} = useResettableRef({
    has_custom_art: false,
    art: null,
    has_media: false,
    media: null,
    links: {
        art: null,
        media: null,
        download: null,
    }
});

const {
    loading,
    error,
    isEditMode,
    form,
    v$,
    clearContents,
    create,
    edit,
    doSubmit,
    close
} = useBaseEditModal(
    props,
    emit,
    $modal,
    {
        artwork_file: {},
        media_file: {}
    },
    {
        artwork_file: null,
        media_file: null
    },
    {
        resetForm: (originalResetForm) => {
            originalResetForm();
            reset();
        },
        populateForm: (data, formRef) => {
            record.value = mergeExisting(record.value, data as typeof record.value);
            formRef.value = mergeExisting(formRef.value, data);
        },
    },
);

const {$gettext} = useTranslate();

const langTitle = computed(() => {
    return isEditMode.value
        ? $gettext('Edit Episode')
        : $gettext('Add Episode');
});

defineExpose({
    create,
    edit,
    close
});
</script>
