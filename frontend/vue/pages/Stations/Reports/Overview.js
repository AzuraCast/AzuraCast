import Overview from '~/components/Stations/Reports/Overview.vue';
import initApp from "~/layout";
import useStationPanelLayout from "~/layouts/StationPanelLayout";

initApp(useStationPanelLayout(Overview));
