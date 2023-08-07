import Streamers from '~/components/Stations/Streamers.vue';
import initApp from "~/layout";
import useStationPanelLayout from "~/layouts/StationPanelLayout";

initApp(useStationPanelLayout(Streamers));
