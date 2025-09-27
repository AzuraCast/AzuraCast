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
            <podcast-form-basic-info
                :categories-options="categoriesOptions"
                :language-options="languageOptions"
            />

            <podcast-form-source/>

            <podcast-common-artwork
                v-model="form.artwork_file"
                :artwork-src="record.links.art"
                :new-art-url="newArtUrl"
            />

            <podcast-form-branding/>
        </tabs>
    </modal-form>
</template>

<script setup lang="ts">
import PodcastFormBasicInfo from "~/components/Stations/Podcasts/PodcastForm/BasicInfo.vue";
import PodcastFormSource from "~/components/Stations/Podcasts/PodcastForm/Source.vue";
import PodcastFormBranding from "~/components/Stations/Podcasts/PodcastForm/Branding.vue";
import PodcastCommonArtwork from "~/components/Stations/Podcasts/Common/Artwork.vue";
import mergeExisting from "~/functions/mergeExisting";
import {BaseEditModalEmits, BaseEditModalProps, useBaseEditModal} from "~/functions/useBaseEditModal";
import {computed, toRef, useTemplateRef} from "vue";
import {useTranslate} from "~/vendor/gettext";
import ModalForm from "~/components/Common/ModalForm.vue";
import Tabs from "~/components/Common/Tabs.vue";
import {NestedFormOptionInput} from "~/functions/objectToFormOptions.ts";
import {storeToRefs} from "pinia";
import {useStationsPodcastsForm} from "~/components/Stations/Podcasts/PodcastForm/form.ts";
import {PodcastRecord, PodcastResponseBody} from "~/entities/Podcasts.ts";

interface PodcastEditModalProps extends BaseEditModalProps {
    languageOptions: NestedFormOptionInput,
    categoriesOptions: NestedFormOptionInput,
    newArtUrl: string
}

const props = defineProps<PodcastEditModalProps>();

const emit = defineEmits<BaseEditModalEmits>();

const $modal = useTemplateRef('$modal');

const formStore = useStationsPodcastsForm();
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
    PodcastRecord,
    PodcastResponseBody
>(
    toRef(props, 'createUrl'),
    emit,
    $modal,
    resetForm,
    (data) => {
        record.value = mergeExisting(record.value, data);

        r$.value.$reset({
            toState: mergeExisting(r$.value.$value, {
                ...data,
                categories: data.categories?.map((row) => row.category) ?? []
            })
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
        ? $gettext('Edit Podcast')
        : $gettext('Add Podcast');
});

defineExpose({
    create,
    edit,
    close
});
</script>
