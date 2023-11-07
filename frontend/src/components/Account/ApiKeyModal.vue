<template>
    <modal
        id="api_keys_modal"
        ref="$modal"
        size="md"
        centered
        :title="$gettext('Add API Key')"
        no-enforce-focus
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
import AccountApiKeyNewKey from "./ApiKeyNewKey.vue";
import FormGroupField from "~/components/Form/FormGroupField.vue";
import {required} from '@vuelidate/validators';
import {ref} from "vue";
import {useVuelidateOnForm} from "~/functions/useVuelidateOnForm";
import {useAxios} from "~/vendor/axios";
import Modal from "~/components/Common/Modal.vue";
import {ModalTemplateRef, useHasModal} from "~/functions/useHasModal.ts";

const props = defineProps({
    createUrl: {
        type: String,
        required: true
    }
});

const emit = defineEmits(['relist']);

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

const $modal = ref<ModalTemplateRef>(null);
const {show, hide} = useHasModal($modal);

const create = () => {
    clearContents();
    show();
};

const {axios} = useAxios();

const doSubmit = async () => {
    const isValid = await validate();
    if (!isValid) {
        return;
    }

    error.value = null;

    axios({
        method: 'POST',
        url: props.createUrl,
        data: form.value
    }).then((resp) => {
        newKey.value = resp.data.key;
        emit('relist');
    }).catch((error) => {
        error.value = error.response.data.message;
    });
};

defineExpose({
    create
});
</script>
