import {RouteRecord} from "vue-router";

export default function populateComponentRemotely(apiUrl: string) {
    return {
        meta: {
            remoteUrl: apiUrl,
            state: {}
        },
        props: (to: RouteRecord) => {
            return to.meta.state;
        }
    }
}
