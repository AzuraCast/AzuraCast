import {forEach} from "es-toolkit/compat";
import {h, VNode} from "vue";
import {useNotify} from "~/components/Common/Toasts/useNotify.ts";
import {ApiMediaBatchResult} from "~/entities/ApiInterfaces.ts";
import {useTranslate} from "~/vendor/gettext.ts";

export default function useHandleBatchResponse() {
    const {notifySuccess, notifyError} = useNotify();
    const {$gettext} = useTranslate();

    const DISPLAY_LIMIT = 4;

    const handleBatchResponse = (
        data: ApiMediaBatchResult,
        successMessage: string,
        errorMessage: string
    ): void => {
        if (data.success) {
            const itemNameNodes: VNode[] = [];

            if (data.directories.length > 0) {
                if (data.directories.length <= DISPLAY_LIMIT) {
                    forEach(data.directories, (item) => {
                        itemNameNodes.push(h('div', {}, item));
                    });
                } else {
                    itemNameNodes.push(
                        h('div', {}, $gettext('%{num} directories', { num: data.directories.length }))
                    );
                }
            }

            if (data.files.length > 0) {
                if (data.files.length <= DISPLAY_LIMIT) {
                    forEach(data.files, (item) => {
                        itemNameNodes.push(h('div', {}, item));
                    });
                } else {
                    itemNameNodes.push(
                        h('div', {}, $gettext('%{num} files', {num: data.files.length}))
                    );
                }
            }

            notifySuccess(itemNameNodes, {
                title: successMessage
            });
        } else {
            const itemErrorNodes: VNode[] = [];
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
