<template>
    <modal-form ref="modal" :loading="loading" :title="langTitle" :error="error" :disable-save-button="$v.form.$invalid"
                @submit="doSubmit" @hidden="clearContents">

        <b-tabs content-class="mt-3">
            <admin-permissions-global-form :form="$v.form" :global-permissions="globalPermissions">
            </admin-permissions-global-form>

            <admin-permissions-station-form :form="$v.form" :stations="stations"
                                            :station-permissions="stationPermissions">
            </admin-permissions-station-form>
        </b-tabs>

    </modal-form>
</template>

<script>
import {validationMixin} from 'vuelidate';
import {required} from 'vuelidate/dist/validators.min.js';
import BaseEditModal from '~/components/Common/BaseEditModal';
import AdminPermissionsGlobalForm from "./Form/GlobalForm";
import AdminPermissionsStationForm from "./Form/StationForm";
import _ from 'lodash';

export default {
    name: 'AdminPermissionsEditModal',
    components: {AdminPermissionsStationForm, AdminPermissionsGlobalForm},
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
        getSubmittableFormData() {
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

            return form;
        },
    }
};
</script>
