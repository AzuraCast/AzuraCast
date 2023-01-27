import {reactivePick} from "@vueuse/core";
import {keys} from "lodash";

export function pickProps(props, subset) {
    return reactivePick(
        props,
        ...keys(subset)
    )
}
