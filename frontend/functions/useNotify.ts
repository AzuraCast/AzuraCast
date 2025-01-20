import {useTranslate} from "~/vendor/gettext";
import {h, render} from "vue";
import {default as BSToast} from 'bootstrap/js/src/toast';

import Toast from '~/components/Common/Toast.vue';
import {currentVueInstance} from "~/vendor/vueInstance";

type ToastMessage = string | Array<any>

export interface ToastProps {
    message: ToastMessage,
    title?: string,
    variant?: string,
}

export function createToast(props: ToastProps) {
    let slot: Array<any>;
    if (Array.isArray(props.message)) {
        slot = props.message
        props.message = "";
    }

    const defaultSlot = () => {
        return slot
    };

    const vNode = h(Toast, props, defaultSlot);
    vNode.appContext = currentVueInstance._context;

    const newDiv = document.createElement('div');
    newDiv.style.display = "contents";
    document.querySelector('.toast-container').appendChild(newDiv);

    render(vNode, newDiv);

    return new BSToast(vNode.el);
}

/* Composition API BootstrapVue utilities */
export function useNotify() {
    const {$gettext} = useTranslate();

    const notify = (
        message: ToastMessage = null,
        options: Partial<ToastProps> = {}
    ): ToastMessage => {
        if (document.hidden) {
            return;
        }

        const toast = createToast({
            ...options,
            message
        });
        toast.show();

        return message;
    };

    const notifyError = (
        message: ToastMessage = null,
        options: Partial<ToastProps> = {}
    ): ToastMessage => {
        message ??= $gettext('An error occurred and your request could not be completed.');

        const defaults = {
            variant: 'danger'
        };

        return notify(message, {...defaults, ...options});
    };

    const notifySuccess = (
        message: ToastMessage = null,
        options: Partial<ToastProps> = {}
    ): ToastMessage => {
        message ??= $gettext('Changes saved.');

        const defaults = {
            variant: 'success'
        };

        return notify(message, {...defaults, ...options});
    };

    return {
        notify,
        notifyError,
        notifySuccess
    };
}
