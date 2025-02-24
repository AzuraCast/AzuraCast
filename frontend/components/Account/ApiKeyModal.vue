<template>
    <modal
        id="api_keys_modal"
        ref="$modal"
        size="md"
        centered
        :title="$gettext('Add API Key')"
        no-enforce-focus
        @shown="onShown"
        @hidden="clearContents"
    >
        <template #default>
            <div
                v-show="error != null"
                class="alert alert-danger"
            >
                {{ error }}
            </div>

            <form
                v-if="newKey === null"
                class="form vue-form"
                @submit.prevent="doSubmit"
            >
                <form-group-field
                    id="form_comments"
                    ref="$field"
                    :field="v$.comment"
                    autofocus
                    :label="$gettext('API Key Description/Comments')"
                />

                <invisible-submit-button />
            </form>

            <div v-else>
                <account-api-key-new-key :new-key="newKey" />
            </div>
        </template>

        <template #modal-footer="slotProps">
            <slot
                name="modal-footer"
                v-bind="slotProps"
            >
                <button
                    type="button"
                    class="btn btn-secondary"
                    @click="hide"
                >
                    {{ $gettext('Close') }}
                </button>
                <button
                    v-if="newKey === null"
                    type="submit"
                    class="btn"
                    :class="(v$.$invalid) ? 'btn-danger' : 'btn-primary'"
                    @click="doSubmit"
                >
                    {{ $gettext('Create New Key') }}
                </button>
            </slot>
        </template>
    </modal>
</template>

<script setup lang="ts">
import InvisibleSubmitButton from "~/components/Common/InvisibleSubmitButton.vue";
import AccountApiKeyNewKey from "~/components/Account/ApiKeyNewKey.vue";
import FormGroupField from "~/components/Form/FormGroupField.vue";
import {required} from "@vuelidate/validators";
import {nextTick, ref, useTemplateRef} from "vue";
import {useVuelidateOnForm} from "~/functions/useVuelidateOnForm";
import {useAxios} from "~/vendor/axios";
import Modal from "~/components/Common/Modal.vue";
import {useHasModal} from "~/functions/useHasModal.ts";
import {HasRelistEmit} from "~/functions/useBaseEditModal.ts";
import {ApiAccountNewApiKey} from "~/entities/ApiInterfaces.ts";

const props = defineProps<{
    createUrl: string,
}>();

const emit = defineEmits<HasRelistEmit>();

const error = ref(null);
const newKey = ref(null);

const {form, resetForm, v$, validate} = useVuelidateOnForm(
    {
        comment: {required}
    },
    {
        comment: ''
    }
);

const clearContents = () => {
    resetForm();
    error.value = null;
    newKey.value = null;
};

const $modal = useTemplateRef('$modal');
const {show, hide} = useHasModal($modal);

const create = () => {
    clearContents();
    show();
};

const $field = useTemplateRef('$field');

const onShown = () => {
    void nextTick(() => {
        $field.value?.focus();
    })
};

const {axios} = useAxios();

const doSubmit = async () => {
    const isValid = await validate();
    if (!isValid) {
        return;
    }

    error.value = null;

    try {
        const {data} = await axios.post<ApiAccountNewApiKey>(
            props.createUrl,
            form.value
        );

        newKey.value = data.key;
    } catch (error) {
        error.value = error.response.data.message;
    }

    emit('relist');
};

defineExpose({
    create
});
</script>
