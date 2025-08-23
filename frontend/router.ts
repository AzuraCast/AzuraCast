import {computed, ComputedRef, MaybeRefOrGetter, toValue} from "vue";
import {useStationId} from "~/functions/useStationQuery.ts";

export function getApiUrl(suffix: string): ComputedRef<string> {
    return computed((): string => {
        return `/api${suffix}`;
    });
}

export function getStationApiUrl(
    suffix: MaybeRefOrGetter<string>,
    id?: MaybeRefOrGetter<string | number | null>
): ComputedRef<string> {
    if (!id) {
        id = useStationId();
    }

    return computed((): string => {
        const idValue = toValue(id);

        if (idValue === null) {
            throw new Error("Can't find station ID!");
        }

        const suffixValue = toValue(suffix);
        
        const stationSuffix = `/station/${idValue}${suffixValue}`;
        return getApiUrl(stationSuffix).value;
    });
}
