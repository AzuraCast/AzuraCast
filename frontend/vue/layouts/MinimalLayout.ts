import {useAzuraCast} from "~/vendor/azuracast";
import {Component, h} from "vue";

export default function useMinimalLayout(component: string | Component) {
    return {
        setup() {
            const {componentProps} = useAzuraCast();
            return {
                componentProps
            }
        },
        render() {
            return h(component, this.componentProps);
        }
    }
}
