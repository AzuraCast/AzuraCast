import {computed, ComputedRef, MaybeRefOrGetter, toValue} from "vue";
import {useAzuraCastStation} from "~/vendor/azuracast.ts";

export function getApiUrl(suffix: string): ComputedRef<string> {
    return computed((): string => {
        return `/api${suffix}`;
    });
}

export function getStationApiUrl(
    suffix: MaybeRefOrGetter<string>,
    id?: MaybeRefOrGetter<string | number>
): ComputedRef<string> {
    if (!id) {
        const station = useAzuraCastStation();
        if (station) {
            id = station.id;
        } else {
            throw new Error("Can't find station ID!");
        }
    }

    return computed((): string => {
        const idValue = toValue(id);
        const suffixValue = toValue(suffix);
        
        const stationSuffix = `/station/${idValue}${suffixValue}`;
        return getApiUrl(stationSuffix).value;
    });
}
