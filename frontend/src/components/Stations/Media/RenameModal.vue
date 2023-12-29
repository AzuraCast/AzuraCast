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
                :field="v$.newPath"
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
                :class="(v$.$invalid) ? 'btn-danger' : 'btn-primary'"
                @click="doRename"
            >
                {{ $gettext('Rename') }}
            </button>
        </template>
    </modal>
</template>

<script setup lang="ts">
import {required} from '@vuelidate/validators';
import FormGroupField from "~/components/Form/FormGroupField.vue";
import {nextTick, ref} from "vue";
import {useVuelidateOnForm} from "~/functions/useVuelidateOnForm";
import {useAxios} from "~/vendor/axios";
import Modal from "~/components/Common/Modal.vue";
import InvisibleSubmitButton from "~/components/Common/InvisibleSubmitButton.vue";
import {ModalTemplateRef, useHasModal} from "~/functions/useHasModal.ts";

const props = defineProps({
    renameUrl: {
        type: String,
        required: true
    }
});

const emit = defineEmits(['relist']);

const file = ref(null);

const {form, v$, resetForm, ifValid} = useVuelidateOnForm(
    {
        newPath: {required}
    },
    {
        newPath: null
    }
);

const $modal = ref<ModalTemplateRef>(null);
const {show, hide} = useHasModal($modal);

const open = (filePath: string): void => {
    file.value = filePath;
    form.value.newPath = filePath;

    show();
}

const $field = ref<InstanceType<typeof FormGroupField> | null>(null);

const onShown = () => {
    nextTick(() => {
        $field.value?.focus();
    });
};

const onHidden = () => {
    resetForm();
    file.value = null;
}

const {axios} = useAxios();

const doRename = () => {
    ifValid(() => {
        axios.put(props.renameUrl, {
            file: file.value,
            ...form.value
        }).finally(() => {
            hide();
            emit('relist');
        });
    });
};

defineExpose({
    open
});
</script>
