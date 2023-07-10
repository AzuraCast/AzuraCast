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
            <remote-form-basic-info v-model:form="form" />

            <remote-form-auto-dj v-model:form="form" />
        </o-tabs>
    </modal-form>
</template>

<script setup>
import RemoteFormBasicInfo from "./Form/BasicInfo";
import RemoteFormAutoDj from "./Form/AutoDj";
import {REMOTE_ICECAST} from "~/components/Entity/RadioAdapters";
import {baseEditModalProps, useBaseEditModal} from "~/functions/useBaseEditModal";
import {computed, ref} from "vue";
import {useNotify} from "~/functions/useNotify";
import {useTranslate} from "~/vendor/gettext";
import ModalForm from "~/components/Common/ModalForm.vue";

const props = defineProps({
    ...baseEditModalProps,
});

const emit = defineEmits(['relist', 'needs-restart']);

const $modal = ref(); // Template Ref

const {notifySuccess} = useNotify();

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
        display_name: null,
        is_visible_on_public_pages: true,
        type: REMOTE_ICECAST,
        enable_autodj: false,
        autodj_format: null,
        autodj_bitrate: null,
        custom_listen_url: null,
        url: null,
        mount: null,
        admin_password: null,
        source_port: null,
        source_mount: null,
        source_username: null,
        source_password: null,
        is_public: false
    },
    {
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
        ? $gettext('Edit Remote Relay')
        : $gettext('Add Remote Relay');
});

defineExpose({
    create,
    edit,
    close
});
</script>
