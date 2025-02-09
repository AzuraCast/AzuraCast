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
                :field="v$.newDirectory"
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
                :class="(v$.$invalid) ? 'btn-danger' : 'btn-primary'"
                @click="doMkdir"
            >
                {{ $gettext('Create Directory') }}
            </button>
        </template>
    </modal>
</template>

<script setup lang="ts">
import {required} from "@vuelidate/validators";
import FormGroupField from "~/components/Form/FormGroupField.vue";
import {useVuelidateOnForm} from "~/functions/useVuelidateOnForm";
import {nextTick, useTemplateRef} from "vue";
import {useNotify} from "~/functions/useNotify";
import {useAxios} from "~/vendor/axios";
import {useTranslate} from "~/vendor/gettext";
import Modal from "~/components/Common/Modal.vue";
import InvisibleSubmitButton from "~/components/Common/InvisibleSubmitButton.vue";
import {useHasModal} from "~/functions/useHasModal.ts";
import {HasRelistEmit} from "~/functions/useBaseEditModal.ts";

const props = defineProps<{
    currentDirectory: string,
    mkdirUrl: string
}>();

const emit = defineEmits<HasRelistEmit>();

const {form, v$, resetForm, ifValid} = useVuelidateOnForm(
    {
        newDirectory: {required}
    },
    {
        newDirectory: null
    }
);

const $modal = useTemplateRef('$modal');
const {hide, show: open} = useHasModal($modal);

const onHidden = () => {
    resetForm();
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

const doMkdir = () => {
    ifValid(() => {
        void axios.post(props.mkdirUrl, {
            'currentDirectory': props.currentDirectory,
            'name': form.value.newDirectory
        }).then(() => {
            notifySuccess($gettext('New directory created.'));
        }).finally(() => {
            emit('relist');
            hide();
        });
    });
};

defineExpose({
    open
});
</script>
