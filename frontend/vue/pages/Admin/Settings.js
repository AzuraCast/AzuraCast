import AdminSettings from '~/components/Admin/Settings.vue';
import initApp from "~/layout";
import useAdminPanelLayout from "~/layouts/AdminPanelLayout";

initApp(useAdminPanelLayout(AdminSettings));
