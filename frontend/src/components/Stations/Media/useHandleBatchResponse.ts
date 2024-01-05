import {forEach} from "lodash";
import {h} from "vue";
import {useNotify} from "~/functions/useNotify.ts";
import {useTranslate} from "~/vendor/gettext.ts";

interface BatchResponse {
    success: bool,
    dirs: string[],
    files: string[],
    errors: string[]
}

export default function useHandleBatchResponse() {
    const {notifySuccess, notifyError} = useNotify();
    const {$gettext} = useTranslate();

    const notifyNoFiles = () => {
        notifyError($gettext('No files selected.'));
    };

    const handleBatchResponse = (
        data: BatchResponse,
        successMessage: string,
        errorMessage: string
    ): void => {
        if (data.success) {
            const itemNameNodes = [];
            forEach(data.dirs, (item) => {
                itemNameNodes.push(h('div', {}, item));
            });
            forEach(data.files, (item) => {
                itemNameNodes.push(h('div', {}, item));
            });

            notifySuccess(itemNameNodes, {
                title: successMessage
            });
        } else {
            const itemErrorNodes = [];
            forEach(data.errors, (err) => {
                itemErrorNodes.push(h('div', {}, err));
            })

            notifyError(itemErrorNodes, {
                title: errorMessage
            });
        }
    }

    return {
        notifyNoFiles,
        handleBatchResponse
    };
}
