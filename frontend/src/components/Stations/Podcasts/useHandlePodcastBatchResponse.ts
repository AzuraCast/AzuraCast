import {forEach} from "lodash";
import {h} from "vue";
import {useNotify} from "~/functions/useNotify.ts";

interface BatchEpisode {
    id: string,
    title: string
}

interface BatchResponse {
    success: bool,
    episodes: BatchEpisode[],
    errors: string[],
    records?: array | null
}

export default function useHandlePodcastBatchResponse() {
    const {notifySuccess, notifyError} = useNotify();

    const handleBatchResponse = (
        data: BatchResponse,
        successMessage: string,
        errorMessage: string
    ): void => {
        if (data.success) {
            const itemNameNodes = [];
            forEach(data.episodes, (item) => {
                itemNameNodes.push(h('div', {}, item.title));
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
