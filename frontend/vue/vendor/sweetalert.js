import Swal from 'sweetalert2';

export default function useSweetAlert(vueApp) {
    vueApp.config.globalProperties.$swal = function (options = {}) {
        return Swal.fire(options);
    };

    vueApp.config.globalProperties.$confirmDelete = function (options = {}) {
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
