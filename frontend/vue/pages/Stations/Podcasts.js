import Podcasts from '~/components/Stations/Podcasts.vue';
import initApp from "~/layout";
import useStationPanelLayout from "~/layouts/StationPanelLayout";

initApp(useStationPanelLayout(Podcasts));
