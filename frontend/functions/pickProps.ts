import {reactivePick} from "@vueuse/core";
import {keys} from "lodash";
import { UnwrapRef } from "vue";

export function pickProps<K extends object, T extends Readonly<{[key: string]: any}>>(
    props: T,
    subset: K
): {
    [S in keyof T]: UnwrapRef<T[S]>
} {
    const propNames: (keyof T)[] = keys(subset);

    return reactivePick<T, keyof T>(
        props,
        ...propNames
    )
}
