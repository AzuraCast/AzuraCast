import {reactivePick} from "@vueuse/core";
import {keys} from "lodash";

export function pickProps<K extends Readonly<object>, T extends object>(
    props: T,
    subset: K
): Pick<T, keyof K> {
    return reactivePick(
        props,
        ...keys(subset)
    )
}
