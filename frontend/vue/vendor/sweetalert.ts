import Swal from 'sweetalert2/dist/sweetalert2';
import {useTranslate} from "~/vendor/gettext";
import {Directive} from "vue";

export function useSweetAlert() {
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

    const showAlert = (options = {}) => {
        return swalCustom.fire(options);
    }

    const confirmDelete = (options = {}) => {
        return swalConfirmDelete.fire(options);
    }

    const vConfirmLink: Directive<HTMLAnchorElement, string> = (el, binding) => {
        el.addEventListener('click', (e) => {
            e.preventDefault();

            const options = {
                title: null
            };

            if (el.hasAttribute('data-confirm-title')) {
                options.title = el.getAttribute('data-confirm-title');
            } else if (binding.value) {
                options.title = binding.value;
            }

            confirmDelete(options).then((resp) => {
                if (!resp.value) {
                    return;
                }

                window.location.href = el.href;
            });
        });
    };

    return {
        showAlert,
        confirmDelete,
        vConfirmLink
    };
}
