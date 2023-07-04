import Swal from 'sweetalert2/dist/sweetalert2';
import {useTranslate} from "~/vendor/gettext";

const {$gettext} = useTranslate();

const swalCustom = Swal.mixin({
    confirmButtonText: $gettext('Confirm'),
    cancelButtonText: $gettext('Cancel'),
    showCancelButton: true,
});

const swalConfirmDelete = swalCustom.mixin({
    title: $gettext('Delete Record?'),
    confirmButtonText: $gettext('Delete'),
    confirmButtonColor: '#e64942',
    focusCancel: true
});

export function useSweetAlert() {
    const showAlert = (options = {}) => {
        return swalCustom.fire(options);
    }

    const confirmDelete = (options = {}) => {
        return swalConfirmDelete.fire(options);
    }

    return {
        showAlert,
        confirmDelete
    };
}

export default function installSweetAlert(vueApp) {
    vueApp.config.globalProperties.$swal = (options = {}) => {
        return swalCustom.fire(options);
    };
    vueApp.config.globalProperties.$confirmDelete = (options = {}) => {
        return swalConfirmDelete.fire(options);
    };
}
