<template>
    <b-modal size="lg" id="edit_modal" ref="modal" :title="langTitle" :busy="loading">
        <b-overlay variant="card" :show="loading">
            <b-alert variant="danger" :show="error != null">{{ error }}</b-alert>
            <b-form class="form" @submit.prevent="doSubmit">
                <b-tabs content-class="mt-3">
                    <mount-form-basic-info :form="$v.form"
                                           :station-frontend-type="stationFrontendType"></mount-form-basic-info>
                    <mount-form-auto-dj :form="$v.form"
                                        :station-frontend-type="stationFrontendType"></mount-form-auto-dj>
                    <mount-form-intro v-model="$v.form.intro_file.$model" :record-has-intro="record.intro_path !== null"
                                      :new-intro-url="newIntroUrl"
                                      :edit-intro-url="record.links.intro"></mount-form-intro>
                    <mount-form-advanced v-if="enableAdvancedFeatures" :form="$v.form"
                                         :station-frontend-type="stationFrontendType"></mount-form-advanced>
                </b-tabs>

                <invisible-submit-button/>
            </b-form>
        </b-overlay>
        <template v-slot:modal-footer>
            <b-button variant="default" type="button" @click="close">
                <translate key="lang_btn_close">Close</translate>
            </b-button>
            <b-button variant="primary" type="submit" @click="doSubmit" :disabled="$v.form.$invalid">
                <translate key="lang_btn_save">Save Changes</translate>
            </b-button>
        </template>
    </b-modal>
</template>
<script>
import required from 'vuelidate/src/validators/required';
import InvisibleSubmitButton from '../../Common/InvisibleSubmitButton';
import BaseEditModal from '../../Common/BaseEditModal';

import {FRONTEND_ICECAST, FRONTEND_SHOUTCAST} from '../../Entity/RadioAdapters';
import MountFormBasicInfo from './Form/BasicInfo';
import MountFormAutoDj from './Form/AutoDj';
import MountFormAdvanced from './Form/Advanced';
import MountFormIntro from "./Form/Intro";

export default {
    name: 'EditModal',
    mixins: [BaseEditModal],
    components: {MountFormIntro, MountFormAdvanced, MountFormAutoDj, MountFormBasicInfo, InvisibleSubmitButton},
    props: {
        stationFrontendType: String,
        newIntroUrl: String,
        enableAdvancedFeatures: Boolean
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
                custom_listen_url: {},
                max_listener_duration: {required},
                intro_file: {}
            }
        };

        if (FRONTEND_SHOUTCAST === this.stationFrontendType) {
            validations.form.authhash = {};
        }
        if (FRONTEND_ICECAST === this.stationFrontendType) {
            validations.form.fallback_mount = {};
            validations.form.frontend_config = {};
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
        populateForm (d) {
            this.record = d;
            this.form = {
                'name': d.name,
                'display_name': d.display_name,
                'is_visible_on_public_pages': d.is_visible_on_public_pages,
                'is_default': d.is_default,
                'relay_url': d.relay_url,
                'is_public': d.is_public,
                'enable_autodj': d.enable_autodj,
                'autodj_format': d.autodj_format,
                'autodj_bitrate': d.autodj_bitrate,
                'custom_listen_url': d.custom_listen_url,
                'authhash': d.authhash,
                'fallback_mount': d.fallback_mount,
                'max_listener_duration': d.max_listener_duration,
                'frontend_config': d.frontend_config,
                'intro_file': null
            };
        }
    }
};
</script>
