import isObject from "~/functions/isObject";
import {toRaw} from "vue";
import {cloneDeep} from "es-toolkit";

/*
 * A "deep" merge that only merges items from the source into the destination that already exist in the destination.
 * Useful for merging in form values with API returns.
 */
export default function mergeExisting<T extends Record<any, any>>(
    destRaw: T,
    sourceRaw: Partial<T>
): T {
    const dest = toRaw(destRaw);
    const source = toRaw(sourceRaw);

    const ret: T = cloneDeep(dest);
    for (const destKey in dest) {
        if (destKey in source && dest[destKey] !== undefined && source[destKey] !== undefined) {
            const destVal = dest[destKey];
            const sourceVal = source[destKey];

            if (isObject(sourceVal) && isObject(destVal)) {
                ret[destKey] = mergeExisting(destVal, sourceVal);
            } else {
                ret[destKey] = sourceVal;
            }
        }
    }

    return ret;
}
