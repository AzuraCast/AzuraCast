import {useAzuraCast} from "~/vendor/azuracast";
import type {Component} from "vue";
import {defineComponent, h} from "vue";
import MinimalLayoutComponent from "~/components/MinimalLayout.vue";

export default function useMinimalLayout(component: Component) {
    return defineComponent({
        setup() {
            const {componentProps} = useAzuraCast();

            return {
                componentProps
            }
        },
        render() {
            return h(
                MinimalLayoutComponent,
                {},
                {
                    default: () => h(component, this.componentProps)
                }
            );
        }
    });
}
