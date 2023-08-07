import {useAzuraCast} from "~/vendor/azuracast";
import {Component, h} from "vue";
import PanelLayoutComponent from "~/components/PanelLayout.vue";
import Sidebar from "~/components/Stations/Sidebar.vue";

export default function useStationPanelLayout(component: string | Component) {
    return {
        setup() {
            const {panelProps, sidebarProps, componentProps} = useAzuraCast();

            return {
                panelProps,
                sidebarProps,
                componentProps
            }
        },
        render() {
            return h(
                PanelLayoutComponent,
                this.panelProps,
                {
                    sidebar: () => h(Sidebar, this.sidebarProps),
                    default: () => h(component, this.componentProps)
                }
            );
        }
    }
}
