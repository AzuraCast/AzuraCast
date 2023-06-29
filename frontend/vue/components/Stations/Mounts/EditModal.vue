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
        <b-tabs
            content-class="mt-3"
        >
            <mount-form-basic-info
                :form="v$"
                :station-frontend-type="stationFrontendType"
            />
            <mount-form-auto-dj
                :form="v$"
                :station-frontend-type="stationFrontendType"
            />
            <mount-form-intro
                v-model="v$.intro_file.$model"
                :record-has-intro="record.intro_path !== null"
                :new-intro-url="newIntroUrl"
                :edit-intro-url="record.links.intro"
            />
            <mount-form-advanced
                v-if="showAdvanced"
                :form="v$"
                :station-frontend-type="stationFrontendType"
            />
        </b-tabs>
    </modal-form>
</template>

<script setup>
import {required} from '@vuelidate/validators';
import {FRONTEND_ICECAST, FRONTEND_SHOUTCAST} from '~/components/Entity/RadioAdapters';
import MountFormBasicInfo from './Form/BasicInfo';
import MountFormAutoDj from './Form/AutoDj';
import MountFormAdvanced from './Form/Advanced';
import MountFormIntro from "./Form/Intro";
import mergeExisting from "~/functions/mergeExisting";
import {baseEditModalProps, useBaseEditModal} from "~/functions/useBaseEditModal";
import {computed, ref} from "vue";
import {useNotify} from "~/functions/useNotify";
import {useTranslate} from "~/vendor/gettext";
import {useResettableRef} from "~/functions/useResettableRef";
import ModalForm from "~/components/Common/ModalForm.vue";

const props = defineProps({
    ...baseEditModalProps,
    stationFrontendType: {
        type: String,
        required: true
    },
    newIntroUrl: {
        type: String,
        required: true
    },
    showAdvanced: {
        type: Boolean,
        default: true
    },
});

const emit = defineEmits(['relist', 'needs-restart']);

const $modal = ref(); // Template Ref

const {notifySuccess} = useNotify();

const {record, reset} = useResettableRef({
    intro_path: null,
    links: {
        intro: null
    }
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
    () => computed(() => {
        let validations = {
            name: {required},
            display_name: {},
            is_visible_on_public_pages: {},
            is_default: {},
            relay_url: {},
            is_public: {},
            enable_autodj: {},
            autodj_format: {},
            autodj_bitrate: {},
            max_listener_duration: {required},
            intro_file: {}
        };

        if (props.showAdvanced) {
            validations.custom_listen_url = {};
        }

        if (FRONTEND_SHOUTCAST === props.stationFrontendType) {
            validations.authhash = {};
        }

        if (FRONTEND_ICECAST === props.stationFrontendType) {
            validations.fallback_mount = {};

            if (props.showAdvanced) {
                validations.frontend_config = {};
            }
        }

        return validations;
    }),
    {
        name: null,
        display_name: null,
        is_visible_on_public_pages: true,
        is_default: false,
        relay_url: null,
        is_public: true,
        enable_autodj: true,
        autodj_format: 'mp3',
        autodj_bitrate: 128,
        custom_listen_url: null,
        authhash: null,
        fallback_mount: '/error.mp3',
        max_listener_duration: 0,
        frontend_config: null,
        intro_file: null
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
        onSubmitSuccess: () => {
            notifySuccess();
            emit('relist');
            emit('needs-restart');
            close();
        },
    }
);

const {$gettext} = useTranslate();

const langTitle = computed(() => {
    return isEditMode.value
        ? $gettext('Edit Mount Point')
        : $gettext('Add Mount Point');
});

defineExpose({
    create,
    edit,
    close
});
</script>
