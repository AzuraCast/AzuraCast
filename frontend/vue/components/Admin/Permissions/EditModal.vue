<template>
    <modal-form
        ref="modal"
        :loading="loading"
        :title="langTitle"
        :error="error"
        :disable-save-button="v$.$invalid"
        @submit="doSubmit"
        @hidden="clearContents"
    >
        <b-tabs
            content-class="mt-3"
            pills
        >
            <admin-permissions-global-form
                :form="v$"
                :global-permissions="globalPermissions"
            />

            <admin-permissions-station-form
                :form="v$"
                :stations="stations"
                :station-permissions="stationPermissions"
            />
        </b-tabs>
    </modal-form>
</template>

<script>
import {required} from '@vuelidate/validators';
import BaseEditModal from '~/components/Common/BaseEditModal';
import AdminPermissionsGlobalForm from "./Form/GlobalForm";
import AdminPermissionsStationForm from "./Form/StationForm";
import {forEach, map} from 'lodash';
import {useVuelidateOnForm} from "~/functions/useVuelidateOnForm";

/* TODO Options API */

export default {
    name: 'AdminPermissionsEditModal',
    components: {AdminPermissionsStationForm, AdminPermissionsGlobalForm},
    mixins: [BaseEditModal],
    props: {
        stations: {
            type: Object,
            required: true
        },
        globalPermissions: {
            type: Object,
            required: true
        },
        stationPermissions: {
            type: Object,
            required: true
        }
    },
    setup() {
        const {form, resetForm, v$} = useVuelidateOnForm(
            {
                'name': {required},
                'permissions': {
                    'global': {},
                    'station': {},
                }
            },
            {
                'name': '',
                'permissions': {
                    'global': [],
                    'station': [],
                }
            }
        );

        return {
            form,
            resetForm,
            v$
        }
    },
    computed: {
        langTitle() {
            return this.isEditMode
                ? this.$gettext('Edit Role')
                : this.$gettext('Add Role');
        }
    },
    methods: {
        populateForm(data) {
            this.form.name = data.name;
            this.form.permissions.global = data.permissions.global;
            this.form.permissions.station = map(data.permissions.station, (permissions, stationId) => {
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

            forEach(this.form.permissions.station, (row) => {
                form.permissions.station[row.station_id] = row.permissions;
            });

            return form;
        },
    }
};
</script>
