<template>
    <modal-form
        ref="$modal"
        :loading="loading"
        :title="langTitle"
        :error="error"
        :disable-save-button="r$.$invalid"
        @submit="doSubmit"
        @hidden="clearContents"
    >
        <tabs>
            <episode-form-basic-info/>

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
import EpisodeFormBasicInfo from "~/components/Stations/Podcasts/EpisodeForm/BasicInfo.vue";
import PodcastCommonArtwork from "~/components/Stations/Podcasts/Common/Artwork.vue";
import EpisodeFormMedia from "~/components/Stations/Podcasts/EpisodeForm/Media.vue";
import {BaseEditModalEmits, BaseEditModalProps, useBaseEditModal} from "~/functions/useBaseEditModal";
import {computed, toRef, useTemplateRef} from "vue";
import mergeExisting from "~/functions/mergeExisting";
import {useTranslate} from "~/vendor/gettext";
import ModalForm from "~/components/Common/ModalForm.vue";
import Tabs from "~/components/Common/Tabs.vue";
import {ApiPodcast} from "~/entities/ApiInterfaces.ts";
import {storeToRefs} from "pinia";
import {useStationsPodcastEpisodesForm} from "~/components/Stations/Podcasts/EpisodeForm/form.ts";
import {PodcastEpisodeRecord, PodcastEpisodeResponseBody} from "~/entities/Podcasts.ts";

interface EpisodeEditModalProps extends BaseEditModalProps {
    podcast: Required<ApiPodcast>
}

const props = defineProps<EpisodeEditModalProps>();

const emit = defineEmits<BaseEditModalEmits>();

const newArtUrl = computed(() => props.podcast.links.episode_new_art);
const newMediaUrl = computed(() => props.podcast.links.episode_new_media);
const podcastIsManual = computed(() => {
    return props.podcast.source == 'manual';
});

const $modal = useTemplateRef('$modal');

const formStore = useStationsPodcastEpisodesForm();
const {form, record, r$} = storeToRefs(formStore);
const {$reset: resetForm} = formStore;

const {
    loading,
    error,
    isEditMode,
    clearContents,
    create,
    edit,
    doSubmit,
    close
} = useBaseEditModal<
    PodcastEpisodeRecord,
    PodcastEpisodeResponseBody
>(
    toRef(props, 'createUrl'),
    emit,
    $modal,
    resetForm,
    (data) => {
        record.value = mergeExisting(record.value, data);

        r$.value.$reset({
            toState: mergeExisting(r$.value.$value, data)
        })
    },
    async () => {
        const {valid} = await r$.value.$validate();
        return {valid, data: form.value};
    }
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
