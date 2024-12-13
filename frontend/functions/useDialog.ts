import {useTranslate} from "~/vendor/gettext";
import {h, render} from "vue";
import {currentVueInstance} from "~/vendor/vueInstance.ts";
import Dialog, {DialogComponentProps, DialogOptions, DialogResponse} from "~/components/Common/Dialog.vue";

export function createDialog(options: DialogOptions): Promise<DialogResponse> {
    let resolveFunc: (value: DialogResponse) => void = () => {
        /* Replaced by promise func below */
    }

    const promise = new Promise<DialogResponse>((resolve) => {
        resolveFunc = resolve
    });

    const props: DialogComponentProps = {
        ...options,
        resolvePromise: resolveFunc
    }

    const vNode = h(Dialog, props);
    vNode.appContext = currentVueInstance._context;

    const newDiv = document.createElement('div');
    render(vNode, newDiv);

    return promise;
}

export function useDialog() {
    const {$gettext} = useTranslate();

    const showAlert = (options: Partial<DialogOptions> = {}): Promise<DialogResponse> => {
        const props: DialogOptions = {
            title: $gettext('Are you sure?'),
            confirmButtonText: $gettext('Confirm'),
            cancelButtonText: $gettext('Cancel'),
            ...options
        }

        return createDialog(props);
    }

    const confirmDelete = (options: Partial<DialogOptions> = {}): Promise<DialogResponse> => {
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

    return {
        showAlert,
        confirmDelete
    };
}
