import {computed, ComputedRef, MaybeRefOrGetter, toValue} from "vue";
import {useStationId} from "~/functions/useStationQuery.ts";

export function getApiUrl(suffix: MaybeRefOrGetter<string>): ComputedRef<string> {
    return computed((): string => {
        const suffixValue = toValue(suffix);
        return `/api${suffixValue}`;
    });
}

export function getStationApiUrl(
    suffix: MaybeRefOrGetter<string>,
    id?: MaybeRefOrGetter<string | number | null>
): ComputedRef<string> {
    if (!id) {
        id = useStationId();
    }

    return getApiUrl(
        computed((): string => {
            const idValue = toValue(id);
            if (idValue === null) {
                throw new Error("Can't find station ID!");
            }

            const suffixValue = toValue(suffix);
            return `/station/${idValue}${suffixValue}`;
        })
    );
}
