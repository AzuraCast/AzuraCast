<template>
    <b-modal size="lg" id="edit_modal" ref="modal" :title="langTitle" :busy="loading">
        <b-overlay variant="card" :show="loading">
            <b-alert variant="danger" :show="error != null">{{ error }}</b-alert>

            <b-form class="form" @submit.prevent="doSubmit">
                <b-tabs content-class="mt-3">
                    <admin-permissions-global-form :form="$v.form" :global-permissions="globalPermissions">
                    </admin-permissions-global-form>

                    <admin-permissions-station-form :form="$v.form" :stations="stations"
                                                    :station-permissions="stationPermissions">
                    </admin-permissions-station-form>
                </b-tabs>

                <invisible-submit-button/>
            </b-form>
        </b-overlay>
        <template #modal-footer>
            <b-button variant="default" type="button" @click="close">
                <translate key="lang_btn_close">Close</translate>
            </b-button>
            <b-button variant="primary" type="submit" @click="doSubmit" :disabled="$v.form.$invalid">
                <translate key="lang_btn_save_changes">Save Changes</translate>
            </b-button>
        </template>
    </b-modal>
</template>

<script>
import {validationMixin} from 'vuelidate';
import {required} from 'vuelidate/dist/validators.min.js';
import InvisibleSubmitButton from '~/components/Common/InvisibleSubmitButton';
import BaseEditModal from '~/components/Common/BaseEditModal';
import AdminPermissionsGlobalForm from "./Form/GlobalForm";
import AdminPermissionsStationForm from "~/components/Admin/Permissions/Form/StationForm";
import _ from 'lodash';

export default {
    name: 'AdminPermissionsEditModal',
    components: {AdminPermissionsStationForm, AdminPermissionsGlobalForm, InvisibleSubmitButton},
    mixins: [validationMixin, BaseEditModal],
    props: {
        stations: Object,
        globalPermissions: Object,
        stationPermissions: Object
    },
    computed: {
        langTitle() {
            return this.isEditMode
                ? this.$gettext('Edit Role')
                : this.$gettext('Add Role');
        }
    },
    validations() {
        return {
            form: {
                'name': {required},
                'permissions': {
                    'global': {},
                    'station': {
                        $each: {
                            'station_id': {},
                            'permissions': {},
                        }
                    },
                }
            }
        };
    },
    methods: {
        resetForm() {
            this.form = {
                'name': '',
                'permissions': {
                    'global': [],
                    'station': [],
                }
            };
        },
        populateForm (data) {
            this.form.name = data.name;
            this.form.permissions.global = data.permissions.global;
            this.form.permissions.station = _.map(data.permissions.station, (permissions, stationId) => {
                return {
                    'station_id': stationId,
                    'permissions': permissions
                };
            });
        },
        buildSubmitRequest () {
            let form = {
                name: this.form.name,
                permissions: {
                    global: this.form.permissions.global,
                    station: {}
                }
            };

            _.forEach(this.form.permissions.station, (row) => {
                form.permissions.station[row.station_id] = row.permissions;
            });

            return {
                method: (this.isEditMode)
                    ? 'PUT'
                    : 'POST',
                url: (this.isEditMode)
                    ? this.editUrl
                    : this.createUrl,
                data: form
            };
        },
    }
};
</script>
