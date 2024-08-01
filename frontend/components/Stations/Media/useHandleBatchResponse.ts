import {forEach} from "lodash";
import {h} from "vue";
import {useNotify} from "~/functions/useNotify.ts";

interface BatchResponse {
    success: boolean,
    dirs: string[],
    files: string[],
    errors: string[]
}

export default function useHandleBatchResponse() {
    const {notifySuccess, notifyError} = useNotify();

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
        handleBatchResponse
    };
}
