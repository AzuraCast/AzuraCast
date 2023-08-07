import Requests from '~/components/Stations/Reports/Requests.vue';
import initApp from "~/layout";
import useStationPanelLayout from "~/layouts/StationPanelLayout";

initApp(useStationPanelLayout(Requests));
