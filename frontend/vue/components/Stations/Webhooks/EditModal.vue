<template>
    <modal-form ref="modal" :loading="loading" :title="langTitle" :error="error" :disable-save-button="$v.form.$invalid"
                @submit="doSubmit">

        <type-select v-if="!type" :webhook-types="webhookTypes"></type-select>
        <b-tabs v-else>
            <basic-info :trigger-options="triggerOptions" :form="$v.form"></basic-info>


        </b-tabs>

    </modal-form>
</template>
<script>
import {required} from 'vuelidate/dist/validators.min.js';
import BaseEditModal from '~/components/Common/BaseEditModal';
import TypeSelect from "./Form/TypeSelect";
import BasicInfo from "./Form/BasicInfo";
import _ from "lodash";

const TYPE_GENERIC='generic';
const TYPE_EMAIL='email';
const TYPE_TUNEIN='tunein';
const TYPE_DISCORD='discord';
const TYPE_TELEGRAM='telegram';
const TYPE_TWITTER='twitter';
const TYPE_GOOGLE_ANALYTICS='google_analytics';
const TYPE_MATOMO_ANALYTICS='matomo_analytics';

export default {
    name: 'EditModal',
    components: {BasicInfo, TypeSelect},
    mixins: [BaseEditModal],
    props: {
        webhookTypes: Object,
        webhookTriggers: Object
    },
    validations() {
        let validations = {
            type: {required},
            form: {
                name: {required},
                triggers: {},
                config: {}
            }
        };

        if (this.triggerOptions.length > 0) {
            validations.form.triggers = {required};
        }

        return validations;
    },
    data() {
        return {
            type: null,
        }
    },
    computed: {
        langTitle() {
            return this.isEditMode
                ? this.$gettext('Edit Web Hook')
                : this.$gettext('Add Web Hook');
        },
        triggerOptions () {
            if (!this.type) {
                return [];
            }

            let webhookKeys = _.get(this.webhookTypes, [this.type, 'triggers'], []);
            return _.map(webhookKeys, (key) => {
                return {
                    text: this.webhookTriggers[key],
                    value: key
                };
            });
        },
    },
    methods: {
        resetForm() {
            this.form = {};
        },
        populateForm(d) {
            this.form = {};
        }
    }
};
</script>
