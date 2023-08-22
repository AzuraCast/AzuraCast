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
                v-model="form.media_file"
                :record-has-media="record.has_media"
                :new-media-url="newMediaUrl"
                :edit-media-url="record.links.media"
                :download-url="record.links.download"
            />

            <podcast-common-artwork
                v-model="form.artwork_file"
                :artwork-src="record.art"
                :new-art-url="newArtUrl"
                :edit-art-url="record.links.art"
            />
        </tabs>
    </modal-form>
</template>

<script setup>
import EpisodeFormBasicInfo from './EpisodeForm/BasicInfo';
import PodcastCommonArtwork from './Common/Artwork';
import EpisodeFormMedia from './EpisodeForm/Media';
import {baseEditModalProps, useBaseEditModal} from "~/functions/useBaseEditModal";
import {computed, ref} from "vue";
import {useResettableRef} from "~/functions/useResettableRef";
import mergeExisting from "~/functions/mergeExisting";
import {useTranslate} from "~/vendor/gettext";
import ModalForm from "~/components/Common/ModalForm.vue";
import {useLuxon} from "~/vendor/luxon";
import Tabs from "~/components/Common/Tabs.vue";

const props = defineProps({
    ...baseEditModalProps,
    podcastId: {
        type: String,
        required: true
    },
    newArtUrl: {
        type: String,
        required: true
    },
    newMediaUrl: {
        type: String,
        required: true
    }
});

const emit = defineEmits(['relist']);

const $modal = ref(); // Template Ref

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

const {DateTime} = useLuxon();

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
    {},
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
            let publishDate = '';
            let publishTime = '';

            if (data.publish_at !== null) {
                const publishDateTime = DateTime.fromSeconds(data.publish_at);
                publishDate = publishDateTime.toISODate();
                publishTime = publishDateTime.toISOTime({
                    suppressMilliseconds: true,
                    includeOffset: false
                });
            }

            record.value = mergeExisting(record.value, data);
            formRef.value = mergeExisting(formRef.value, {
                ...data,
                publish_date: publishDate,
                publish_time: publishTime
            });
        },
        getSubmittableFormData: (formRef) => {
            const modifiedForm = formRef.value;

            if (modifiedForm.publish_date.length > 0 && modifiedForm.publish_time.length > 0) {
                const publishDateTimeString = modifiedForm.publish_date + 'T' + modifiedForm.publish_time;
                const publishDateTime = DateTime.fromISO(publishDateTimeString);

                modifiedForm.publish_at = publishDateTime.toSeconds();
            }

            return modifiedForm;
        }
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
