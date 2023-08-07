import AdminUpdates from '~/components/Admin/Updates.vue';
import initApp from "~/layout";
import useAdminPanelLayout from "~/layouts/AdminPanelLayout";

initApp(useAdminPanelLayout(AdminUpdates));
