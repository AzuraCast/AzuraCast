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
            <podcast-form-basic-info
                v-model:form="form"
                :categories-options="categoriesOptions"
                :language-options="languageOptions"
            />

            <podcast-form-source
                v-model:form="form"
            />

            <podcast-common-artwork
                v-model="form.artwork_file"
                :artwork-src="record.links.art"
                :new-art-url="newArtUrl"
            />

            <podcast-form-branding
                v-model:form="form"
            />
        </tabs>
    </modal-form>
</template>

<script setup lang="ts">
import PodcastFormBasicInfo from './PodcastForm/BasicInfo.vue';
import PodcastFormSource from './PodcastForm/Source.vue';
import PodcastFormBranding from './PodcastForm/Branding.vue';
import PodcastCommonArtwork from './Common/Artwork.vue';
import mergeExisting from "~/functions/mergeExisting";
import {
    BaseEditModalEmits,
    BaseEditModalProps,
    ModalFormTemplateRef,
    useBaseEditModal
} from "~/functions/useBaseEditModal";
import {computed, ref} from "vue";
import {useResettableRef} from "~/functions/useResettableRef";
import {useTranslate} from "~/vendor/gettext";
import ModalForm from "~/components/Common/ModalForm.vue";
import Tabs from "~/components/Common/Tabs.vue";
import {map} from "lodash";
import {NestedFormOptionInput} from "~/functions/objectToFormOptions.ts";

interface PodcastEditModalProps extends BaseEditModalProps {
    languageOptions: NestedFormOptionInput,
    categoriesOptions: NestedFormOptionInput,
    newArtUrl: string
}

const props = defineProps<PodcastEditModalProps>();

const emit = defineEmits<BaseEditModalEmits>();

const $modal = ref<ModalFormTemplateRef>(null);

const {record, reset} = useResettableRef({
    has_custom_art: false,
    art: null,
    links: {
        art: null
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
        categories: {}
    },
    {
        artwork_file: null,
        categories: []
    },
    {
        resetForm: (originalResetForm) => {
            originalResetForm();
            reset();
        },
        populateForm: (data, formRef) => {
            data.categories = map(
                data.categories,
                (row) => row.category
            );

            record.value = mergeExisting(record.value, data as typeof record.value);
            formRef.value = mergeExisting(formRef.value, data);
        },
    },
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
