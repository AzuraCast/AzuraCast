import {MaybeRef, toValue} from "vue";

export default function populateComponentRemotely(url: MaybeRef) {
    return {
        meta: {
            remoteUrl: toValue(url),
            state: {}
        },
        props: (to) => {
            return to.meta.state;
        }
    }
}
