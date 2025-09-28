<template>
    <modal
        id="rename_file"
        ref="$modal"
        centered
        :title="$gettext('Rename File/Directory')"
        @shown="onShown"
        @hidden="onHidden"
    >
        <form @submit.prevent="doRename">
            <form-group-field
                id="new_directory_name"
                ref="$field"
                :field="r$.newPath"
                :label="$gettext('New File Name')"
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
                @click="doRename"
            >
                {{ $gettext('Rename') }}
            </button>
        </template>
    </modal>
</template>

<script setup lang="ts">
import {required} from "@regle/rules";
import FormGroupField from "~/components/Form/FormGroupField.vue";
import {nextTick, ref, useTemplateRef} from "vue";
import {useAxios} from "~/vendor/axios";
import Modal from "~/components/Common/Modal.vue";
import InvisibleSubmitButton from "~/components/Common/InvisibleSubmitButton.vue";
import {useHasModal} from "~/functions/useHasModal.ts";
import {HasRelistEmit} from "~/functions/useBaseEditModal.ts";
import {useResettableRef} from "~/functions/useResettableRef.ts";
import {useAppRegle} from "~/vendor/regle.ts";

const props = defineProps<{
    renameUrl: string
}>();

const emit = defineEmits<HasRelistEmit>();

const file = ref<string | null>(null);

type RenameModalRecord = {
    newPath: string
}

const {record: form, reset: resetForm} = useResettableRef<RenameModalRecord>({
    newPath: ''
});

const {r$} = useAppRegle(
    form,
    {
        newPath: {required}
    },
    {}
);

const $modal = useTemplateRef('$modal');
const {show, hide} = useHasModal($modal);

const open = (filePath: string): void => {
    file.value = filePath;
    form.value.newPath = filePath;

    show();
}

const $field = useTemplateRef('$field');

const onShown = () => {
    void nextTick(() => {
        $field.value?.focus();
    });
};

const onHidden = () => {
    resetForm();
    r$.$reset();
    
    file.value = null;
}

const {axios} = useAxios();

const doRename = async () => {
    const {valid} = await r$.$validate();
    if (!valid) {
        return;
    }

    try {
        await axios.put(props.renameUrl, {
            file: file.value,
            ...form.value
        });
    } finally {
        hide();
        emit('relist');
    }
};

defineExpose({
    open
});
</script>
