import Media from '~/components/Stations/Media.vue';
import initApp from "~/layout";
import useStationPanelLayout from "~/layouts/StationPanelLayout";

initApp(useStationPanelLayout(Media));
