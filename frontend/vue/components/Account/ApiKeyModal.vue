<template>
    <b-modal size="md" centered id="api_keys_modal" ref="modal" :title="langTitle" @hidden="clearContents"
             no-enforce-focus>
        <template #default="slotProps">
            <b-alert variant="danger" :show="error != null">{{ error }}</b-alert>

            <b-form v-if="newKey === null" class="form vue-form" @submit.prevent="doSubmit">
                <b-form-fieldset>
                    <b-wrapped-form-group id="form_comments" :field="v$.form.comment" autofocus>
                        <template #label>
                            {{ $gettext('API Key Description/Comments') }}
                        </template>
                    </b-wrapped-form-group>
                </b-form-fieldset>

                <invisible-submit-button/>
            </b-form>

            <div v-else>
                <account-api-key-new-key :new-key="newKey"></account-api-key-new-key>
            </div>
        </template>

        <template #modal-footer="slotProps">
            <slot name="modal-footer" v-bind="slotProps">
                <b-button variant="default" type="button" @click="close">
                    {{ $gettext('Close') }}
                </b-button>
                <b-button v-if="newKey === null" :variant="(v$.$invalid) ? 'danger' : 'primary'" type="submit"
                          @click="doSubmit">
                    {{ $gettext('Create New Key') }}
                </b-button>
            </slot>
        </template>
    </b-modal>
</template>

<script>
import BFormFieldset from "~/components/Form/BFormFieldset";
import InvisibleSubmitButton from "~/components/Common/InvisibleSubmitButton";
import AccountApiKeyNewKey from "./ApiKeyNewKey";
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import {required} from '@vuelidate/validators';
import useVuelidate from "@vuelidate/core";

export default {
    name: 'AccountApiKeyModal',
    components: {BFormFieldset, InvisibleSubmitButton, AccountApiKeyNewKey, BWrappedFormGroup},
    props: {
        createUrl: String
    },
    setup() {
        return {
            v$: useVuelidate()
        };
    },
    data() {
        return {
            error: null,
            newKey: null,
            form: this.getBlankForm()
        }
    },
    validations: {
        form: {
            comment: {required}
        }
    },
    computed: {
        langTitle() {
            return this.$gettext('Add API Key');
        }
    },
    methods: {
        create() {
            this.resetForm();
            this.error = null;

            this.$refs.modal.show();
        },
        getBlankForm() {
            return {
                comment: ''
            };
        },
        resetForm() {
            this.newKey = null;
            this.form = this.getBlankForm();
        },
        doSubmit() {
            this.v$.$touch();
            if (this.v$.$errors.length > 0) {
                return;
            }

            this.error = null;

            this.$wrapWithLoading(
                this.axios({
                    method: 'POST',
                    url: this.createUrl,
                    data: this.form
                })
            ).then((resp) => {
                this.newKey = resp.data.key;
                this.$emit('relist');
            }).catch((error) => {
                this.error = error.response.data.message;
            });
        },
        close() {
            this.$refs.modal.hide();
            this.clearContents();
        },
        clearContents() {
            this.v$.$reset();

            this.error = null;
            this.resetForm();
        },
    }
};
</script>
