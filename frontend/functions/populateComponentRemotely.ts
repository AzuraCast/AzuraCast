import {MaybeRef, toValue} from "vue";
import {RouteRecord} from "vue-router";

export default function populateComponentRemotely(url: MaybeRef) {
    return {
        meta: {
            remoteUrl: toValue(url),
            state: {}
        },
        props: (to: RouteRecord) => {
            return to.meta.state;
        }
    }
}
