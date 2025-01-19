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
            <mount-form-basic-info
                v-model:form="form"
                :station-frontend-type="stationFrontendType"
            />
            <mount-form-auto-dj
                v-model:form="form"
                :station-frontend-type="stationFrontendType"
            />
            <mount-form-intro
                v-model="form.intro_file"
                :record-has-intro="record.intro_path !== null"
                :new-intro-url="newIntroUrl"
                :edit-intro-url="record.links.intro"
            />
            <mount-form-advanced
                v-if="enableAdvancedFeatures"
                v-model:form="form"
                :station-frontend-type="stationFrontendType"
            />
        </tabs>
    </modal-form>
</template>

<script setup lang="ts">
import MountFormBasicInfo from './Form/BasicInfo.vue';
import MountFormAutoDj from './Form/AutoDj.vue';
import MountFormAdvanced from './Form/Advanced.vue';
import MountFormIntro from "./Form/Intro.vue";
import mergeExisting from "~/functions/mergeExisting";
import {
    BaseEditModalEmits,
    BaseEditModalProps,
    ModalFormTemplateRef,
    useBaseEditModal
} from "~/functions/useBaseEditModal";
import {computed, ref} from "vue";
import {useNotify} from "~/functions/useNotify";
import {useTranslate} from "~/vendor/gettext";
import {useResettableRef} from "~/functions/useResettableRef";
import ModalForm from "~/components/Common/ModalForm.vue";
import {useAzuraCast} from "~/vendor/azuracast";
import Tabs from "~/components/Common/Tabs.vue";
import {FrontendAdapter} from "~/entities/RadioAdapters.ts";

interface MountsEditModalProps extends BaseEditModalProps {
    stationFrontendType: FrontendAdapter,
    newIntroUrl: string
}

const props = defineProps<MountsEditModalProps>();

const {enableAdvancedFeatures} = useAzuraCast();

interface MountsEditModalEmits extends BaseEditModalEmits {
    (e: 'needs-restart'): void
}

const emit = defineEmits<MountsEditModalEmits>();

const $modal = ref<ModalFormTemplateRef>(null);

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
        intro_file: {}
    },
    {
        intro_file: null
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
