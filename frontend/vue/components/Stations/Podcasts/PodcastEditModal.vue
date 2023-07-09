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
        <o-tabs
            nav-tabs-class="nav-tabs"
            content-class="mt-3"
        >
            <podcast-form-basic-info
                :form="v$"
                :categories-options="categoriesOptions"
                :language-options="languageOptions"
            />

            <podcast-common-artwork
                v-model="v$.artwork_file.$model"
                :artwork-src="record.art"
                :new-art-url="newArtUrl"
                :edit-art-url="record.links.art"
            />
        </o-tabs>
    </modal-form>
</template>

<script setup>
import {required} from '@vuelidate/validators';
import PodcastFormBasicInfo from './PodcastForm/BasicInfo';
import PodcastCommonArtwork from './Common/Artwork';
import mergeExisting from "~/functions/mergeExisting";
import {baseEditModalProps, useBaseEditModal} from "~/functions/useBaseEditModal";
import {computed, ref} from "vue";
import {useResettableRef} from "~/functions/useResettableRef";
import {useTranslate} from "~/vendor/gettext";
import ModalForm from "~/components/Common/ModalForm.vue";

const props = defineProps({
    ...baseEditModalProps,
    stationTimeZone: {
        type: String,
        required: true
    },
    languageOptions: {
        type: Object,
        required: true
    },
    categoriesOptions: {
        type: Object,
        required: true
    },
    newArtUrl: {
        type: String,
        required: true
    }
});

const emit = defineEmits(['relist']);

const $modal = ref(); // Template Ref

const {record, reset} = useResettableRef({
    has_custom_art: false,
    art: null,
    links: {}
});

const {
    loading,
    error,
    isEditMode,
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
        'title': {required},
        'link': {},
        'description': {required},
        'language': {required},
        'author': {},
        'email': {},
        'categories': {required},
        'artwork_file': {}
    },
    {
        'title': '',
        'link': '',
        'description': '',
        'language': 'en',
        'author': '',
        'email': '',
        'categories': [],
        'artwork_file': null
    },
    {
        resetForm: (originalResetForm) => {
            originalResetForm();
            reset();
        },
        populateForm: (data, formRef) => {
            record.value = data;
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
