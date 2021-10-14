import Swal from 'sweetalert2';

import Vue from 'vue';

const ConfirmFunctions = {
    install(Vue, opts) {
        Vue.prototype.$swal = function (options = {}) {
            return Swal.fire(options);
        };

        Vue.prototype.$confirmDelete = function (options = {}) {
            const defaults = {
                title: this.$gettext('Delete Record?'),
                confirmButtonText: this.$gettext('Delete'),
                confirmButtonColor: '#e64942',
                showCancelButton: true,
                focusCancel: true
            };

            return this.$swal({...defaults, ...options});
        };
    }
};

Vue.use(ConfirmFunctions);
