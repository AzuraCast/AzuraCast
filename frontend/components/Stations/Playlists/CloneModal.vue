<template>
    <modal-form
        id="clone_modal"
        ref="$modal"
        :title="$gettext('Duplicate Playlist')"
        :disable-save-button="r$.$invalid"
        @submit="doSubmit"
        @hidden="clearContents"
    >
        <div class="row g-3">
            <form-group-field
                id="form_edit_name"
                class="col-md-12"
                :field="r$.name"
                :label="$gettext('New Playlist Name')"
            />

            <form-group-multi-check
                id="form_edit_clone"
                class="col-md-12"
                :field="r$.clone"
                :options="copyOptions"
                stacked
                :label="$gettext('Customize Copy')"
            />
        </div>
    </modal-form>
</template>

<script setup lang="ts">
import {required} from "@regle/rules";
import FormGroupField from "~/components/Form/FormGroupField.vue";
import ModalForm from "~/components/Common/ModalForm.vue";
import {ref, useTemplateRef} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useNotify} from "~/components/Common/Toasts/useNotify.ts";
import {useAxios} from "~/vendor/axios";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";
import {useResettableRef} from "~/functions/useResettableRef.ts";
import {useAppRegle} from "~/vendor/regle.ts";

const emit = defineEmits<{
    (e: 'relist'): void,
    (e: 'needs-restart'): void
}>();

const cloneUrl = ref<string | null>(null);

const {record: form, reset: resetForm} = useResettableRef({
    name: '',
    clone: []
});

const {r$} = useAppRegle(
    form,
    {
        name: {required},
        clone: {}
    },
    {}
);

const clearContents = () => {
    cloneUrl.value = null;
    resetForm();
    r$.$reset();
};

const {$gettext} = useTranslate();

const copyOptions = [
    {
        value: 'media',
        text: $gettext('Copy associated media and folders.')
    },
    {
        value: 'schedule',
        text: $gettext('Copy scheduled playback times.')
    }
];

const $modal = useTemplateRef('$modal');

const open = (name: string, newCloneUrl: string) => {
    clearContents();

    cloneUrl.value = newCloneUrl;
    form.value.name = $gettext(
        '%{name} - Copy',
        {name: name}
    );

    $modal.value?.show();
};

const {notifySuccess} = useNotify();
const {axios} = useAxios();

const doSubmit = async () => {
    const {valid} = await r$.$validate();
    if (!valid || !cloneUrl.value) {
        return;
    }

    await axios({
        method: 'POST',
        url: cloneUrl.value,
        data: form.value
    });

    notifySuccess();
    emit('needs-restart');
    emit('relist');
    $modal.value?.hide();
};

defineExpose({
    open
});
</script>
