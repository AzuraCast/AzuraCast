import {useAzuraCast} from "~/vendor/azuracast";
import {Component, h} from "vue";
import PanelLayoutComponent from "~/components/PanelLayout.vue";

export default function usePanelLayout(component: Component) {
    return {
        setup() {
            const {panelProps, componentProps} = useAzuraCast();

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
