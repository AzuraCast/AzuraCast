import {reactivePick} from "@vueuse/core";
import {keys} from "lodash";

export function pickProps<T extends object, K extends T>(
    props: T,
    subset: K
): Pick<T, keyof K> {
    return reactivePick(
        props,
        ...keys(subset)
    )
}
