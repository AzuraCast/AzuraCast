import '~/store';

import Playlists from '~/components/Stations/Playlists.vue';
import initApp from "~/layout";
import useStationPanelLayout from "~/layouts/StationPanelLayout";

initApp(useStationPanelLayout(Playlists));
