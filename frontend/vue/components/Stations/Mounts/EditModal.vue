<template>
    <modal-form ref="modal" :loading="loading" :title="langTitle" :error="error" :disable-save-button="$v.form.$invalid"
                @submit="doSubmit" @hidden="clearContents">

        <b-tabs content-class="mt-3">
            <mount-form-basic-info :form="$v.form"
                                   :station-frontend-type="stationFrontendType"></mount-form-basic-info>
            <mount-form-auto-dj :form="$v.form"
                                :station-frontend-type="stationFrontendType"></mount-form-auto-dj>
            <mount-form-intro v-model="$v.form.intro_file.$model" :record-has-intro="record.intro_path !== null"
                              :new-intro-url="newIntroUrl"
                              :edit-intro-url="record.links.intro"></mount-form-intro>
            <mount-form-advanced v-if="showAdvanced" :form="$v.form"
                                 :station-frontend-type="stationFrontendType"></mount-form-advanced>
        </b-tabs>

    </modal-form>
</template>
<script>
import {required} from 'vuelidate/dist/validators.min.js';
import BaseEditModal from '~/components/Common/BaseEditModal';

import {FRONTEND_ICECAST, FRONTEND_SHOUTCAST} from '~/components/Entity/RadioAdapters';
import MountFormBasicInfo from './Form/BasicInfo';
import MountFormAutoDj from './Form/AutoDj';
import MountFormAdvanced from './Form/Advanced';
import MountFormIntro from "./Form/Intro";
import mergeExisting from "~/functions/mergeExisting";

export default {
    name: 'EditModal',
    emits: ['needs-restart'],
    mixins: [BaseEditModal],
    components: {MountFormIntro, MountFormAdvanced, MountFormAutoDj, MountFormBasicInfo},
    props: {
        stationFrontendType: String,
        newIntroUrl: String,
        showAdvanced: {
            type: Boolean,
            default: true
        },
    },
    data() {
        return {
            record: {
                intro_path: null,
                links: {
                    intro: null
                }
            }
        }
    },
    validations() {
        let validations = {
            form: {
                name: {required},
                display_name: {},
                is_visible_on_public_pages: {},
                is_default: {},
                relay_url: {},
                is_public: {},
                enable_autodj: {},
                autodj_format: {},
                autodj_bitrate: {},
                max_listener_duration: {required},
                intro_file: {}
            }
        };

        if (this.showAdvanced) {
            validations.form.custom_listen_url = {};
        }

        if (FRONTEND_SHOUTCAST === this.stationFrontendType) {
            validations.form.authhash = {};
        }
        if (FRONTEND_ICECAST === this.stationFrontendType) {
            validations.form.fallback_mount = {};

            if (this.showAdvanced) {
                validations.form.frontend_config = {};
            }
        }

        return validations;
    },
    computed: {
        langTitle () {
            return this.isEditMode
                ? this.$gettext('Edit Mount Point')
                : this.$gettext('Add Mount Point');
        }
    },
    methods: {
        resetForm () {
            this.record = {
                intro_path: null,
                links: {
                    intro: null
                }
            };
            this.form = {
                name: null,
                display_name: null,
                is_visible_on_public_pages: true,
                is_default: false,
                relay_url: null,
                is_public: true,
                enable_autodj: true,
                autodj_format: 'mp3',
                autodj_bitrate: 128,
                custom_listen_url: null,
                authhash: null,
                fallback_mount: '/error.mp3',
                max_listener_duration: 0,
                frontend_config: null,
                intro_file: null
            };
        },
        populateForm(d) {
            this.record = d;
            this.form = mergeExisting(this.form, d);
        },
        onSubmitSuccess(response) {
            this.$notifySuccess();

            this.$emit('needs-restart');
            this.$emit('relist');

            this.close();
        },
    }
};
</script>
