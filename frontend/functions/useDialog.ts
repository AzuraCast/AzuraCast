import {useTranslate} from "~/vendor/gettext";
import {Directive, h, render} from "vue";
import {currentVueInstance} from "~/vendor/vueInstance.ts";
import Dialog, {DialogComponentProps, DialogOptions, DialogResponse} from "~/components/Common/Dialog.vue";

export function createDialog(props: DialogComponentProps) {
    let resolveFunc: (value: DialogResponse) => void = () => {
        /* Replaced by promise func below */
    }

    const promise = new Promise<DialogResponse>((resolve) => {
        resolveFunc = resolve
    });

    props.resolvePromise = resolveFunc;

    const vNode = h(Dialog, props);
    vNode.appContext = currentVueInstance._context;

    const newDiv = document.createElement('div');
    render(vNode, newDiv);

    return promise;
}

export function useDialog() {
    const {$gettext} = useTranslate();

    const showAlert = (options: DialogOptions = {}) => {
        const props: DialogOptions = {
            confirmButtonText: $gettext('Confirm'),
            confirmButtonClass: 'btn-success',
            cancelButtonText: $gettext('Cancel'),
            ...options
        }

        return createDialog(props);
    }

    const confirmDelete = (options: DialogOptions = {}) => {
        const props: DialogOptions = {
            title: $gettext('Delete Record?'),
            confirmButtonText: $gettext('Delete'),
            confirmButtonClass: 'btn-danger',
            cancelButtonText: $gettext('Cancel'),
            focusCancel: true,
            ...options
        }

        return createDialog(props);
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
