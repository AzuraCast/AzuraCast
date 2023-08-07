import ProfileEdit from '~/components/Stations/ProfileEdit.vue';
import initApp from "~/layout";
import useStationPanelLayout from "~/layouts/StationPanelLayout";

initApp(useStationPanelLayout(ProfileEdit));
