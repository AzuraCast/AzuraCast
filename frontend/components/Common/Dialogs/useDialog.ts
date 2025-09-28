import {useTranslate} from "~/vendor/gettext";
import {ref} from "vue";
import {defineStore} from "pinia";
import {remove} from "es-toolkit";

export type DialogOptions = {
    title: string,
    confirmButtonText: string,
    confirmButtonClass?: string,
    cancelButtonText: string,
    cancelButtonClass?: string,
    focusCancel?: boolean
}

export type DialogResponse = {
    value: boolean
}

type DialogRow = {
    id: string,
    promise: Promise<DialogResponse>,
    resolveFunc: (value: DialogResponse) => void,
    options: DialogOptions
}

export const useDialog = defineStore(
    'global-dialogs',
    () => {
        const dialogs = ref<DialogRow[]>([]);

        const createDialog = (options: DialogOptions): Promise<DialogResponse> => {
            let resolveFunc: (value: DialogResponse) => void = () => {
                /* Replaced by promise func below */
            }

            const promise = new Promise<DialogResponse>((resolve) => {
                resolveFunc = resolve
            });

            dialogs.value.push({
                id: Math.random().toString(36),
                promise: promise,
                resolveFunc: resolveFunc,
                options: options
            });

            return promise;
        }

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

        const resolveDialog = (id: string, value: DialogResponse): void => {
            const dialog = dialogs.value.find(
                (row) => row.id === id,
            );

            if (dialog) {
                dialog.resolveFunc(value);
            }
        }

        const removeDialog = (id: string): void => {
            // Send a second redundant resolve to ensure the dialog is closed out.
            resolveDialog(id, {
                value: false
            });

            remove(
                dialogs.value,
                (dialog) => dialog.id === id
            );
        }

        return {
            dialogs,
            resolveDialog,
            removeDialog,
            showAlert,
            confirmDelete
        };
    }
);
