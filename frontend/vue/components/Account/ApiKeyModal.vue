<template>
    <b-modal size="md" centered id="api_keys_modal" ref="modal" :title="langTitle" @hidden="clearContents"
             no-enforce-focus>
        <template #default="slotProps">
            <b-alert variant="danger" :show="error != null">{{ error }}</b-alert>

            <b-form v-if="newKey === null" class="form vue-form" @submit.prevent="doSubmit">
                <b-form-fieldset>
                    <b-wrapped-form-group id="form_comments" :field="$v.form.comment" autofocus>
                        <template #label="{lang}">
                            <translate :key="lang">API Key Description/Comments</translate>
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
                    <translate key="lang_btn_close">Close</translate>
                </b-button>
                <b-button v-if="newKey === null" :variant="($v.form.$invalid) ? 'danger' : 'primary'" type="submit"
                          @click="doSubmit">
                    <translate key="lang_btn_create_key">Create New Key</translate>
                </b-button>
            </slot>
        </template>
    </b-modal>
</template>

<script>
import {validationMixin} from 'vuelidate';
import {required} from 'vuelidate/dist/validators.min.js';
import BFormFieldset from "~/components/Form/BFormFieldset";
import InvisibleSubmitButton from "~/components/Common/InvisibleSubmitButton";
import AccountApiKeyNewKey from "./ApiKeyNewKey";
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";

export default {
    name: 'AccountApiKeyModal',
    components: {BWrappedFormGroup, AccountApiKeyNewKey, InvisibleSubmitButton, BFormFieldset},
    mixins: [validationMixin],
    props: {
        createUrl: String
    },
    validations() {
        return {
            form: {
                comment: {required}
            }
        };
    },
    data() {
        return {
            error: null,
            form: {},
            newKey: null,
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
        resetForm() {
            this.newKey = null;
            this.form = {
                comment: ''
            };
        },
        doSubmit() {
            this.$v.form.$touch();
            if (this.$v.form.$anyError) {
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
        },
        clearContents() {
            this.$v.form.$reset();

            this.error = null;
            this.resetForm();
        },
    }
};
</script>
