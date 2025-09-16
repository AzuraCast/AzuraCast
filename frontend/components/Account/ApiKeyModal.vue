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
                    :field="r$.comment"
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
                    :class="(r$.$invalid) ? 'btn-danger' : 'btn-primary'"
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
import {nextTick, ref, useTemplateRef} from "vue";
import {getErrorAsString, useAxios} from "~/vendor/axios";
import Modal from "~/components/Common/Modal.vue";
import {useHasModal} from "~/functions/useHasModal.ts";
import {HasRelistEmit} from "~/functions/useBaseEditModal.ts";
import {ApiAccountNewApiKey} from "~/entities/ApiInterfaces.ts";
import {useAppRegle} from "~/vendor/regle.ts";
import {required} from "@regle/rules";

const props = defineProps<{
    createUrl: string,
}>();

const emit = defineEmits<HasRelistEmit>();

const error = ref<string | null>(null);
const newKey = ref<string | null>(null);

const {r$} = useAppRegle(
    {
        comment: ''
    },
    {
        comment: {required}
    },
    {}
);

const clearContents = () => {
    r$.$reset({
        toOriginalState: true
    });

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
    const {valid, data: postData} = await r$.$validate();
    if (!valid) {
        return;
    }

    error.value = null;

    try {
        const {data} = await axios.post<ApiAccountNewApiKey>(
            props.createUrl,
            postData
        );

        newKey.value = data.key;
    } catch (e) {
        error.value = getErrorAsString(e);
    }

    emit('relist');
};

defineExpose({
    create
});
</script>
