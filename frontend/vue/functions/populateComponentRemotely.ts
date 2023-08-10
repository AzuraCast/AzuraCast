import axios from "axios";
import {MaybeRef, toValue} from "vue";

export default function populateComponentRemotely(url: MaybeRef) {
    return {
        beforeEnter: (to, from, next) => {
            axios.get(toValue(url)).then((resp) => {
                Object.assign(to.meta, {
                    state: resp.data
                });
                next();
            });
        },
        props: (route) => ({
            ...route.meta.state
        })
    }
}
