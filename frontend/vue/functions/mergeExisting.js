/*
 * A "deep" merge that only merges items from the source into the destination that already exist in the destination.
 * Useful for merging in form values with API returns.
 */
export default function mergeExisting(dest, source) {
    let ret = {};
    for (const destKey in dest) {
        if (destKey in source) {
            const destVal = dest[destKey];
            const sourceVal = source[destKey];
            if (typeof destVal === "object" && typeof sourceVal === "object") {
                ret[destKey] = mergeExisting(destVal, sourceVal);
            } else {
                ret[destKey] = sourceVal;
            }
        } else {
            ret[destKey] = dest[destKey];
        }
    }
    return ret;
}
