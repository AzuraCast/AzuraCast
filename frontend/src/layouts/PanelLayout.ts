import {useAzuraCast} from "~/vendor/azuracast";
import {Component, h} from "vue";
import PanelLayoutComponent from "~/components/PanelLayout.vue";
import {useProvidePlayerStore} from "~/functions/usePlayerStore.ts";

export default function usePanelLayout(component: Component) {
    return {
        setup() {
            const {panelProps, componentProps} = useAzuraCast();

            useProvidePlayerStore('global');

            return {
                panelProps,
                componentProps
            }
        },
        render() {
            return h(
                PanelLayoutComponent,
                this.panelProps,
                {
                    default: () => h(component, this.componentProps)
                }
            );
        }
    }
}
