import HlsStreams from '~/components/Stations/HlsStreams.vue';
import initApp from "~/layout";
import useStationPanelLayout from "~/layouts/StationPanelLayout";

initApp(useStationPanelLayout(HlsStreams));
