import {computed, MaybeRefOrGetter, toValue} from "vue";
import {useStationId} from "~/functions/useStationQuery.ts";

export function useApiRouter() {
    const stationId = useStationId();

    const getApiUrl = (suffix: MaybeRefOrGetter<string>) => computed(
        (): string => {
            const suffixValue = toValue(suffix);
            return `/api${suffixValue}`;
        }
    );

    const getStationApiUrl = (
        suffix: MaybeRefOrGetter<string>,
        id?: MaybeRefOrGetter<string | number | null>
    ) => getApiUrl(
        computed((): string => {
            if (id !== undefined) {
                id = toValue(id);
            } else {
                id = stationId.value;
            }

            if (id === null) {
                throw new Error("Can't find station ID!");
            }

            const suffixValue = toValue(suffix);
            return `/station/${id}${suffixValue}`;
        })
    );

    return {
        getApiUrl,
        getStationApiUrl
    };
}
