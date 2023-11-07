import {useAzuraCastStation} from "~/vendor/azuracast.ts";
import {computed, ComputedRef} from "vue";

export function getApiUrl(suffix: string): ComputedRef<string> {
    return computed((): string => {
        return `/api${suffix}`;
    });
}

export function getStationApiUrl(suffix: string): ComputedRef<string> {
    const {id} = useAzuraCastStation();

    return computed((): string => {
        const stationSuffix = `/station/${id}${suffix}`;
        return getApiUrl(stationSuffix).value;
    });
}
