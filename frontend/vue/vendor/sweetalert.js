import Swal from 'sweetalert2';
import gettext from "~/vendor/gettext";

const {$gettext} = gettext;

const swalCustom = Swal.mixin({
    confirmButtonText: $gettext('Confirm'),
    cancelButtonText: $gettext('Cancel'),
    showCancelButton: true,
});

export function showAlert(options = {}) {
    return swalCustom.fire(options);
}

const swalConfirmDelete = swalCustom.mixin({
    title: $gettext('Delete Record?'),
    confirmButtonText: $gettext('Delete'),
    confirmButtonColor: '#e64942',
    focusCancel: true
});

export function confirmDelete(options = {}) {
    return swalConfirmDelete.fire(options);
}

export default function useSweetAlert(vueApp) {
    vueApp.config.globalProperties.$swal = showAlert;
    vueApp.config.globalProperties.$confirmDelete = confirmDelete;
}
