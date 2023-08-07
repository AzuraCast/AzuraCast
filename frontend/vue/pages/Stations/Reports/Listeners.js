import Listeners from '~/components/Stations/Reports/Listeners.vue';
import initApp from "~/layout";
import useStationPanelLayout from "~/layouts/StationPanelLayout";

initApp(useStationPanelLayout(Listeners));
