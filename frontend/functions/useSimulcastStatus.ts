import {computed, onMounted, ref, shallowRef, watch} from "vue";
import {reactiveComputed, useEventSource} from "@vueuse/core";
import {getApiUrl} from "~/router.ts";

export interface SimulcastStatusProps {
    stationShortName: string,
    useSse?: boolean
}

interface SimulcastSsePayload {
    data: {
        current_time?: number,
        simulcast: any
    }
}

export default function useSimulcastStatus(initialProps: SimulcastStatusProps) {
    const props = reactiveComputed(() => ({
        useSse: true,
        ...initialProps
    }));

    const simulcastStreams = shallowRef<any[]>([]);
    const lastUpdate = ref<number>(0);

    const updateStream = (stream: any) => {
        const existingIndex = simulcastStreams.value.findIndex(s => s.id === stream.id);
        if (existingIndex >= 0) {
            simulcastStreams.value[existingIndex] = stream;
        } else {
            simulcastStreams.value.push(stream);
        }
        lastUpdate.value = Date.now();
    };

    if (props.useSse) {
        const sseBaseUri = getApiUrl('/live/simulcast/sse');
        const sseUriParams = new URLSearchParams({
            "cf_connect": JSON.stringify({
                "subs": {
                    [`simulcast:${props.stationShortName}`]: {
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

        const {data} = useEventSource(sseUri);
        watch(data, (dataRaw: string | null) => {
            if (!dataRaw) {
                return;
            }

            const jsonData = JSON.parse(dataRaw);

            if ('connect' in jsonData) {
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

    // Computed properties
    const activeStreams = computed(() => {
        return simulcastStreams.value.filter(stream => 
            ['running', 'starting', 'stopping'].includes(stream.status)
        );
    });

    const errorStreams = computed(() => {
        return simulcastStreams.value.filter(stream => stream.status === 'error');
    });

    const hasActiveStreams = computed(() => {
        return activeStreams.value.length > 0;
    });

    const hasErrors = computed(() => {
        return errorStreams.value.length > 0;
    });

    return {
        simulcastStreams,
        activeStreams,
        errorStreams,
        hasActiveStreams,
        hasErrors,
        lastUpdate,
        updateStream
    };
}
