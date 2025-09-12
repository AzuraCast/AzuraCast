<template>
    <modal
        id="create_directory"
        ref="$modal"
        centered
        :title="$gettext('New Directory')"
        @hidden="onHidden"
        @shown="onShown"
    >
        <form @submit.prevent="doMkdir">
            <form-group-field
                id="new_directory_name"
                ref="$field"
                :field="r$.newDirectory"
                :label="$gettext('Directory Name')"
            />

            <invisible-submit-button />
        </form>
        <template #modal-footer>
            <button
                type="button"
                class="btn btn-secondary"
                @click="hide"
            >
                {{ $gettext('Close') }}
            </button>
            <button
                type="button"
                class="btn"
                :class="(r$.$invalid) ? 'btn-danger' : 'btn-primary'"
                @click="doMkdir"
            >
                {{ $gettext('Create Directory') }}
            </button>
        </template>
    </modal>
</template>

<script setup lang="ts">
import {required} from "@regle/rules";
import FormGroupField from "~/components/Form/FormGroupField.vue";
import {nextTick, useTemplateRef} from "vue";
import {useNotify} from "~/components/Common/Toasts/useNotify.ts";
import {useAxios} from "~/vendor/axios";
import {useTranslate} from "~/vendor/gettext";
import Modal from "~/components/Common/Modal.vue";
import InvisibleSubmitButton from "~/components/Common/InvisibleSubmitButton.vue";
import {useHasModal} from "~/functions/useHasModal.ts";
import {HasRelistEmit} from "~/functions/useBaseEditModal.ts";
import {useResettableRef} from "~/functions/useResettableRef.ts";
import {useAppRegle} from "~/vendor/regle.ts";

const props = defineProps<{
    currentDirectory: string,
    mkdirUrl: string
}>();

const emit = defineEmits<HasRelistEmit>();

type NewDirectoryRecord = {
    newDirectory: string
}

const {record: form, reset: resetForm} = useResettableRef<NewDirectoryRecord>({
    newDirectory: ''
});

const {r$} = useAppRegle(
    form,
    {
        newDirectory: {required}
    },
    {}
);

const $modal = useTemplateRef('$modal');
const {hide, show: open} = useHasModal($modal);

const onHidden = () => {
    resetForm();
    r$.$reset();
}

const $field = useTemplateRef('$field');

const onShown = () => {
    void nextTick(() => {
        $field.value?.focus();
    })
};

const {notifySuccess} = useNotify();
const {axios} = useAxios();
const {$gettext} = useTranslate();

const doMkdir = async () => {
    const {valid} = await r$.$validate();
    if (!valid) {
        return;
    }

    try {
        await axios.post(props.mkdirUrl, {
            'currentDirectory': props.currentDirectory,
            'name': form.value.newDirectory
        });

        notifySuccess($gettext('New directory created.'));
    } finally {
        emit('relist');
        hide();
    }
};

defineExpose({
    open
});
</script>
