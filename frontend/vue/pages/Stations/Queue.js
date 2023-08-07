import Queue from '~/components/Stations/Queue.vue';
import initApp from "~/layout";
import useStationPanelLayout from "~/layouts/StationPanelLayout";

initApp(useStationPanelLayout(Queue));
