import {forEach} from "es-toolkit/compat";
import {h, VNode} from "vue";
import {useNotify} from "~/components/Common/Toasts/useNotify.ts";

interface BatchEpisode {
    id: string,
    title: string
}

interface BatchResponse {
    success: boolean,
    episodes: BatchEpisode[],
    errors: string[],
    records?: object[] | null
}

export default function useHandlePodcastBatchResponse() {
    const {notifySuccess, notifyError} = useNotify();

    const handleBatchResponse = (
        data: BatchResponse,
        successMessage: string,
        errorMessage: string
    ): void => {
        if (data.success) {
            const itemNameNodes: VNode[] = [];
            forEach(data.episodes, (item) => {
                itemNameNodes.push(h('div', {}, item.title));
            });

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
