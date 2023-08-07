import Fallback from '~/components/Stations/Fallback.vue';
import initApp from "~/layout";
import useStationPanelLayout from "~/layouts/StationPanelLayout";

initApp(useStationPanelLayout(Fallback));
