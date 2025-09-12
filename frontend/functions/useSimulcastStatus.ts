import {ComputedRef, ref, shallowRef, watch} from "vue";
import {reactiveComputed, useEventSource} from "@vueuse/core";
import {getApiUrl} from "~/router.ts";

export interface SimulcastStatusProps {
    stationShortName: string,
    useSse?: ComputedRef<boolean>
}

interface SimulcastSsePayload {
    data: {
        current_time?: number,
        simulcast: any
    }
}

export default function useSimulcastStatus(initialProps: SimulcastStatusProps) {
    const props = reactiveComputed(() => ({
        useSse: false,
        ...initialProps
    }));

    const simulcastStreams = shallowRef<any[]>([]);
    const isConnected = ref<boolean>(false);

    const updateStream = (stream: any) => {
        const existingIndex = simulcastStreams.value.findIndex(s => s.id === stream.id);
        if (existingIndex >= 0) {
            // Create new array to trigger reactivity
            const newStreams = [...simulcastStreams.value];
            newStreams[existingIndex] = stream;
            simulcastStreams.value = newStreams;
        } else {
            // Create new array to trigger reactivity
            simulcastStreams.value = [...simulcastStreams.value, stream];
        }
    };

    // Set up SSE connection reactively based on useSse prop
    watch(
        () => props.useSse,
        (useSse) => {
            if (useSse) {
                const sseBaseUri = getApiUrl('/live/simulcast/sse');
                const channelName = `simulcast:${props.stationShortName}`;
                
                const sseUriParams = new URLSearchParams({
                    "cf_connect": JSON.stringify({
                        "subs": {
                            [channelName]: {
                                "recover": true
                            },
                        }
                    }),
                });
                const sseUri = sseBaseUri.value + '?' + sseUriParams.toString();

                const handleSseData = (ssePayload: SimulcastSsePayload) => {
                    const jsonData = ssePayload.data;
                    if (jsonData.simulcast && jsonData.simulcast.id) {
                        updateStream(jsonData.simulcast);
                    }
                };

                const {data, status, error} = useEventSource(sseUri);
                
                // Watch connection status
                watch(status, (newStatus) => {
                    isConnected.value = newStatus === 'OPEN';
                });
                
                watch(error, (err) => {
                    console.error('SSE Error:', err);
                });
                
                watch(data, (dataRaw: string | null) => {
                    if (!dataRaw) {
                        return;
                    }

                    const jsonData = JSON.parse(dataRaw);

                    if ('connect' in jsonData) {
                        isConnected.value = true;
                        const connectData = jsonData.connect;
                        // Handle initial cached data
                        for (const subName in connectData.subs) {
                            const sub = connectData.subs[subName];
                            if ('publications' in sub && sub.publications.length > 0) {
                                sub.publications.forEach((initialRow: SimulcastSsePayload) => handleSseData(initialRow));
                            }
                        }
                    } else if ('pub' in jsonData) {
                        handleSseData(jsonData.pub);
                    }
                });
            }
        },
        { immediate: true }
    );

    return {
        simulcastStreams,
        updateStream,
        isConnected
    };
}
