import {useAzuraCastStation} from "~/vendor/azuracast.ts";

export enum QueryKeys {
    StationMedia = 'StationMedia'
}

export const queryKeyWithStation = (
    prefix: unknown[],
    suffix?: unknown[]
): unknown[] => {
    const {id} = useAzuraCastStation();

    const newQueryKeys = [...prefix];
    newQueryKeys.push({station: id});

    if (suffix) {
        newQueryKeys.push(...suffix);
    }

    return newQueryKeys;
}
